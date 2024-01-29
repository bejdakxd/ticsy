<?php

namespace App\Livewire\Tables;

use App\Helpers\Table\TableBuilder;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;

class TasksTable extends \App\Livewire\Table
{
    function query(): Builder
    {
        return Task::query()->with('caller');
    }

    function schema(): TableBuilder
    {
        return $this->tableBuilder()
            ->column('Number', 'id', ['tasks.edit', 'id'])
            ->column('Caller', 'caller.name')
            ->column('Resolver', 'resolver.name')
            ->column('Status', 'status.value')
            ->column('Priority', 'priority.value');
    }
}