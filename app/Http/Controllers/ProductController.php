<?php

namespace App\Http\Controllers;

use App\Models\Core\BaseModel;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Helpers\PermissionsHelper;
use App\Models\ProductProviderCost;
use App\Models\ProductClientCost;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ProductController extends Controller
{
    public function __construct()
    {
        DB::setDefaultConnection('strong-bulls');
    }

    public function listProducts(Request $request): \Illuminate\Http\JsonResponse
    {
        //Permissões
        $user_can_list = PermissionsHelper::CAN_ACCESS('product', 'list');
        $user_can_create = PermissionsHelper::CAN_ACCESS('product', 'create');

        if (!$user_can_list) {
            // Senao tiver permissao para listar.
            $list = collect();
        } else {
            // Inicio da Query
            $query = Product::with(
                'providers.categories',
                'providers.location',
                'providers.costs',
                'clients.prices',
                'location',
                'category',
                'client_prices.client.prices',
                'costs.provider.categories',
                'costs.provider.location');

            // Filtros
            $categoryFilter = $request['categoryFilter']; // array
            $locationFilter = $request['locationFilter']; // array
            $searchFilter = $request['searchFilter'];     // string
            $pageSize = $request['pageSize'];

            if (!empty($categoryFilter) && is_array($categoryFilter)) {
                $query->whereIn('category_id', $categoryFilter);
            }
            if (!empty($locationFilter) && is_array($locationFilter)) {
                $query->whereIn('location_id', $locationFilter);
            }

            if (!empty($searchFilter)) {
                $query->where(function ($q) use ($searchFilter) {
                    $q->where('name', 'like', '%' . $searchFilter . '%')
                        ->orWhereHas('providers', function ($q2) use ($searchFilter) {
                            $q2->where('name', 'like', '%' . $searchFilter . '%');
                        });
                });
            }
            //Ordenar pelo nome
            $query->orderBy('name');

            // Retorna paginado ou todas as entradas
            if (is_numeric($pageSize)) {
                $list = $query->paginate($pageSize);

                // Transformo a collection, para preservar os dados sobre a paginacao.
                $list->getCollection()->transform(fn($product) => $this->transformProductData($product));
            } else {
                // Sem paginação
                $list = $query->get()->map(fn($product) => $this->transformProductData($product));
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

    public function insertProduct(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $user_can_create = PermissionsHelper::CAN_ACCESS('product', 'create');

        // Valido a permissao sobre criar.
        if (!$user_can_create) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create a product.'
            ], 403); // 403 = Forbidden
        }

        // Se tiver permissao valido os dados e prossigo.
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'duration' => 'nullable|string',
            'operation_days' => 'nullable|string',
            'providers' => 'nullable|array',
            'providers.*' => 'exists:providers,id', // assegura que todos os ids dos providers existem na tabela do provider e nenhum e invalido,
            'clients' => 'nullable|array',
            'clients.*' => 'exists:clients,id', // assegura que todos os ids dos clientes existem na tabela do client e nenhum e invalido
            'location_id' => 'nullable|exists:locations,id',
            'category_id' => 'nullable|exists:categories,id',
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

        $product = new Product();
        $product->name = $validated['name'];
        $product->description = $validated['description'] ?? null;
        $product->duration = $validated['duration'] ?? null;
        $product->operation_days = $validated['operation_days'] ?? null;
        $product->created_by = $user->id;
        $product->updated_by = $user->id;
        $product->location()->associate($validated['location_id'] ?? null);
        $product->category()->associate($validated['category_id'] ?? null);
        $product->attachments = json_encode($uploadedFiles);
        $product->save();

        // Associo os fornecedores ao nosso produto acabado de criar.
        // Sync - Diferente do Attach pois atualiza, ou seja se recebi 3 ids novos e tinha 3 associacoes antigas, ele limpa as antigas e cria os novos, se ja existir so atualiza.
        $product->providers()->sync($request->input('providers', []));

        // Associo os clientes ao nosso produto acabado de criar.
        $product->clients()->sync($request->input('clients', []));

        // Associo os novos custos dos providers ao produto.
        if (isset($request['new_costs']) && is_array($request['new_costs'])) {
            foreach ($request['new_costs'] as $costData) {
                // Valido se tenho todos os dados necessarios
                if (
                    isset($costData['provider_id']) &&
                    isset($costData['price']) &&
                    isset($costData['year'])
                ) {
                    ProductProviderCost::create([
                        'product_id' => $product->id,
                        'provider_id' => $costData['provider_id'],
                        'price' => $costData['price'],
                        'year' => $costData['year'],
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]);
                }
            }
        }

        // Associo os novos preços dos clientes ao produto.
        if (isset($request['new_prices_client']) && is_array($request['new_prices_client'])) {
            foreach ($request['new_prices_client'] as $costData) {
                // Valido se tenho todos os dados necessarios
                if (
                    isset($costData['client_id']) &&
                    isset($costData['price']) &&
                    isset($costData['year'])
                ) {
                    ProductClientCost::create([
                        'product_id' => $product->id,
                        'client_id' => $costData['client_id'],
                        'price' => $costData['price'],
                        'year' => $costData['year'],
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]);
                }
            }
        }

        return response()->json(['success' => true, 'data' => $product]);
    }

    public function updateProduct(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $user_can_edit = PermissionsHelper::CAN_ACCESS('product', 'edit');

        // Valido a permissao sobre editar.
        if (!$user_can_edit) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update the product.'
            ], 403); // 403 = Forbidden
        }

        // Se tiver permissao valido os dados e prossigo.
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'duration' => 'nullable|string',
            'operation_days' => 'nullable|string',
            'providers' => 'nullable|array',
            'providers.*' => 'exists:providers,id', // assegura que todos os ids dos providers existem na tabela do provider e nenhum e invalido
            'clients' => 'nullable|array',
            'clients.*' => 'exists:clients,id', // assegura que todos os ids dos clientes existem na tabela do client e nenhum e invalido
            'location_id' => 'nullable|exists:locations,id',
            'category_id' => 'nullable|exists:categories,id',
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

        // Atualizo o Produto
        $product = Product::find($request->id);
        $product->name = $validated['name'];
        $product->description = $validated['description'] ?? null;
        $product->duration = $validated['duration'] ?? null;
        $product->operation_days = $validated['operation_days'] ?? null;
        $product->updated_by = $user->id;
        $product->location()->associate($validated['location_id'] ?? null);
        $product->category()->associate($validated['category_id'] ?? null);
        $product->attachments = json_encode($finalAttachments);
        $product->save();

        //Associo os fornecedores ao nosso produto.
        // Sync - Diferente do Attach pois atualiza, ou seja se recebi 3 ids novos e tinha 3 associacoes antigas, ele limpa as antigas e cria os novos, se ja existir so atualiza.
        $product->providers()->sync($request->input('providers', []));

        //Associo os clients ao nosso produto.
        $product->clients()->sync($request->input('clients', []));

        // =========== CUSTOS DOS FORNECEDORES =================//
        // Removo custos associados entre produto e provider existentes
        // Como ha o caso em que posso remover um existente e adicionar como novo logo de seguida
        // É preferivel primeiro eu tratar de remover associações e só depois associar as novas.
        if (isset($request['deleted_costs']) && is_array($request['deleted_costs'])) {
            foreach ($request['deleted_costs'] as $costData) {
                if (isset($costData['provider_id'])) {
                    ProductProviderCost::where('product_id', $product->id)
                        ->where('provider_id', $costData['provider_id'])
                        ->where('year', $costData['year'])
                        ->delete();
                }
            }
        }

        // Associo os novos custos ao produto.
        if (isset($request['new_costs']) && is_array($request['new_costs'])) {
            foreach ($request['new_costs'] as $costData) {
                // Valido se tenho todos os dados necessarios
                if (
                    isset($costData['provider_id']) &&
                    isset($costData['price']) &&
                    isset($costData['year'])
                ) {
                    ProductProviderCost::create([
                        'product_id' => $product->id,
                        'provider_id' => $costData['provider_id'],
                        'price' => $costData['price'],
                        'year' => $costData['year'],
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]);
                }
            }
        }

        // Atualizo custos existentes (se houver)
        if (isset($request['updated_costs']) && is_array($request['updated_costs'])) {
            foreach ($request['updated_costs'] as $costData) {
                if (
                    isset($costData['provider_id']) &&
                    isset($costData['price'])
                ) {
                    ProductProviderCost::where('product_id', $product->id)
                        ->where('provider_id', $costData['provider_id'])
                        ->where('year', $costData['year'])
                        ->update([
                            'price' => $costData['price'],
                            'updated_by' => $user->id,
                        ]);
                }
            }
        }

        // =========== CUSTOS/PREÇOS DOS CLIENTES =================//
        // Removo custos associados entre produto e client existentes
        // Como ha o caso em que posso remover um existente e adicionar como novo logo de seguida
        if (isset($request['deleted_prices_client']) && is_array($request['deleted_prices_client'])) {
            foreach ($request['deleted_prices_client'] as $costData) {
                if (isset($costData['client_id'])) {
                    ProductClientCost::where('product_id', $product->id)
                        ->where('client_id', $costData['client_id'])
                        ->where('year', $costData['year'])
                        ->delete();
                }
            }
        }

        // Associo os novos preços ao produto.
        if (isset($request['new_prices_client']) && is_array($request['new_prices_client'])) {
            foreach ($request['new_prices_client'] as $costData) {
                // Valido se tenho todos os dados necessarios
                if (
                    isset($costData['client_id']) &&
                    isset($costData['price']) &&
                    isset($costData['year'])
                ) {
                    ProductClientCost::create([
                        'product_id' => $product->id,
                        'client_id' => $costData['client_id'],
                        'price' => $costData['price'],
                        'year' => $costData['year'],
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]);
                }
            }
        }

        // Atualizo custos existentes (se houver)
        if (isset($request['updated_prices_client']) && is_array($request['updated_prices_client'])) {
            foreach ($request['updated_prices_client'] as $costData) {
                if (
                    isset($costData['client_id']) &&
                    isset($costData['price'])
                ) {
                    ProductClientCost::where('product_id', $product->id)
                        ->where('client_id', $costData['client_id'])
                        ->where('year', $costData['year'])
                        ->update([
                            'price' => $costData['price'],
                            'updated_by' => $user->id,
                        ]);
                }
            }
        }

        return response()->json(['success' => true, 'data' => $product]);
    }

    public function deleteProduct($id): \Illuminate\Http\JsonResponse
    {
        $user_can_delete = PermissionsHelper::CAN_ACCESS('product', 'delete');

        // Valido a permissao sobre editar.
        if (!$user_can_delete) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete the product.'
            ], 403); // 403 = Forbidden
        }

        // Se tiver permissao vou buscar os dados do produto para remover.
        $product = Product::with('providers')->find($id);

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        // Detach dos fornecedores associados ao produto (limpa a entry da pivot table)
        $product->providers()->detach();

        // Elimino a associação do produto e os custos do fornecedor
        $product->costs()->delete();

        // Detach dos clientes associados ao produto (limpa a entry da pivot table)
        $product->clients()->detach();

        // Elimino a associação entre os produto e os custos do cliente
        $product->client_prices()->delete();

        // Elimino o produto
        $product->delete();
        return response()->json(['success' => true, 'data' => $product]);
    }

    private function transformProductData($product): array
    {
        //Permissoes Editar, Eliminar e Detalhes
        $user_can_edit = PermissionsHelper::CAN_ACCESS('product', 'edit');
        $user_can_delete = PermissionsHelper::CAN_ACCESS('product', 'delete');
        $user_can_details = PermissionsHelper::CAN_ACCESS('product', 'details');

        // Ficheiros para um formato que seja aceite pelo el-upload filesList no front end
        // Corremos o array guardado na coluna attachments e contruimos um novo com os objetos definidos no formato desejado (name, url, size, type, status)
        $attachments = collect(json_decode($product->attachments ?? '[]', true))
            ->map(function ($item) {
                return [
                    'name' => $item['name'] ?? basename($item['path']),
                    'url' => asset($item['path']),
                    'size' => $item['size'] ?? null,
                    'type' => $item['type'] ?? null,
                    'status' => 'success',
                ];
            })->toArray();

        // ================ PROVIDER DATA FORMATTING =========== //
        // Divido os Custos do Fornecedor por anos
        $providersByYear = [];
        foreach ($product->costs as $cost) {
            $year = $cost->year;
            $providerId = $cost->provider_id;

            //Se nao existir index para o ano do custo então criamos.
            if (!isset($providersByYear[$year])) {
                $providersByYear[$year] = [];
            }

            // Asseguro que nao ha 2 custos do mesmo provider/produto com o mesmo ano. So pode ter 1 por ano.
            // Se o provider não tem um custo nesse exato ano então consideramos.
            if (!in_array($providerId, $providersByYear[$year])) {
                $providersByYear[$year][] = $providerId;
            }
        }

        // Ordeno os dados por ordem decrescente (ano)
        krsort($providersByYear);

        //Organizo os custos através da prioridade do provider.
        $sortedCosts = $product->costs
            ->sortByDesc(function ($cost) {
                return $cost->provider->priority ?? 0;
            })
            ->values();

        // Filtro os dados dos providers/custos que serao mostrados nos detalhes
        $currentYear = Carbon::now()->year;
        $providersData = $product->providers
            // Filtro apenas providers que tenham um custo para este produto no ano atual
            ->filter(function ($provider) use ($product, $currentYear) {
                return $provider->costs->contains(function ($cost) use ($product, $currentYear) {
                    return $cost->product_id === $product->id && $cost->year == $currentYear;
                });
            })
            // Ordeno pela prioridade descendente do provider.
            ->sortByDesc('priority')
            // Apenas os primeiros 5
            ->take(5)
            // Defino costData com o custo associado ao produto/provider do ano atual
            ->map(function ($provider) use ($product, $currentYear) {
                $cost = $provider->costs->firstWhere(function ($cost) use ($product, $currentYear) {
                    return $cost->product_id === $product->id && $cost->year == $currentYear;
                });

                $provider->costData = $cost;
                unset($provider->costs);
                return $provider;
            })
            ->values();


        // ================ CLIENT DATA FORMATTING =========== //
        // Divido os Precos do client por anos
        $clientsByYear = [];
        foreach ($product->client_prices as $price) {
            $year = $price->year;
            $clientId = $price->client_id;

            //Se nao existir index para o ano do custo então criamos.
            if (!isset($clientsByYear[$year])) {
                $clientsByYear[$year] = [];
            }

            // Asseguro que nao ha 2 custos do mesmo cliente/produto com o mesmo ano. So pode ter 1 por ano.
            // Se o cliente não tem um custo nesse exato ano então consideramos.
            if (!in_array($clientId, $clientsByYear[$year])) {
                $clientsByYear[$year][] = $clientId;
            }
        }
        // Ordeno os dados por ordem decrescente (ano)
        krsort($clientsByYear);

        // Filtro os dados dos clientes/precos que serao mostrados nos detalhes
        $currentYear = Carbon::now()->year;
        $clientsData = $product->clients
            // Filtro apenas clientes que tenham um preco para este produto no ano atual
            ->filter(function ($client) use ($product, $currentYear) {
                return $client->prices->contains(function ($price) use ($product, $currentYear) {
                    return $price->product_id === $product->id && $price->year == $currentYear;
                });
            })
            // Apenas os primeiros 5
            ->take(5)
            // Defino costData com o preco associado ao produto/cliente do ano atual
            ->map(function ($client) use ($product, $currentYear) {
                $cost = $client->prices->firstWhere(function ($price) use ($product, $currentYear) {
                    return $price->product_id === $product->id && $price->year == $currentYear;
                });

                $client->costData = $cost;
                unset($client->prices);
                return $client;
            })
            ->values();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'duration' => $product->duration,
            'operation_days' => $product->operation_days,
            'location_id' => $product->location_id,
            'category_id' => $product->category_id,
            'location' => $product->location,
            'category' => $product->category,
            'providers' => $product->providers->pluck('id'), // Apenas os ids
            'clients' => $product->clients->pluck('id'), // Apenas os ids
            'providers_by_year' => $providersByYear, //fornecedores divididos por ano.
            'clients_by_year' => $clientsByYear, //clientes divididos por ano.
            'costs' => $sortedCosts,
            'client_prices' => $product->client_prices,
            'attachments' => $attachments,
            'can_edit' => $user_can_edit,
            'can_delete' => $user_can_delete,
            'can_details' => $user_can_details,
            'providersData' => $providersData,
            'clientsData' => $clientsData
        ];
    }
}
