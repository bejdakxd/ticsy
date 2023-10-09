<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Incident;
use App\Models\Resolver;
use App\Models\Ticket;
use App\Models\TicketConfiguration;
use App\Models\Type;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class TicketsController extends Controller
{
    const DEFAULT_PAGINATION = 10;

    public function index()
    {
        $user = Auth::user();
        $tickets = $user->tickets()
            ->with(['category', 'user', 'resolver'])
            ->latest()
            ->simplePaginate(self::DEFAULT_PAGINATION);

        return view('tickets.index', ['tickets' => $tickets]);
    }

    public function create($type = null)
    {
        $type = ($type === null) ? Type::find(TicketConfiguration::DEFAULT_TYPE) : Type::where('name', '=', $type)->firstOrFail();

        $formType = ucfirst('create');
        $action = route('tickets.store');
        $priorities = array_reverse(TicketConfiguration::PRIORITIES);

        return view('tickets.create', [
            'type' => $type,
            'formType' => $formType,
            'categories' => Category::all(),
            'priorities' => $priorities,
            'default_priority' => TicketConfiguration::DEFAULT_PRIORITY,
            'action' => $action,
        ]);
    }

    public function store(Request $request)
    {
        $min_desc = TicketConfiguration::MINIMUM_DESCRIPTION_CHARACTERS;
        $max_desc = TicketConfiguration::MAXIMUM_DESCRIPTION_CHARACTERS;

        $request->validate([
            'type' => 'numeric|required|min:1|max:' . count(TicketConfiguration::TYPES),
            'category' => 'numeric|required|min:1|max:' . count(TicketConfiguration::CATEGORIES),
            'description' => 'string|required|min:' . $min_desc . '|max:' . $max_desc,
        ]);

        $ticket = new Ticket();
        $ticket->user_id = Auth::user()->id;
        $ticket->type_id = $request['type'];
        $ticket->category_id = $request['category'];
        $ticket->description = $request['description'];
        $ticket->save();

        Session::flash('success', 'You have successfully created a ticket');
        return redirect()->route('tickets.edit', $ticket);
    }

    public function edit($id)
    {
        $ticket = Ticket::findOrFail($id);

        $this->authorize('edit', $ticket);

        $formType = ucfirst('edit');
        $action = route('tickets.update', ['ticket' => $ticket]);

        return view('tickets.edit', [
            'ticket' => $ticket,
            'formType' => $formType,
            'categories' => Category::all(),
            'priorities' => TicketConfiguration::PRIORITIES,
            'resolvers' => User::role('resolver')->get(),
            'action' => $action,
        ]);
    }

    public function setPriority($id, Request $request)
    {
        $ticket = Ticket::findOrFail($id);

        $this->authorize('setPriority', $ticket);

        $ticket->priority = $request['priority'];
        $ticket->save();

        Session::flash('success', 'You have successfully changed the priority');
        return redirect()->route('tickets.edit', $ticket);
    }

    public function setResolver($id, Request $request)
    {
        $ticket = Ticket::findOrFail($id);

        $this->authorize('setResolver', $ticket);

        $ticket->resolver_id = $request['resolver'];
        $ticket->save();

        Session::flash('success', 'You have successfully changed the resolver');
        return redirect()->route('tickets.edit', $ticket);
    }

}
