<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TicketStoreRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\Tickets\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * @param TicketStoreRequest $request
     * @return JsonResponse
     */
    public function store(TicketStoreRequest $request): JsonResponse
    {
        $ticket = $this->ticketService->create(
            title: $request->string('title')->toString(),
            description: $request->string('description')->toString(),
        );

        return response()->json([
            'message' => 'Ticket created',
            'data' => new TicketResource($ticket),
        ], 201);
    }

    /**
     * @param Ticket $ticket
     * @return TicketResource
     */
    public function show(Ticket $ticket): TicketResource
    {
        return new TicketResource($ticket);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
