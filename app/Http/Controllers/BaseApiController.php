<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Thin base for JSON API controllers.
 *
 * Centralises the response envelope (`{ data, message? }`) so every endpoint
 * looks the same to the frontend. Error responses come from the global
 * exception renderer in bootstrap/app.php — controllers should not catch
 * exceptions themselves.
 */
abstract class BaseApiController extends Controller
{
    use AuthorizesRequests;

    /**
     * 200 OK with a consistent envelope.
     *
     * Accepts an array, an Eloquent Resource, or a ResourceCollection. The
     * data is unwrapped from JsonResource when passed directly so the caller
     * doesn't have to choose between $resource and $resource->response().
     *
     * @param  array<string, mixed>|JsonResource|ResourceCollection  $data
     */
    protected function successResponse(
        array|JsonResource|ResourceCollection $data,
        ?string $message = null,
        int $status = 200,
    ): JsonResponse {
        $payload = ['data' => $data];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        return response()->json($payload, $status);
    }

    /**
     * 201 Created shortcut for store endpoints.
     *
     * @param  array<string, mixed>|JsonResource|ResourceCollection  $data
     */
    protected function createdResponse(
        array|JsonResource|ResourceCollection $data,
        ?string $message = null,
    ): JsonResponse {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * 204 No Content for delete endpoints and other empty-body successes.
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
