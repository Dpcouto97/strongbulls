<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Helpers\PermissionsHelper;

class CheckPermission
{
    /**
     * Handle an incoming request.
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string $resource
     * @param string $action
     */
    public function handle(Request $request, Closure $next, string $resource, string $action)
    {
        if (!PermissionsHelper::CAN_ACCESS($resource, $action)) {
            abort(403, 'Unauthorized: You do not have permission to view this page.');
        }

        return $next($request);
    }
}
