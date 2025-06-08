<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ClientController extends Controller
{
    /**
     * Display a listing of clients based on user role.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Client::query();

        // Admins can see all clients, advisors only see their own
        if (!$request->user()->isAdmin()) {
            $query->forUser($request->user()->id);
        }

        $clients = $query->active()
            ->with('user') // Include user relationship for admin view
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15);

        return ClientResource::collection($clients);
    }

    /**
     * Store a newly created client.
     */
    public function store(StoreClientRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $client = Client::create([
                'user_id' => $request->user()->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'street' => $request->street,
                'city' => $request->city,
                'postal_code' => $request->postal_code,
                'birth_date' => $request->birth_date,
                'notes' => $request->notes,
            ]);

            DB::commit();

            Log::info('Client created successfully', [
                'client_id' => $client->id,
                'user_id' => $request->user()->id,
                'client_email' => $client->email,
            ]);

            return response()->json([
                'client' => new ClientResource($client),
                'message' => 'Mandant erfolgreich angelegt',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create client', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'data' => $request->validated(),
            ]);

            return response()->json([
                'message' => 'Ein Fehler ist beim Anlegen des Mandanten aufgetreten',
                'error' => 'Interner Serverfehler',
            ], 500);
        }
    }

    /**
     * Display the specified client.
     */
    public function show(Request $request, Client $client): JsonResponse
    {
        // Ensure the client belongs to the authenticated user (unless admin)
        if (!$request->user()->isAdmin() && $client->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Mandant nicht gefunden',
            ], 404);
        }

        return response()->json([
            'client' => new ClientResource($client->load('user')),
        ]);
    }

    /**
     * Update the specified client.
     */
    public function update(StoreClientRequest $request, Client $client): JsonResponse
    {
        // Ensure the client belongs to the authenticated user (unless admin)
        if (!$request->user()->isAdmin() && $client->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Mandant nicht gefunden',
            ], 404);
        }

        try {
            DB::beginTransaction();

            $client->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'street' => $request->street,
                'city' => $request->city,
                'postal_code' => $request->postal_code,
                'birth_date' => $request->birth_date,
                'notes' => $request->notes,
            ]);

            DB::commit();

            Log::info('Client updated successfully', [
                'client_id' => $client->id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'client' => new ClientResource($client->fresh()),
                'message' => 'Mandant erfolgreich aktualisiert',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update client', [
                'client_id' => $client->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Ein Fehler ist beim Aktualisieren des Mandanten aufgetreten',
                'error' => 'Interner Serverfehler',
            ], 500);
        }
    }

    /**
     * Remove the specified client (soft delete by marking as inactive).
     */
    public function destroy(Request $request, Client $client): JsonResponse
    {
        // Ensure the client belongs to the authenticated user (unless admin)
        if (!$request->user()->isAdmin() && $client->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Mandant nicht gefunden',
            ], 404);
        }

        try {
            DB::beginTransaction();

            $client->update(['is_active' => false]);

            DB::commit();

            Log::info('Client deactivated successfully', [
                'client_id' => $client->id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Mandant erfolgreich deaktiviert',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to deactivate client', [
                'client_id' => $client->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Ein Fehler ist beim Deaktivieren des Mandanten aufgetreten',
                'error' => 'Interner Serverfehler',
            ], 500);
        }
    }
}
