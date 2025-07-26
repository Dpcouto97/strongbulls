<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Helpers\PermissionsHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ExerciseController extends Controller
{
    public function __construct()
    {
        DB::setDefaultConnection('strong-bulls');
    }

    public function listExercises(Request $request): \Illuminate\Http\JsonResponse
    {
        //Permissões
        $user_can_list = PermissionsHelper::CAN_ACCESS('exercise', 'list');
        $user_can_create = PermissionsHelper::CAN_ACCESS('exercise', 'create');

        // Verifico se devo ignorar a permissao pois o metodo está a devolver dados para uma listagem
        $bypassPermission = $request->boolean('byPassPermission');

        if (!$user_can_list && !$bypassPermission) {
            // Senao tiver permissao para listar ou é para ignorar a permissao
            $list = collect();
        } else {
            // Inicio da Query
            $query = Exercise::query();

            // Filtros
            $searchFilter = $request['searchFilter'];
            $muscleGroupFilter = $request['muscleGroupFilter'];
            $pageSize = $request['pageSize'];
            $sortBy = $request->input('sortBy', 'name'); // default 'date'
            $sortOrder = $request->input('sortOrder', 'asc'); // default' desc'

            if (!empty($searchFilter)) {
                $query->where('name', 'like', '%' . $searchFilter . '%');
            }
            if (!empty($muscleGroupFilter) && is_array($muscleGroupFilter)) {
                $query->whereIn('muscle_group', $muscleGroupFilter);
            }

            // Validação segura do sortBy e sortOrder
            $allowedSortFields = ['name'];
            $allowedSortOrder = ['asc', 'desc'];

            if (in_array($sortBy, $allowedSortFields) && in_array($sortOrder, $allowedSortOrder)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                // Default sort
                $query->orderBy('name', 'asc');
            }

            // Retorna paginado ou todas as entradas
            if (is_numeric($pageSize)) {
                $list = $query->paginate($pageSize);

                // Transformo apenas a collection, para preservar os dados sobre a paginacao.
                $list->getCollection()->transform(fn($exercise) => $this->transformExerciseData($exercise));
            } else {
                // Sem paginação
                $list = $query->get()->map(fn($exercise) => $this->transformExerciseData($exercise));
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

    public function insertExercise(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $user_can_create = PermissionsHelper::CAN_ACCESS('exercise', 'create');

        // Valido a permissao sobre criar.
        if (!$user_can_create) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create a exercise.'
            ], 403); // 403 = Forbidden
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'muscle_group' => 'nullable|string',
            'description' => 'nullable|string',
        ]);


        // Na criação de um produto apenas me limito a associar os novos ficheiros e guardar no servidor.
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

        $exercise = new Exercise();
        $exercise->name = $validated['name'];
        $exercise->muscle_group = $validated['muscle_group'];
        $exercise->created_by = $user->id;
        $exercise->updated_by = $user->id;
        $exercise->attachments = json_encode($uploadedFiles);
        $exercise->save();

        return response()->json(['success' => true, 'data' => $exercise]);
    }

    public function updateExercise(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $user_can_edit = PermissionsHelper::CAN_ACCESS('exercise', 'edit');

        // Valido a permissao sobre editar.
        if (!$user_can_edit) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update the exercise.'
            ], 403); // 403 = Forbidden
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'muscle_group' => 'nullable|string',
            'description' => 'nullable|string',
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

        $exercise = Exercise::find($request->id);
        $exercise->name = $validated['name'];
        $exercise->muscle_group = $validated['muscle_group'];
        $exercise->description = $validated['description'];
        $exercise->updated_by = $user->id;
        $exercise->attachments = json_encode($finalAttachments);
        $exercise->save();


        return response()->json(['success' => true, 'data' => $exercise]);
    }

    public function deleteExercise($id): \Illuminate\Http\JsonResponse
    {
        $user_can_delete = PermissionsHelper::CAN_ACCESS('exercise', 'delete');

        // Valido a permissao sobre remover.
        if (!$user_can_delete) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete the exercise.'
            ], 403); // 403 = Forbidden
        }

        // Se tiver permissao vou buscar os dados do exercise para remover.
        $exercise = Exercise::query()->find($id);

        if (!$exercise) {
            return response()->json(['success' => false, 'message' => 'Exercise not found'], 404);
        }

        $exercise->delete();
        return response()->json(['success' => true, 'data' => $exercise]);
    }

    private function transformExerciseData($exercise): array
    {
        //Permissoes Editar, Eliminar e Detalhes
        $user_can_edit = PermissionsHelper::CAN_ACCESS('exercise', 'edit');
        $user_can_delete = PermissionsHelper::CAN_ACCESS('exercise', 'delete');
        $user_can_details = PermissionsHelper::CAN_ACCESS('exercise', 'details');

        // Ficheiros para um formato que seja aceite pelo el-upload filesList no front end
        // Corremos o array guardado na coluna attachments e contruimos um novo com os objetos definidos no formato desejado (name, url, size, type, status)
        $attachments = collect(json_decode($exercise->attachments ?? '[]', true))
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
            'id' => $exercise->id,
            'name' => $exercise->name,
            'muscle_group' => $exercise->muscle_group,
            'description' => $exercise->description,
            'attachments' => $attachments,
            'can_edit' => $user_can_edit,
            'can_delete' => $user_can_delete,
            'can_details' => $user_can_details
        ];
    }
}
