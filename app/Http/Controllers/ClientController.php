<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Helpers\PermissionsHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ClientController extends Controller
{
    public function __construct()
    {
        DB::setDefaultConnection('strong-bulls');
    }

    public function listClients(Request $request): \Illuminate\Http\JsonResponse
    {
        //Permissões
        $user_can_list = PermissionsHelper::CAN_ACCESS('client', 'list');
        $user_can_create = PermissionsHelper::CAN_ACCESS('client', 'create');

        // Verifico se devo ignorar a permissao pois o metodo está a devolver dados para uma listagem
        $bypassPermission = $request->boolean('byPassPermission');

        if (!$user_can_list && !$bypassPermission) {
            // Senao tiver permissao para listar  ou é para ignorar a permissao
            $list = collect();
        } else {
            // Inicio da Query
            $query = Client::query();

            // Filtros
            $searchFilter = $request['searchFilter'];
            $pageSize = $request['pageSize'];

            if (!empty($searchFilter)) {
                $query->where('name', 'like', '%' . $searchFilter . '%');
            }

            //Ordenar pelo nome
            $query->orderBy('name');

            // Retorna paginado ou todas as entradas
            if (is_numeric($pageSize)) {
                $list = $query->paginate($pageSize);

                // Transformo apenas a collection, para preservar os dados sobre a paginacao.
                $list->getCollection()->transform(fn($client) => $this->transformClientData($client));
            } else {
                // Sem paginação
                $list = $query->get()->map(fn($client) => $this->transformClientData($client));
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

    public function insertClient(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $user_can_create = PermissionsHelper::CAN_ACCESS('client', 'create');

        // Valido a permissao sobre criar.
        if (!$user_can_create) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create a client.'
            ], 403); // 403 = Forbidden
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone_number' => 'nullable|string',
            'address' => 'nullable|string',
            'nif' => 'nullable|string',
            'birth_date' => 'nullable|date',
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

        $client = new Client();
        $client->name = $validated['name'];
        $client->email = $validated['email'];;
        $client->phone_number = $validated['phone_number'];
        $client->description = $validated['description'];
        $client->address = $validated['address'];
        $client->nif = $validated['nif'];
        $client->birth_date = $validated['birth_date'];
        $client->created_by = $user->id;
        $client->updated_by = $user->id;
        $client->attachments = json_encode($uploadedFiles);
        $client->save();

        return response()->json(['success' => true, 'data' => $client]);
    }

    public function updateClient(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $user_can_edit = PermissionsHelper::CAN_ACCESS('client', 'edit');

        // Valido a permissao sobre editar.
        if (!$user_can_edit) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update the client.'
            ], 403); // 403 = Forbidden
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone_number' => 'nullable|string',
            'address' => 'nullable|string',
            'nif' => 'nullable|string',
            'birth_date' => 'nullable|date',
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

        $client = Client::find($request->id);
        $client->name = $validated['name'];
        $client->email = $validated['email'];;
        $client->phone_number = $validated['phone_number'];
        $client->address = $validated['address'];
        $client->nif = $validated['nif'];
        $client->birth_date = $validated['birth_date'];
        $client->description = $validated['description'];
        $client->updated_by = $user->id;
        $client->attachments = json_encode($finalAttachments);
        $client->save();


        return response()->json(['success' => true, 'data' => $client]);
    }

    public function deleteClient($id): \Illuminate\Http\JsonResponse
    {
        $user_can_delete = PermissionsHelper::CAN_ACCESS('client', 'delete');

        // Valido a permissao sobre remover.
        if (!$user_can_delete) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete the client.'
            ], 403); // 403 = Forbidden
        }

        // Se tiver permissao vou buscar os dados do client para remover.
        $client = Client::query()->find($id);

        if (!$client) {
            return response()->json(['success' => false, 'message' => 'Client not found'], 404);
        }

        $client->delete();
        return response()->json(['success' => true, 'data' => $client]);
    }

    private function transformClientData($client): array
    {
        //Permissoes Editar, Eliminar e Detalhes
        $user_can_edit = PermissionsHelper::CAN_ACCESS('client', 'edit');
        $user_can_delete = PermissionsHelper::CAN_ACCESS('client', 'delete');
        $user_can_details = PermissionsHelper::CAN_ACCESS('client', 'details');

        // Ficheiros para um formato que seja aceite pelo el-upload filesList no front end
        // Corremos o array guardado na coluna attachments e contruimos um novo com os objetos definidos no formato desejado (name, url, size, type, status)
        $attachments = collect(json_decode($client->attachments ?? '[]', true))
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
            'id' => $client->id,
            'name' => $client->name,
            'email' => $client->email,
            'phone_number' => $client->phone_number,
            'address' => $client->address,
            'nif' => $client->nif,
            'birth_date' => $client->birth_date,
            'description' => $client->description,
            'attachments' => $attachments,
            'can_edit' => $user_can_edit,
            'can_delete' => $user_can_delete,
            'can_details' => $user_can_details
        ];
    }
}
