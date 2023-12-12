<?php

namespace App\Livewire;

use App\Helpers\Fieldable;
use App\Models\Category;
use App\Models\Group;
use App\Models\Item;
use App\Models\Ticket;
use App\Models\TicketConfig;
use App\Models\Type;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class TicketCreateForm extends Form
{
    public Type|int $type;
    public string $typeName;
    public $category;
    public $item;
    public $description;
    public Collection $categories;
    public Collection $items;

    public function rules()
    {
        return [
            'category' => 'numeric|required',
            'item' => 'numeric|required',
            'description' => 'string|required',
        ];
    }

    public function mount(int $type = Type::DEFAULT)
    {
        $this->type = Type::findOrFail($type);
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
        $this->validate();

        $ticket = new Ticket();
        $ticket->caller_id = Auth::user()->id;
        $ticket->type_id = $this->type->id;
        $ticket->category_id = $this->category;
        $ticket->item_id = $this->item;
        $ticket->description = $this->description;
        $ticket->save();

        Session::flash('success', 'You have successfully created a ticket');
        return redirect()->route('tickets.edit', $ticket);
    }
}
