<?php

namespace App\Models;

use App\Enums\Status;
use App\Enums\OnHoldReason;
use App\Enums\TaskSequence;
use App\Helpers\TaskPlan;
use App\Interfaces\Activitable;
use App\Interfaces\Slable;
use App\Interfaces\Taskable;
use App\Interfaces\Ticket;
use App\Models\Request\RequestCategory;
use App\Models\Request\RequestItem;
use App\Traits\HasSla;
use App\Traits\TicketTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Request extends Model implements Ticket, Slable, Activitable, Taskable
{
    use HasSla, HasFactory, TicketTrait;

    protected $guarded = [];
    protected $casts = [
        'status' => Status::class,
        'on_hold_reason' => OnHoldReason::class,
        'resolved_at' => 'datetime',
        'task_sequence' => TaskSequence::class,
    ];
    protected $attributes = [
        'status' => self::DEFAULT_STATUS,
        'group_id' => self::DEFAULT_GROUP,
        'priority' => self::DEFAULT_PRIORITY,
    ];

    const PRIORITY_TO_SLA_MINUTES = [
        1 => 30,
        2 => 2 * 60,
        3 => 12 * 60,
        4 => 24 * 60,
    ];

    const CATEGORY_TO_ITEM = [
        [RequestCategory::COMPUTER, RequestItem::BACKUP],
        [RequestCategory::COMPUTER, RequestItem::CONFIGURE],
        [RequestCategory::SERVER, RequestItem::ACCESS],
        [RequestCategory::SERVER, RequestItem::MAINTENANCE],
        [RequestCategory::SERVER, RequestItem::CONFIGURE],
    ];

    function category(): BelongsTo
    {
        return $this->belongsTo(RequestCategory::class, 'category_id');
    }

    function item(): BelongsTo
    {
        return $this->belongsTo(RequestItem::class, 'item_id');
    }

    function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    function taskPlan(): TaskPlan
    {
        return $this->initializeTaskPlan($this->category_id, $this->item->id);
    }

    function hasNonStartedTask(): bool
    {
        return count(($this->tasks()->notStarted()->get())) > 0;
    }

    function hasAllTasksClosed(): bool
    {
        return count($this->tasks()->notClosed()->get()) == 0;
    }

    protected function initializeTaskPlan($category, $item): TaskPlan
    {
        $taskPlan = new TaskPlan();

        if($category == RequestCategory::COMPUTER){
            if($item == RequestItem::BACKUP){
                $taskPlan
                    ->addTask('Backup computer of user '. $this->caller->name .'. ')
                    ->addTask('Verify if the backup from previous task is restorable.');
            }
        } elseif($category == RequestCategory::SERVER){
            if($item == RequestItem::ACCESS){
                $taskPlan
                    ->addTask('Verify if '. $this->caller->name . ' is eligible for access to mentioned server.')
                    ->addTask('Give the access to the user')
                    ->addTask('Verify with '. $this->caller->name .', that the access works.');
            } elseif($item == RequestItem::MAINTENANCE){
                $taskPlan
                    ->setSequence(TaskSequence::AT_ONCE)
                    ->addTask('Restart database.')
                    ->addTask('Restart respective services.');
            }
        }

        if(count($taskPlan->tasks) == 0){
            $taskPlan->addTask($this->description);
        }

        return $taskPlan;
    }

}
