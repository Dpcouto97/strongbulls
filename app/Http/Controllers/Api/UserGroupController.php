<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\UserGroup;
use App\Models\UserGroupPermission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use App\Helpers\PermissionsHelper;
use Illuminate\Support\Facades\Auth;

class UserGroupController extends Controller
{
    public function __construct()
    {
        DB::setDefaultConnection('strong-bulls');
    }

    /**
     * List Users
     */
    public function list(Request $request): \Illuminate\Http\JsonResponse
    {
        $searchFilter = $request['searchFilter'];
        $pageSize = $request['pageSize'];

        $query = UserGroup::with('permissions');

        // Filtro por nome ou email.
        if (!empty($searchFilter)) {
            $query->where(function ($q) use ($searchFilter) {
                $q->where('name', 'like', '%' . $searchFilter . '%')
                    ->orWhere('email', 'like', '%' . $searchFilter . '%');
            });
        }

        //Ordenar pelo nome
        $query->orderBy('name');

        // Confirmo se é para paginar ou devolver todos os dados.
        // Depende dos parametros passados no metodo da api.
        if (is_numeric($pageSize)) {
            $list = $query->paginate($pageSize);
        } else {
            $list = $query->get();
        }

        return response()->json(['success' => true, 'data' => $list]);
    }

    /**
     * Create a new user
     */
    public function create(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
            'permissions' => ['required', 'array'],
            'permissions.*.module' => ['required', 'string'],
            'permissions.*.list' => ['required', 'boolean'],
            'permissions.*.create' => ['required', 'boolean'],
            'permissions.*.edit' => ['required', 'boolean'],
            'permissions.*.delete' => ['required', 'boolean'],
            'permissions.*.details' => ['required', 'boolean'],
        ]);

        // Asseguro desta forma que so executo, caso todas as operacoes sejam bem sucedidas.
        DB::beginTransaction();

        try {
            // Crio o Grupo
            $group = UserGroup::create([
                'name' => $validated['name'],
                'status' => $validated['status']
            ]);

            // Associo as permissoes
            foreach ($validated['permissions'] as $permission) {
                UserGroupPermission::create([
                    'user_group_id' => $group->id,
                    'module' => $permission['module'],
                    'list' => $permission['list'],
                    'create' => $permission['create'],
                    'edit' => $permission['edit'],
                    'delete' => $permission['delete'],
                    'details' => $permission['details'],
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
            }

            DB::commit();

            return response()->json(['success' => true, 'data' => $group]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update User
     */
    public function update(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
            'permissions' => ['required', 'array']
        ]);

        $permissions = $validated['permissions'];

        // Asseguro desta forma que so executo, caso todas as operacoes sejam bem sucedidas.
        // Agrupa varias operações de BD como se fosse apenas uma.
        DB::beginTransaction();

        try {
            //Atualizo o Grupo
            $group = UserGroup::findOrFail($id);
            $group->update([
                'name' => $validated['name'],
                'status' => $validated['status']
            ]);

            // Processo as permissoes
            foreach ($permissions as $permission) {
                UserGroupPermission::updateOrCreate(
                    [
                        'user_group_id' => $group->id,
                        'module' => $permission['module'],
                    ],
                    [
                        'list' => $permission['list'],
                        'create' => $permission['create'],
                        'edit' => $permission['edit'],
                        'delete' => $permission['delete'],
                        'details' => $permission['details'],
                        'updated_by' => $user->id,
                    ]
                );
            }

            DB::commit();

            return response()->json(['success' => true, 'data' => $group]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function delete($id): \Illuminate\Http\JsonResponse
    {
        $group = UserGroup::find($id);

        if (!$group) {
            return response()->json(['success' => false, 'message' => ' User Group not found'], 404);
        }

        if ($group->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete group with users associated.'
            ], 400);

        }

        DB::beginTransaction();

        try {
            // Forco a remocao das entradas da tabela de permissoes associadas a este utilizador
            // Neste caso especifico nao preencho apenas as colunas SoftDelete, faz mais sentido remover o registo da tabela.
            $group->permissions()->forceDelete();

            // Elimino o Grupo
            $group->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $group
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error deleting group',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
