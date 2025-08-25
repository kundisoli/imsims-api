<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInventoryPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission = null): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Account deactivated'], 403);
        }

        // Check general inventory access
        if (!$user->canAccessInventory()) {
            return response()->json(['message' => 'Insufficient permissions to access inventory'], 403);
        }

        // Check specific permission if provided
        if ($permission && !$user->hasPermission($permission)) {
            return response()->json(['message' => "Insufficient permissions: {$permission}"], 403);
        }

        return $next($request);
    }
}