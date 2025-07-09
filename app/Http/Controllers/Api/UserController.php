<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use App\Helpers\PermissionsHelper;

class UserController extends Controller
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
        //Permissões
        $user_can_list = PermissionsHelper::CAN_ACCESS('user', 'list');
        $user_can_create = PermissionsHelper::CAN_ACCESS('user', 'create');
        $user_can_edit = PermissionsHelper::CAN_ACCESS('user', 'edit');
        $user_can_delete = PermissionsHelper::CAN_ACCESS('user', 'delete');
        $user_can_details = PermissionsHelper::CAN_ACCESS('user', 'details');

        if (!$user_can_list) {
            // Senao tiver permissao para listar.
            $list = collect();
        } else {
            // Inicio da Query
            $query = User::with('user_group');

            //Aplicamos o filtro
            $searchFilter = $request['searchFilter'];
            $pageSize = $request['pageSize'];

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

    /**
     * Create a new user
     */
    public function create(Request $request): \Illuminate\Http\JsonResponse
    {
        $user_can_create = PermissionsHelper::CAN_ACCESS('user', 'create');

        // Valido a permissao sobre criar.
        if (!$user_can_create) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create a user.'
            ], 403); // 403 = Forbidden
        }

        // Se tiver permissao valido os dados e prossigo.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
            'user_group_id' => ['nullable', 'exists:user_groups,id'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'user_group_id' => $validated['user_group_id'] ?? null,
        ]);

        return response()->json(['success' => true, 'data' => $user]);
    }

    /**
     * Update User
     */
    public function update(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $user_can_edit = PermissionsHelper::CAN_ACCESS('user', 'edit');

        // Valido a permissao sobre editar.
        if (!$user_can_edit) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update the user.'
            ], 403); // 403 = Forbidden
        }

        // Se tiver permissao valido os dados e prossigo.
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', Password::default(), 'confirmed'],
            'user_group_id' => ['nullable', 'exists:user_groups,id'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->user_group_id = $validated['user_group_id'] ?? null;
        $user->save();

        return response()->json(['success' => true, 'data' => $user]);
    }

    public function delete($id): \Illuminate\Http\JsonResponse
    {
        $user_can_delete = PermissionsHelper::CAN_ACCESS('user', 'delete');

        // Valido a permissao sobre editar.
        if (!$user_can_delete) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete the user.'
            ], 403); // 403 = Forbidden
        }

        // Se tiver permissao vou buscar os dados da categoria para remover.
        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['success' => true, 'data' => $user]);
    }
}
