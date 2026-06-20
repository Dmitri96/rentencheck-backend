<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Clients\CreateClientAction;
use App\Actions\Clients\DeleteClientAction;
use App\Actions\Clients\UpdateClientAction;
use App\Data\Clients\ClientData;
use App\Http\Controllers\BaseApiController;
use App\Http\Requests\StoreClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Thin HTTP layer for client CRUD.
 *
 * - Validation lives in StoreClientRequest (request-time).
 * - Authorization lives in ClientPolicy ($this->authorize calls).
 * - Business logic lives in app/Actions/Clients/*.
 * - Error/transaction handling lives in the global exception renderer
 *   (bootstrap/app.php) and the per-Action DB::transaction wrappers.
 */
final class ClientController extends BaseApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Client::class);

        $query = Client::query()
            ->when(
                ! $request->user()->isAdmin(),
                fn ($q) => $q->forUser($request->user()->id),
            )
            ->active()
            ->with('user')
            ->orderBy('last_name')
            ->orderBy('first_name');

        return ClientResource::collection($query->paginate(15));
    }

    public function store(StoreClientRequest $request, CreateClientAction $action): JsonResponse
    {
        $this->authorize('create', Client::class);

        $client = $action->execute(
            ClientData::from($request->validated()),
            $request->user(),
        );

        return $this->createdResponse(
            new ClientResource($client),
            'Mandant erfolgreich angelegt',
        );
    }

    public function show(Client $client): JsonResponse
    {
        $this->authorize('view', $client);

        return $this->successResponse(new ClientResource($client->load('user')));
    }

    public function update(StoreClientRequest $request, Client $client, UpdateClientAction $action): JsonResponse
    {
        $this->authorize('update', $client);

        $updated = $action->execute($client, ClientData::from($request->validated()));

        return $this->successResponse(
            new ClientResource($updated),
            'Mandant erfolgreich aktualisiert',
        );
    }

    public function destroy(Client $client, DeleteClientAction $action): JsonResponse
    {
        $this->authorize('delete', $client);

        $action->execute($client);

        return $this->successResponse(
            ['id' => $client->id],
            'Mandant erfolgreich deaktiviert',
        );
    }
}
