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

        // Verifico se devo ignorar a permissao pois o metodo está a devolver dados para uma listagem
        $bypassPermission = $request->boolean('byPassPermission');

        if (!$user_can_list && !$bypassPermission) {
            // Senao tiver permissao para listar.
            $list = collect();
        } else {
            // Inicio da Query
            $query = Plan::query();
            $searchFilter = $request['searchFilter'];
            $pageSize = $request['pageSize'];

            //Aplicamos o filtro
            if (!empty($searchFilter)) {
                $query->where('name', 'like', '%' . $searchFilter . '%');
            }

            //Ordenar pelo nome
            $query->orderBy('name');

            // Confirmo se é para paginar ou devolver todos os dados.
            // Depende dos parametros passados no metodo da api.
            if (is_numeric($pageSize)) {
                $list = $query->paginate($pageSize);
                // Como os dados estao paginados temos uma colecao e nao um array normal, temos de usar o transform para adicionar as variaveis das permissoes
                $list->getCollection()->transform(function ($item) use ($user_can_edit, $user_can_delete, $user_can_details) {
                    $item->can_edit = $user_can_edit;
                    $item->can_delete = $user_can_delete;
                    $item->can_details = $user_can_details;
                    return $item;
                });
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
        $user = auth()->user();
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
        ]);

        $plan = new Plan();
        $plan->name = $validated['name'];
        $plan->created_by = $user->id;
        $plan->updated_by = $user->id;
        $plan->save();

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
        ]);

        $plan = Plan::find($request->id);
        $plan->name = $validated['name'];
        $plan->updated_by = $user->id;
        $plan->save();

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
        $category = Category::with('products')->find($id);

        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Category not found'], 404);
        }

        if ($category->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with associated products.'
            ], 400);

        } else if ($category->providers()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with associated providers.'
            ], 400);
        }

        $category->delete();

        return response()->json(['success' => true, 'data' => $category]);
    }
}
