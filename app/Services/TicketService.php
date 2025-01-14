<?php

namespace App\Services;

use App\Enums\Status;
use App\Interfaces\Ticket;
use App\Models\User;

class TicketService
{
    public static function assignTicket(Ticket $ticket, User $resolver): void
    {
        $ticket->resolver_id = $resolver->id;
        $ticket->save();
    }

    static function resolveTicket(Ticket $ticket): void
    {
        $ticket->status = Status::RESOLVED;
        $ticket->save();
    }

    public static function cancelTicket(Ticket $ticket): void
    {
        $ticket->status = Status::CANCELLED;
        $ticket->save();
    }
}
