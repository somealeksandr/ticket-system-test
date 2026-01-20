<?php

namespace App\Services\Tickets;

use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class TicketService
{
    public function create(string $title, string $description): Ticket
    {
        return DB::transaction(function () use ($title, $description) {
            return Ticket::create([
                'title' => $title,
                'description' => $description,
                'status' => 'Open',
            ]);
        });
    }
}
