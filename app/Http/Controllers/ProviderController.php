<?php

namespace App\Http\Controllers;

use App\Models\Core\BaseModel;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Helpers\PermissionsHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProviderController extends Controller
{
    public function __construct()
    {
        DB::setDefaultConnection('strong-bulls');
    }

    public function listProviders(Request $request): \Illuminate\Http\JsonResponse
    {
        //Permissões
        $user_can_list = PermissionsHelper::CAN_ACCESS('provider', 'list');
        $user_can_create = PermissionsHelper::CAN_ACCESS('provider', 'create');

        // Verifico se devo ignorar a permissao pois o metodo está a devolver dados para uma listagem
        $bypassPermission = $request->boolean('byPassPermission');

        if (!$user_can_list && !$bypassPermission) {
            // Senao tiver permissao para listar  ou é para ignorar a permissao
            $list = collect();
        } else {
            // Inicio da Query
            $query = Provider::with('categories', 'location');

            // Filtros
            $categoryFilter = $request['categoryFilter']; // array
            $locationFilter = $request['locationFilter']; // array
            $searchFilter = $request['searchFilter'];     // string
            $pageSize = $request['pageSize'];

            // Como é uma relacao N-N necessario usar o whereHas para especificar a relacao que fui buscarf com o with em cima.
            if (!empty($categoryFilter) && is_array($categoryFilter)) {
                $query->whereHas('categories', function ($q) use ($categoryFilter) {
                    $q->whereIn('categories.id', $categoryFilter);
                });
            }
            if (!empty($locationFilter) && is_array($locationFilter)) {
                $query->whereIn('location_id', $locationFilter);
            }
            if (!empty($searchFilter)) {
                $query->where('name', 'like', '%' . $searchFilter . '%');
            }

            //Ordenar pelo nome
            $query->orderBy('name');

            // Retorna paginado ou todas as entradas
            if (is_numeric($pageSize)) {
                $list = $query->paginate($pageSize);

                // Transformo apenas a collection, para preservar os dados sobre a paginacao.
                $list->getCollection()->transform(fn($provider) => $this->transformProviderData($provider));
            } else {
                // Sem paginação
                $list = $query->get()->map(fn($provider) => $this->transformProviderData($provider));
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

    public function insertProvider(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $user_can_create = PermissionsHelper::CAN_ACCESS('provider', 'create');

        // Valido a permissao sobre criar.
        if (!$user_can_create) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create a provider.'
            ], 403); // 403 = Forbidden
        }

        // Se tiver permissao valido os dados e prossigo.
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'ceo_name' => 'nullable|string',
            'email' => 'nullable|array',
            'email.*' => 'nullable|email|max:255',
            'phone_number' => 'nullable|string',
            'address' => 'nullable|string',
            'nif' => 'nullable|string',
            'vat' => 'nullable|integer|min:0|max:100',
            'payment_policies' => 'nullable|string',
            'iban' => 'nullable|string',
            'cancellation_policies' => 'nullable|string',
            'schedule' => 'nullable|string',
            'location_id' => 'nullable|exists:locations,id',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id', // assegura que todos os ids dos providers existem na tabela do provider e nenhum e invalido
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

        $provider = new Provider();
        $provider->name = $validated['name'];
        $provider->ceo_name = $validated['ceo_name'];
        $cleanedEmails = array_filter($validated['email'] ?? [], fn($e) => !is_null($e) && $e !== '');
        $provider->email = json_encode(array_values($cleanedEmails));
        $provider->phone_number = $validated['phone_number'];
        $provider->address = $validated['address'];
        $provider->nif = $validated['nif'];
        $provider->vat = $validated['vat'] ?? 0;
        $provider->payment_policies = $validated['payment_policies'];
        $provider->iban = $validated['iban'];
        $provider->cancellation_policies = $validated['cancellation_policies'];
        $provider->schedule = $validated['schedule'];
        $provider->priority = $request['priority'];
        $provider->created_by = $user->id;
        $provider->updated_by = $user->id;
        $provider->location()->associate($validated['location_id'] ?? null);
        $provider->attachments = json_encode($uploadedFiles);
        $provider->save();

        if (isset($validated['categories'])) {
            $provider->categories()->sync($validated['categories']);
        }

        return response()->json(['success' => true, 'data' => $provider]);
    }

    public function updateProvider(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $user_can_edit = PermissionsHelper::CAN_ACCESS('provider', 'edit');

        // Valido a permissao sobre editar.
        if (!$user_can_edit) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update the provider.'
            ], 403); // 403 = Forbidden
        }

        // Se tiver permissao valido os dados e prossigo.
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'ceo_name' => 'nullable|string',
            'email' => 'nullable|array',
            'email.*' => 'nullable|email|max:255',
            'phone_number' => 'nullable|string',
            'address' => 'nullable|string',
            'nif' => 'nullable|string',
            'vat' => 'nullable|integer|min:0|max:100',
            'payment_policies' => 'nullable|string',
            'iban' => 'nullable|string',
            'cancellation_policies' => 'nullable|string',
            'schedule' => 'nullable|string',
            'location_id' => 'nullable|exists:locations,id',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id', // assegura que todos os ids dos providers existem na tabela do provider e nenhum e invalido
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

        $provider = Provider::find($request->id);
        $provider->name = $validated['name'];
        $provider->ceo_name = $validated['ceo_name'];
        $cleanedEmails = array_filter($validated['email'] ?? [], fn($e) => !is_null($e) && $e !== '');
        $provider->email = json_encode(array_values($cleanedEmails));
        $provider->phone_number = $validated['phone_number'];
        $provider->address = $validated['address'];
        $provider->nif = $validated['nif'];
        $provider->vat = $validated['vat'] ?? 0;
        $provider->payment_policies = $validated['payment_policies'];
        $provider->iban = $validated['iban'];
        $provider->cancellation_policies = $validated['cancellation_policies'];
        $provider->schedule = $validated['schedule'];
        $provider->priority = $request['priority'];
        $provider->updated_by = $user->id;
        $provider->location()->associate($validated['location_id'] ?? null);
        $provider->attachments = json_encode($finalAttachments);
        $provider->save();

        if (isset($validated['categories'])) {
            $provider->categories()->sync($validated['categories']);
        }

        return response()->json(['success' => true, 'data' => $provider]);
    }

    public function deleteProvider($id): \Illuminate\Http\JsonResponse
    {
        $user_can_delete = PermissionsHelper::CAN_ACCESS('provider', 'delete');

        // Valido a permissao sobre remover.
        if (!$user_can_delete) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete the provider.'
            ], 403); // 403 = Forbidden
        }

        // Se tiver permissao vou buscar os dados do provider para remover.
        $provider = Provider::with('products')->find($id);

        if (!$provider) {
            return response()->json(['success' => false, 'message' => 'Provider not found'], 404);
        }

        // Detach dos produtos associados ao fornecedor (limpa a entry da pivot table)
        $provider->products()->detach();

        // Elimino a associação do custo com provider
        $provider->costs()->delete();

        $provider->delete();
        return response()->json(['success' => true, 'data' => $provider]);
    }

    private function transformProviderData($provider): array
    {
        //Permissoes Editar, Eliminar e Detalhes
        $user_can_edit = PermissionsHelper::CAN_ACCESS('provider', 'edit');
        $user_can_delete = PermissionsHelper::CAN_ACCESS('provider', 'delete');
        $user_can_details = PermissionsHelper::CAN_ACCESS('provider', 'details');

        // Ficheiros para um formato que seja aceite pelo el-upload filesList no front end
        // Corremos o array guardado na coluna attachments e contruimos um novo com os objetos definidos no formato desejado (name, url, size, type, status)
        $attachments = collect(json_decode($provider->attachments ?? '[]', true))
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
            'id' => $provider->id,
            'name' => $provider->name,
            'ceo_name' => $provider->ceo_name,
            'email' => json_decode($provider->email, true),
            'phone_number' => $provider->phone_number,
            'address' => $provider->address,
            'nif' => $provider->nif,
            'vat' => $provider->vat,
            'iban' => $provider->iban,
            'schedule' => $provider->schedule,
            'payment_policies' => $provider->payment_policies,
            'cancellation_policies' => $provider->cancellation_policies,
            'priority' => $provider->priority,
            'location_id' => $provider->location_id,
            'location' => $provider->location,
            'categories' => $provider->categories->pluck('id'),
            'categoriesData' => $provider->categories,
            'attachments' => $attachments,
            'can_edit' => $user_can_edit,
            'can_delete' => $user_can_delete,
            'can_details' => $user_can_details
        ];
    }
}
