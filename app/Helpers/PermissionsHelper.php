<?php

namespace App\Helpers;

use App\Models\UserGroupPermission;
use Illuminate\Support\Facades\Auth;
use Error;
use Exception;

/**
 * Check if the authenticated user has a specific permission on a module.
 *
 * @param string $module
 * @param string $permissionType
 * @return bool
 */
class PermissionsHelper
{
    public static function CAN_ACCESS(string $module, string $permissionType): bool
    {
        // Dados do utilizador da sessao
        $user = Auth::user();

        // Se for Admin ignoramos todas as permissões
        if ($user->is_admin) {
            return true;
        }

        // Se nao obter os dados do utilizador ou o mesmo nao pertencer a user group entao nao tem acesso
        if (!$user || !$user->user_group_id) {
            return false;
        }

        // Confirmo se o permissionType é uma coluna/permissao valida.
        $validPermissions = ['list', 'create', 'edit', 'delete', 'details'];
        if (!in_array($permissionType, $validPermissions)) {
            return false;
        }

        // Verifico se o utilizador tem a permissão e retorno o valor da mesma.
        $permission = UserGroupPermission::where('user_group_id', $user->user_group_id)
            ->where('module', $module)
            ->first();

        // Retorno o valor da permissão.
        return $permission ? (bool)$permission->$permissionType : false;
    }
}
