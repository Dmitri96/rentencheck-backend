<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Auth\GetAuthenticatedUserAction;
use App\Actions\Auth\LoginAction;
use App\Actions\Auth\LogoutAction;
use App\Actions\Auth\RegisterAction;
use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SPA authentication endpoints.
 *
 * Thin HTTP layer — all business logic lives in the Auth Actions.
 * Errors are handled by the global exception renderer in bootstrap/app.php.
 */
final class AuthController extends BaseApiController
{
    public function register(RegisterRequest $request, RegisterAction $action): JsonResponse
    {
        $result = $action->execute($request->validated());

        return $this->createdResponse(
            ['user' => $result['user'], 'token' => $result['token']],
            $result['message'],
        );
    }

    public function login(LoginRequest $request, LoginAction $action): JsonResponse
    {
        $result = $action->execute($request->validated());

        return $this->successResponse(
            ['user' => $result['user'], 'token' => $result['token']],
            $result['message'],
        );
    }

    public function user(Request $request, GetAuthenticatedUserAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $result = $action->execute($user);

        return $this->successResponse(
            ['user' => $result['user'], 'permissions' => $result['permissions']],
            $result['message'],
        );
    }

    public function logout(Request $request, LogoutAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $result = $action->execute($user, $request);

        return $this->successResponse([], $result['message']);
    }
}
