<?php

namespace App\Http\Middleware;

use App\Helpers\JsonResponseHelper;
use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        try {
            $user = $request->user();
        } catch (\Exception $e) {
            return JsonResponseHelper::standardResponse(401, null, 'Unauthorized');
        }

        if (!in_array($user->role, $roles)) {
            return JsonResponseHelper::standardResponse(403, null, 'Forbidden - insufficient permissions');
        }

        return $next($request);
    }
}
