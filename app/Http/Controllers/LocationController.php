<?php

namespace App\Http\Controllers;

use App\Models\Core\BaseModel;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\PermissionsHelper;
use Illuminate\Support\Facades\Auth;

class LocationController extends Controller
{
    public function __construct()
    {
        DB::setDefaultConnection('strong-bulls');
    }

    public function listLocations(Request $request): \Illuminate\Http\JsonResponse
    {
        //Permissões
        $user_can_list = PermissionsHelper::CAN_ACCESS('location', 'list');
        $user_can_create = PermissionsHelper::CAN_ACCESS('location', 'create');
        $user_can_edit = PermissionsHelper::CAN_ACCESS('location', 'edit');
        $user_can_delete = PermissionsHelper::CAN_ACCESS('location', 'delete');
        $user_can_details = PermissionsHelper::CAN_ACCESS('location', 'details');

        // Verifico se devo ignorar a permissao pois o metodo está a devolver dados para uma listagem
        $bypassPermission = $request->boolean('byPassPermission');

        if (!$user_can_list && !$bypassPermission) {
            // Senao tiver permissao para listar.
            $list = collect();
        } else {
            //Inicio da Query
            $query = Location::query();
            $searchFilter = $request['searchFilter'];
            $pageSize = $request['pageSize'];

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

    public function insertLocation(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $user_can_create = PermissionsHelper::CAN_ACCESS('location', 'create');

        // Valido a permissao sobre criar.
        if (!$user_can_create) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create a location.'
            ], 403); // 403 = Forbidden
        }

        // Se tiver permissao valido os dados e prossigo.
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $location = new Location();
        $location->name = $validated['name'];
        $location->created_by = $user->id;
        $location->updated_by = $user->id;
        $location->save();

        return response()->json(['success' => true, 'data' => $location]);
    }

    public function updateLocation(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $user_can_edit = PermissionsHelper::CAN_ACCESS('location', 'edit');

        // Valido a permissao sobre editar.
        if (!$user_can_edit) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update the location.'
            ], 403); // 403 = Forbidden
        }

        // Se tiver permissao valido os dados e prossigo.
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $location = Location::find($request->id);
        $location->name = $validated['name'];
        $location->updated_by = $user->id;
        $location->save();

        return response()->json(['success' => true, 'data' => $location]);
    }

    public function deleteLocation($id): \Illuminate\Http\JsonResponse
    {
        $user_can_delete = PermissionsHelper::CAN_ACCESS('location', 'delete');

        // Valido a permissao sobre editar.
        if (!$user_can_delete) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete the location.'
            ], 403); // 403 = Forbidden
        }

        // Se tiver permissao vou buscar os dados da localizacao para remover.
        $location = Location::with('products')->find($id);

        if (!$location) {
            return response()->json(['success' => false, 'message' => 'Location not found'], 404);
        }

        if ($location->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete location with associated products.'
            ], 400);

        } else if ($location->providers()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete location with associated providers.'
            ], 400);
        }

        $location->delete();

        return response()->json(['success' => true, 'data' => $location]);
    }
}

