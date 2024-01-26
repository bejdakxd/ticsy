<?php

namespace App\Livewire\Tables;

use App\Enums\SortOrder;
use App\Helpers\Table\Table;
use App\Models\Task;

class TasksTable extends \App\Livewire\Table
{
    public string $columnToSortBy = 'id';
    public SortOrder $sortOrder = SortOrder::DESCENDING;

    function table(): Table
    {
        return Table::make(Task::query()->with('caller'))
            ->sortByColumn($this->columnToSortBy)
            ->sortOrder($this->sortOrder)
            ->column('Number', 'id', ['tasks.edit', 'id'])
            ->column('Caller', 'caller.name')
            ->column('Resolver', 'resolver.name')
            ->column('Status', 'status.value')
            ->column('Priority', 'priority.value')
            ->get();
    }
}
