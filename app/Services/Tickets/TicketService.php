<?php

namespace App\Services\Tickets;

use App\Jobs\EnrichTicketJob;
use App\Models\Ticket;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketService
{
    /**
     * @param string $title
     * @param string $description
     * @return Ticket
     */
    public function create(string $title, string $description): Ticket
    {
        return DB::transaction(function () use ($title, $description) {
            $ticket = Ticket::create([
                'title' => $title,
                'description' => $description,
                'status' => 'Open',
            ]);

            EnrichTicketJob::dispatch($ticket->id);

            return $ticket;
        });
    }

    /**
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Ticket::query()->latest()->paginate($perPage);
    }

    /**
     * @param Ticket $ticket
     * @return void
     */
    public function delete(Ticket $ticket): void
    {
        $ticketId = $ticket->id;

        $ticket->delete();

        Log::info('ticket.deleted', [
            'ticket_id' => $ticketId,
        ]);
    }
}
