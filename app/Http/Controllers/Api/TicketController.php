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
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int)$request->query('per_page', 15);

        $tickets = $this->ticketService->paginate($perPage);

        return response()->json([
            'data' => TicketResource::collection($tickets),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
        ]);
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
     * @param Ticket $ticket
     * @return JsonResponse
     */
    public function destroy(Ticket $ticket): JsonResponse
    {
        $this->ticketService->delete($ticket);

        return response()->json([
            'message' => 'Ticket deleted',
        ]);
    }
}
