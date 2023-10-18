<?php

namespace App\View\Components;

use App\Models\Status;
use App\Models\Ticket;
use App\Models\TicketConfiguration;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;

class TicketFieldStatus extends Component
{
    public Ticket $ticket;
    public string $name;
    public string $selected;
    public Collection $statuses;
    public bool $required;
    public bool $disabled;
    public function __construct(Ticket $ticket){
        $this->ticket = $ticket;
        $this->name = 'status';
        $this->selected = $this->ticket->status->name;
        $this->statuses = Status::all();
        $this->required = true;
        $this->disabled = $this->isDisabled();
    }

    public function render(): View|Closure|string
    {
        return view('components.ticket-field-status');
    }

    public function isDisabled(): bool
    {
        if(auth()->user()->cannot('setStatus', $this->ticket)){
            return true;
        }
        if($this->ticket->isArchived()){
            return true;
        };

        return false;
    }
}
