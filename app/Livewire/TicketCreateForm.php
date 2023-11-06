<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Group;
use App\Models\Ticket;
use App\Models\TicketConfig;
use App\Models\Type;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class TicketCreateForm extends TicketForm
{
    public Ticket|null $ticket = null;
    public Type $type;
    public $category;
    public $item;
    public $description;
    public Collection $categories;
    public Collection $items;

    public function rules()
    {
        $min_desc = TicketConfig::MIN_DESCRIPTION_CHARS;
        $max_desc = TicketConfig::MAX_DESCRIPTION_CHARS;

        return [
            'category' => 'numeric|required|min:1|max:' . count(TicketConfig::CATEGORIES),
            'item' => 'numeric|required|min:1|max:' . count(TicketConfig::ITEMS),
            'description' => 'string|required|min:' . $min_desc . '|max:' . $max_desc,
        ];
    }

    public function mount(Type $type, Ticket|null $ticket = null)
    {
        $this->ticket = $ticket;
        $this->type = $type;
        $this->category = null;
        $this->item = null;
        $this->description = null;
        $this->categories = Category::all();
        $this->items = collect([]);
    }

    public function updated($property): void
    {
        $this->validateOnly('category');
        $this->items = $this->category ? Category::findOrFail($this->category)->items()->get() : collect([]);
    }

    public function render()
    {
        return view('livewire.ticket-create-form');
    }

    public function create()
    {
        $min_desc = TicketConfig::MIN_DESCRIPTION_CHARS;
        $max_desc = TicketConfig::MAX_DESCRIPTION_CHARS;

        $this->validate();

        $ticket = new Ticket();
        $ticket->user_id = Auth::user()->id;
        $ticket->type_id = $this->type->id;
        $ticket->category_id = $this->category;
        $ticket->item_id = $this->item;
        $ticket->description = $this->description;
        $ticket->save();

        Session::flash('success', 'You have successfully created a ticket');
        return redirect()->route('tickets.edit', $ticket);
    }
}