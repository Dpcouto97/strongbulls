<?php

namespace App\Http\Controllers;

use App\Models\Core\BaseModel;
use App\Models\Evaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Helpers\PermissionsHelper;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EvaluationController extends Controller
{
    public function __construct()
    {
        DB::setDefaultConnection('strong-bulls');
    }

    public function listEvaluations(Request $request): \Illuminate\Http\JsonResponse
    {
        //Permissões
        $user_can_list = PermissionsHelper::CAN_ACCESS('evaluation', 'list');
        $user_can_create = PermissionsHelper::CAN_ACCESS('evaluation', 'create');

        if (!$user_can_list) {
            // Senao tiver permissao para listar.
            $list = collect();
        } else {
            // Inicio da Query
            $query = Evaluation::with('client');

            // Filtros
            $clientFilter = $request['clientFilter']; // array
            $dateFilter = $request->input('dateFilter');
            $pageSize = $request['pageSize'];
            $sortBy = $request->input('sortBy', 'date'); // default 'date'
            $sortOrder = $request->input('sortOrder', 'desc'); // default' desc'

            if (!empty($clientFilter) && is_array($clientFilter)) {
                $query->whereIn('client_id', $clientFilter);
            }

            if (is_array($dateFilter) && count($dateFilter) === 2) {
                $start = $dateFilter[0];
                $end = $dateFilter[1];
                $query->whereBetween('date', [$start, $end]);
            }

            // Validação segura do sortBy e sortOrder
            $allowedSortFields = ['date'];
            $allowedSortOrder = ['asc', 'desc'];

            if (in_array($sortBy, $allowedSortFields) && in_array($sortOrder, $allowedSortOrder)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                // Default sort
                $query->orderBy('date', 'desc');
            }

            // Retorna paginado ou todas as entradas
            if (is_numeric($pageSize)) {
                $list = $query->paginate($pageSize);

                // Transformo a collection, para preservar os dados sobre a paginacao.
                $list->getCollection()->transform(fn($evaluation) => $this->transformEvaluationData($evaluation));
            } else {
                // Sem paginação
                $list = $query->get()->map(fn($evaluation) => $this->transformEvaluationData($evaluation));
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'list' => $list,
                'permissions' => [
                    'can_create' => $user_can_create,
                ]
            ]
        ]);
    }

    public function insertEvaluation(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $user_can_create = PermissionsHelper::CAN_ACCESS('evaluation', 'create');

        // Valido a permissao sobre criar.
        if (!$user_can_create) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create a evaluation.'
            ], 403); // 403 = Forbidden
        }

        // Se tiver permissao valido os dados e prossigo.
        $validated = $request->validate([
            'description' => 'nullable|string',
            'date' => 'required|date',
            'bmr' => 'nullable|integer',
            'weight' => 'nullable|numeric',
            'imc' => 'nullable|numeric',
            'muscle_mass' => 'nullable|numeric',
            'bone_mass' => 'nullable|numeric',
            'visceral_fat' => 'nullable|integer',
            'body_fat' => 'nullable|numeric',
            'body_water' => 'nullable|numeric',
            'client_id' => 'required|exists:clients,id',
        ]);

        // Na criação de uma Avaliacao apenas me limito a associar os novos ficheiros e guardar no servidor.
        // Upload dos novos ficheiros
        $uploadedFiles = [];
        if ($request->hasFile('new_attachments')) {
            // Se tiver novos ficheiros, para cada um faço store do mesmo no servidor e guardo a info do ficheiro e o seu path no array $uploadedFiles
            foreach ($request->file('new_attachments') as $index => $file) {
                $path = $file->store('uploads', 'public');
                $data = $request->input("new_attachments_data.$index", []); // Extraio as keys e valores de new_attachments_data para $data.

                $uploadedFiles[] = [
                    'path' => '/storage/' . $path,
                    'name' => $data['name'] ?? $file->getClientOriginalName(),
                    'size' => $data['size'] ?? $file->getSize(),
                    'type' => $data['type'] ?? $file->getClientMimeType(),
                ];
            }
        }

        $evaluation = new Evaluation();
        $evaluation->date = $validated['date'] ?? null;
        $evaluation->weight = $validated['weight'] ?? null;
        $evaluation->imc = $validated['imc'] ?? null;
        $evaluation->muscle_mass = $validated['muscle_mass'] ?? null;
        $evaluation->bone_mass = $validated['bone_mass'] ?? null;
        $evaluation->bmr = $validated['bmr'] ?? null;
        $evaluation->visceral_fat = $validated['visceral_fat'] ?? null;
        $evaluation->body_fat = $validated['body_fat'] ?? null;
        $evaluation->body_water = $validated['body_water'] ?? null;
        $evaluation->description = $validated['description'] ?? null;
        $evaluation->created_by = $user->id;
        $evaluation->updated_by = $user->id;
        $evaluation->client()->associate($validated['client_id'] ?? null);
        $evaluation->attachments = json_encode($uploadedFiles);
        $evaluation->save();

        return response()->json(['success' => true, 'data' => $evaluation]);
    }

    public function updateEvaluation(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $user_can_edit = PermissionsHelper::CAN_ACCESS('evaluation', 'edit');

        // Valido a permissao sobre editar.
        if (!$user_can_edit) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update the evaluation.'
            ], 403); // 403 = Forbidden
        }

        // Se tiver permissao valido os dados e prossigo.
        $validated = $request->validate([
            'description' => 'nullable|string',
            'date' => 'required|date',
            'bmr' => 'nullable|integer',
            'weight' => 'nullable|numeric',
            'imc' => 'nullable|numeric',
            'muscle_mass' => 'nullable|numeric',
            'bone_mass' => 'nullable|numeric',
            'visceral_fat' => 'nullable|integer',
            'body_fat' => 'nullable|numeric',
            'body_water' => 'nullable|numeric',
            'client_id' => 'required|exists:clients,id',
        ]);

        //Ficheiros
        // 1. Processo os ficheiros a serem removidos
        $deleted = json_decode($request->deleted_attachments ?? '[]', true);
        $deletedPaths = collect($deleted)
            ->map(function ($file) {
                if (!isset($file['url'])) return null;

                // Converto o URL para /storage/... Originalmente tem a parte do Localhost:8000 etc.
                $urlPath = parse_url($file['url'], PHP_URL_PATH); // exemplo: "/storage/uploads/file1.pdf"

                // Retiro o prefixo "/storage/" para obter o storage path
                return str_replace('/storage/', '', $urlPath); // e.g. "uploads/file1.pdf"
            })
            ->filter()
            ->values()
            ->toArray();

        // Removo os ficheiros da Storage do servidor
        foreach ($deletedPaths as $relativePath) {
            Storage::disk('public')->delete($relativePath);
        }

        // 2. Upload dos novos ficheiros
        $uploadedFiles = [];
        if ($request->hasFile('new_attachments')) {
            // Se tiver novos ficheiros, para cada um faço store do mesmo no servidor e guardo a info do ficheiro e o seu path no array $uploadedFiles
            foreach ($request->file('new_attachments') as $index => $file) {
                $path = $file->store('uploads', 'public');
                $data = $request->input("new_attachments_data.$index", []); // Extraio as keys e valores de new_attachments_data para $data.

                $uploadedFiles[] = [
                    'path' => '/storage/' . $path,
                    'name' => $data['name'] ?? $file->getClientOriginalName(),
                    'size' => $data['size'] ?? $file->getSize(),
                    'type' => $data['type'] ?? $file->getClientMimeType(),
                ];
            }
        }

        // 3. Merge dos dados sobre ficheiros existentes com os ficheiros novos e nao considerando os removidos.
        $existingAttachments = collect(json_decode($request->existing_attachments ?? '[]', true))
            ->filter(function ($item) use ($deletedPaths) {
                // Se nao tiver path ignoro o ficheiro
                if (!isset($item['path'])) return false;

                // Recolho apenas o path do URL
                $path = str_replace('/storage/', '', $item['path']);
                // Apenas mantenho o ficheiro se nao se encontrar no array deletedPaths
                return !in_array($path, $deletedPaths);
            })
            ->values()
            ->toArray();

        // Ficheiros finais
        $finalAttachments = array_merge($existingAttachments, $uploadedFiles);

        // Atualizo a Avaliaçao
        $evaluation = Evaluation::find($request->id);
        $evaluation->date = $validated['date'] ?? null;
        $evaluation->weight = $validated['weight'] ?? null;
        $evaluation->imc = $validated['imc'] ?? null;
        $evaluation->muscle_mass = $validated['muscle_mass'] ?? null;
        $evaluation->bone_mass = $validated['bone_mass'] ?? null;
        $evaluation->bmr = $validated['bmr'] ?? null;
        $evaluation->visceral_fat = $validated['visceral_fat'] ?? null;
        $evaluation->body_fat = $validated['body_fat'] ?? null;
        $evaluation->body_water = $validated['body_water'] ?? null;
        $evaluation->description = $validated['description'] ?? null;
        $evaluation->updated_by = $user->id;
        $evaluation->client()->associate($validated['client_id'] ?? null);
        $evaluation->attachments = json_encode($finalAttachments);
        $evaluation->save();

        return response()->json(['success' => true, 'data' => $evaluation]);
    }

    public function deleteEvaluation($id): \Illuminate\Http\JsonResponse
    {
        $user_can_delete = PermissionsHelper::CAN_ACCESS('evaluation', 'delete');

        // Valido a permissao sobre editar.
        if (!$user_can_delete) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete the evaluation.'
            ], 403); // 403 = Forbidden
        }

        // Se tiver permissao vou buscar os dados da avaliaçao para remover.
        $evaluation = Evaluation::find($id);

        if (!$evaluation) {
            return response()->json(['success' => false, 'message' => 'Evaluation not found'], 404);
        }


        // Elimino a avaliaçao
        $evaluation->delete();
        return response()->json(['success' => true, 'data' => $evaluation]);
    }

    private function transformEvaluationData($evaluation): array
    {
        //Permissoes Editar, Eliminar e Detalhes
        $user_can_edit = PermissionsHelper::CAN_ACCESS('evaluation', 'edit');
        $user_can_delete = PermissionsHelper::CAN_ACCESS('evaluation', 'delete');
        $user_can_details = PermissionsHelper::CAN_ACCESS('evaluation', 'details');

        // Ficheiros para um formato que seja aceite pelo el-upload filesList no front end
        // Corremos o array guardado na coluna attachments e contruimos um novo com os objetos definidos no formato desejado (name, url, size, type, status)
        $attachments = collect(json_decode($evaluation->attachments ?? '[]', true))
            ->map(function ($item) {
                return [
                    'name' => $item['name'] ?? basename($item['path']),
                    'url' => asset($item['path']),
                    'size' => $item['size'] ?? null,
                    'type' => $item['type'] ?? null,
                    'status' => 'success',
                ];
            })->toArray();

        return [
            'id' => $evaluation->id,
            'client_id' => $evaluation->client_id,
            'date' => $evaluation->date,
            'weight' => $evaluation->weight,
            'imc' => $evaluation->imc,
            'muscle_mass' => $evaluation->muscle_mass,
            'bone_mass' => $evaluation->bone_mass,
            'bmr' => $evaluation->bmr,
            'visceral_fat' => $evaluation->visceral_fat,
            'body_fat' => $evaluation->body_fat,
            'body_water' => $evaluation->body_water,
            'description' => $evaluation->description,
            'client' => $evaluation->client,
            'attachments' => $attachments,
            'can_edit' => $user_can_edit,
            'can_delete' => $user_can_delete,
            'can_details' => $user_can_details,
        ];
    }
}
