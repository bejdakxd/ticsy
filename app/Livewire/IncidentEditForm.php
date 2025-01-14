<?php

namespace App\Livewire;

use App\Enums\OnHoldReason;
use App\Enums\Priority;
use App\Enums\Status;
use App\Enums\Tab;
use App\Helpers\Fields\Bar;
use App\Helpers\Fields\Fields;
use App\Helpers\Fields\Select;
use App\Helpers\Fields\TextArea;
use App\Helpers\Fields\TextInput;
use App\Models\Group;
use App\Models\Incident;
use App\Services\ActivityService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class IncidentEditForm extends EditForm
{
    public Incident $incident;
    public Status $status;
    public OnHoldReason|null $onHoldReason;
    public Priority $priority;
    public $group;
    public $resolver;
    public string $comment;

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(Status::class)],
            'onHoldReason' => [
                Rule::requiredIf($this->status == Status::ON_HOLD),
                Rule::enum(OnHoldReason::class),
                'nullable',
            ],
            'priority' => ['required', Rule::enum(Priority::class)],
            'group' => ['required', 'exists:App\Models\Group,id'],
            'resolver' => [
                Rule::in(
                    Group::find($this->group) ? Group::find($this->group)->getResolverIds() : []
                ),
                Rule::requiredIf($this->status == Status::IN_PROGRESS),
                'nullable',
            ],
            'comment' => [
                'max:255',
                Rule::requiredIf($this->status == Status::RESOLVED && $this->status != $this->incident->status),
                Rule::requiredIf($this->status == Status::CANCELLED && $this->status != $this->incident->status),
                Rule::requiredIf($this->status == Status::ON_HOLD && $this->status != $this->incident->status),
                Rule::requiredIf($this->priority != $this->incident->priority),
                'nullable',
            ],
        ];
    }

    public function mount(Incident $incident): void
    {
        $this->incident = $incident;
        $this->model = $incident;
        $this->setActivities();
        $this->status = $this->incident->status;
        $this->onHoldReason = $this->incident->on_hold_reason;
        $this->priority = $this->incident->priority;
        $this->group = $this->incident->group_id;
        $this->resolver = $this->incident->resolver_id;
        $this->comment = '';
    }

    public function updating($property, $value): void
    {
        if($property === 'status' && $value == Status::OPEN){
            $this->resolver = null;
        }
        if($property === 'priority'){
            $this->authorize('setPriority', Incident::class);
        }
    }

    public function updated($property): void
    {
        if($property === 'group'){
            $this->resolver = null;
        }
        if($property === 'status' && $this->status != Status::ON_HOLD){
            $this->onHoldReason = null;
        }

        parent::updated($property);
    }

    public function save()
    {
        $this->validate();
        $this->incident->status = $this->status;
        $this->incident->on_hold_reason = $this->onHoldReason ?? null;
        $this->incident->priority = $this->priority;
        $this->incident->group_id = $this->group;
        $this->incident->resolver_id = ($this->resolver === '') ? null : $this->resolver;
        $this->incident->save();

        if($this->comment !== ''){
            ActivityService::comment($this->incident, $this->comment);
        }

        Session::flash('success', 'You have successfully updated the incident');
        return redirect()->route('incidents.edit', $this->incident);
    }

    function schema(): Fields
    {
        return new Fields(
            TextInput::make('number')->value($this->incident->id),
            TextInput::make('caller')->value($this->incident->caller->name),
            TextInput::make('created')->label('Created at')->value($this->incident->created_at->format('d.m.Y h:i:s')),
            TextInput::make('updated')->label('Updated at')->value($this->incident->updated_at->format('d.m.Y h:i:s')),
            TextInput::make('category')->value($this->incident->category->name),
            TextInput::make('item')->value($this->incident->item->name),
            Select::make('status')->options(Status::class),
            Select::make('onHoldReason')->options(OnHoldReason::class)->hiddenIf($this->isFieldDisabled('onHoldReason'))->blank(),
            Select::make('priority')->options(Priority::class),
            Select::make('group')->options(Group::all()),
            Select::make('resolver')->options(Group::find($this->group) ? Group::find($this->group)->resolvers : [])->blank(),
            function () {
                if($this->incident->sla){
                    return Bar::make('sla')
                        ->label('SLA expires at')
                        ->percentage($this->incident->sla->toPercentage())
                        ->value($this->incident->sla->minutesTillExpires() . ' minutes')
                        ->pulse();
                } return null;
            },
            TextArea::make('description')->value($this->incident->description)->outsideGrid(),
            TextArea::make('comment')->label('Add a comment')->outsideGrid(),
        );
    }

    protected function isFieldDisabled(string $name): bool
    {
        if($this->incident->isArchived()){
            return true;
        }

        if($name == 'comment'){
            return auth()->user()->cannot('addComment', $this->incident);
        }

        if(auth()->user()->cannot('update', Incident::class)){
            return true;
        }

        return match($name){
            'number', 'caller', 'created', 'updated', 'category', 'item', 'description' => true,
            'onHoldReason' => $this->status != Status::ON_HOLD,
            'priority' => $this->status == Status::RESOLVED || !Auth::user()->hasPermissionTo('set_priority'),
            'group', 'resolver' => $this->status == Status::RESOLVED,
            default => false,
        };
    }

    function tabs(): array
    {
        return [];
    }
}
