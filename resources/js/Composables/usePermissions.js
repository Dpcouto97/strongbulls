import { usePage } from '@inertiajs/vue3';

export default function usePermissions() {
    const user = usePage().props.auth.user;

    /**
     * Valida se o user tem permissão para realizar a ação sobre o modulo
     *
     * @param {String} module - Nome do Modulo (e.g., 'product', 'category')
     * @param {String} action - Ação (e.g., 'list', 'create', 'edit', 'delete', 'details')
     * @returns {Boolean}
     */
    const canAccess = (module, action) => {
        // Verifico se o utilizador é admin — tem acesso total
        if (user?.is_admin) {
            return true;
        }

        //Verifico se tenho os valores das permissões
        const permissions = user?.user_group?.permissions || [];

        //Vou buscar os dados da permissão sobre o devido modulo.
        const permission = permissions.find(p => p.module === module);

        // Retorno true se a permissão para a ação for true.
        return permission?.[action] === true;
    };

    return { canAccess };
}
