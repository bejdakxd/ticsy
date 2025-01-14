<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Incident\IncidentCategory;
use Illuminate\Support\Facades\Auth;

class IncidentsController extends Controller
{
    const DEFAULT_INDEX_PAGINATION = 10;

    public function index()
    {
        $user = Auth::user();
        $incidents = $user->incidents()
            ->with(['category', 'caller', 'resolver'])
            ->orderByDesc('id')
            ->simplePaginate(self::DEFAULT_INDEX_PAGINATION);

        return view('incidents.index', ['incidents' => $incidents]);
    }

    public function create()
    {
        return view('incidents.create');
    }

    public function edit($id)
    {
        $incident = Incident::findOrFail($id);

        $this->authorize('edit', $incident);

        return view('incidents.edit', [
            'incident' => $incident,
        ]);
    }
}
