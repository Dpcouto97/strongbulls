<?php

namespace App\Http\Controllers;

use App\Models\Core\BaseModel;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\PermissionsHelper;
use Illuminate\Support\Facades\Auth;

class PlanController extends Controller
{
    public function __construct()
    {
        DB::setDefaultConnection('strong-bulls');
    }

    public function listPlans(Request $request): \Illuminate\Http\JsonResponse
    {
        //Permissões
        $user_can_list = PermissionsHelper::CAN_ACCESS('plan', 'list');
        $user_can_create = PermissionsHelper::CAN_ACCESS('plan', 'create');
        $user_can_edit = PermissionsHelper::CAN_ACCESS('plan', 'edit');
        $user_can_delete = PermissionsHelper::CAN_ACCESS('plan', 'delete');
        $user_can_details = PermissionsHelper::CAN_ACCESS('plan', 'details');

        $bypassPermission = $request->boolean('byPassPermission');

        if (!$user_can_list && !$bypassPermission) {
            // Senao tiver permissao para listar.
            $list = collect();
        } else {
            // Inicio da Query
            $query = Plan::with('clients');
            $searchFilter = $request['searchFilter'];
            $clientFilter = $request['clientFilter']; // array
            $pageSize = $request['pageSize'];
            $sortBy = $request->input('sortBy', 'name'); // default 'name'
            $sortOrder = $request->input('sortOrder', 'asc'); // default' desc

            // Como é uma relacao N-N necessario usar o whereHas para especificar a relacao que fui buscarf com o with em cima.
            if (!empty($clientFilter) && is_array($clientFilter)) {
                $query->whereHas('clients', function ($q) use ($clientFilter) {
                    $q->whereIn('clients.id', $clientFilter);
                });
            }

            //Aplicamos o filtro
            if (!empty($searchFilter)) {
                $query->where('name', 'like', '%' . $searchFilter . '%');
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
            // Confirmo se é para paginar ou devolver todos os dados.
            // Depende dos parametros passados no metodo da api.
            if (is_numeric($pageSize)) {
                $list = $query->paginate($pageSize);

                // Como os dados estao paginados temos uma colecao e nao um array normal, temos de usar o transform para adicionar as variaveis das permissoes
                // Necessario para ir como plain array e nao um modelo eloquent, para desta forma conseguir enviar os clients apenas o id etc.
                $transformed = $list->getCollection()->map(function ($item) use ($user_can_edit, $user_can_delete, $user_can_details) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'description' => $item->description,
                        'type' => $item->type,
                        'created_at' => $item->created_at,
                        'updated_at' => $item->updated_at,
                        'created_by' => $item->created_by,
                        'updated_by' => $item->updated_by,
                        'can_edit' => $user_can_edit,
                        'can_delete' => $user_can_delete,
                        'can_details' => $user_can_details,
                        'clients' => $item->clients->pluck('id')->toArray(), // <-- This is now clean
                    ];
                });
                $list->setCollection($transformed);
            } else {
                $list = $query->get();
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

    public function insertPlan(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = Auth()->user();
        $user_can_create = PermissionsHelper::CAN_ACCESS('plan', 'create');

        // Valido a permissao sobre criar.
        if (!$user_can_create) {
            return response()->json([
                'success' => false,
                'message' => 'Não tens permissão para criar um novo plano'
            ], 403); // 403 = Forbidden
        }

        // Se tiver permissao valido os dados e prossigo.
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'nullable|string',
            'description' => 'nullable|string',
            'clients' => 'nullable|array',
            'clients.*' => 'exists:clients,id',
        ]);

        $plan = new Plan();
        $plan->name = $validated['name'];
        $plan->description = $validated['description'] ?? null;
        $plan->type = $validated['type'] ?? null;
        $plan->created_by = $user->id;
        $plan->updated_by = $user->id;
        $plan->save();

        if (isset($validated['clients'])) {
            $plan->clients()->sync($validated['clients']);
        }

        return response()->json(['success' => true, 'data' => $plan]);
    }

    public function updatePlan(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $user_can_edit = PermissionsHelper::CAN_ACCESS('plan', 'edit');

        // Valido a permissao sobre editar.
        if (!$user_can_edit) {
            return response()->json([
                'success' => false,
                'message' => 'Não tens permissão para editar o plano'
            ], 403); // 403 = Forbidden
        }

        // Se tiver permissao valido os dados e prossigo.
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'nullable|string',
            'description' => 'nullable|string',
            'clients' => 'nullable|array',
            'clients.*' => 'exists:clients,id',
        ]);

        $plan = Plan::find($request->id);
        $plan->name = $validated['name'];
        $plan->description = $validated['description'] ?? null;
        $plan->type = $validated['type'] ?? null;
        $plan->updated_by = $user->id;
        $plan->save();

        if (isset($validated['clients'])) {
            $plan->clients()->sync($validated['clients']);
        }

        return response()->json(['success' => true, 'data' => $plan]);
    }

    public function deletePlan($id): \Illuminate\Http\JsonResponse
    {
        $user_can_delete = PermissionsHelper::CAN_ACCESS('plan', 'delete');

        // Valido a permissao sobre editar.
        if (!$user_can_delete) {
            return response()->json([
                'success' => false,
                'message' => 'Não tens permissão para eliminar o plano.'
            ], 403); // 403 = Forbidden
        }

        // Se tiver permissao vou buscar os dados da categoria para remover.
        $plan = Plan::with('clients')->find($id);

        if (!$plan) {
            return response()->json(['success' => false, 'message' => 'Plan not found'], 404);
        }

        // Detach dos clientes associados ao plano (limpa a entry da pivot table)
        $plan->clients()->detach();

        // Elimino o plano
        $plan->delete();

        return response()->json(['success' => true, 'data' => $plan]);
    }
}
