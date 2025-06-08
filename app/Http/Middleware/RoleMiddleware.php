<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        // Check if user is authenticated
        if (!$user) {
            return response()->json([
                'message' => 'Nicht authentifiziert.',
                'error' => 'Unauthenticated'
            ], 401);
        }

        // Check if user is blocked
        if ($user->isBlocked()) {
            return response()->json([
                'message' => 'Ihr Account wurde gesperrt. Bitte kontaktieren Sie den Administrator.',
                'error' => 'Account blocked'
            ], 403);
        }

        // Check if user is active
        if (!$user->isActive()) {
            return response()->json([
                'message' => 'Ihr Account ist nicht aktiv.',
                'error' => 'Account inactive'
            ], 403);
        }

        // Check if user has any of the required roles
        if (!empty($roles) && !$user->hasAnyRole($roles)) {
            return response()->json([
                'message' => 'Sie haben nicht die erforderlichen Berechtigungen für diese Aktion.',
                'error' => 'Insufficient permissions',
                'required_roles' => $roles,
                'user_roles' => $user->getRoleNames()->toArray()
            ], 403);
        }

        return $next($request);
    }
} 