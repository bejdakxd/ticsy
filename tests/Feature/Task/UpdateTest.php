<?php

namespace Tests\Feature\Task;

use App\Enums\OnHoldReason;
use App\Enums\Priority;
use App\Enums\Status;
use App\Livewire\TaskEditForm;
use App\Models\Group;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use TypeError;
use ValueError;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_resolver_user_cannot_set_status()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();

        Livewire::actingAs($user)
            ->test(TaskEditForm::class, ['task' => $task])
            ->set('status', Status::IN_PROGRESS->value)
            ->assertForbidden();
    }

    public function test_resolver_can_set_status()
    {
        $resolver = User::factory()->resolverAllGroups()->create();
        $task = Task::factory()->create();

        Livewire::actingAs($resolver)
            ->test(TaskEditForm::class, ['task' => $task])
            ->set('status', Status::IN_PROGRESS->value)
            ->set('resolver', $resolver->id)
            ->call('save');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => Status::IN_PROGRESS,
        ]);
    }

    /**
     * @dataProvider invalidStatuses
     */
    public function test_it_fails_validation_when_invalid_status_is_set($value)
    {
        $resolver = User::factory()->resolver()->create();
        $task = Task::factory()->create();

        $this->expectException(ValueError::class);

        Livewire::actingAs($resolver)
            ->test(TaskEditForm::class, ['task' => $task])
            ->set('status', $value);
    }

    /**
     * @dataProvider invalidOnHoldReasons
     */
    public function test_it_throws_value_error_when_invalid_on_hold_reason_set($value, $error)
    {
        $resolver = User::factory()->resolver()->create();
        $task = Task::factory()->create();

        $this->expectException(ValueError::class);

        Livewire::actingAs($resolver)
            ->test(TaskEditForm::class, ['task' => $task])
            ->set('status', Status::ON_HOLD->value)
            ->set('onHoldReason', $value);
    }

    public function test_it_fails_validation_if_status_on_hold_and_on_hold_reason_is_null()
    {
        $resolver = User::factory()->resolver()->create();
        $task = Task::factory()->create();

        Livewire::actingAs($resolver)
            ->test(TaskEditForm::class, ['task' => $task])
            ->set('status', Status::ON_HOLD->value)
            ->set('onHoldReason', '')
            ->call('save')
            ->assertHasErrors(['onHoldReason' => 'required']);
    }

    /**
     * @dataProvider invalidPriorities
     */
    public function test_it_throws_type_error_when_invalid_priority_is_set($value, $error)
    {
        $resolver = User::factory()->resolver()->create();
        $task = Task::factory()->create();

        $this->expectException(TypeError::class);

        Livewire::actingAs($resolver)
            ->test(TaskEditForm::class, ['task' => $task])
            ->set('priority', $value);
    }

    /**
     * @dataProvider invalidGroups
     */
    public function test_it_fails_validation_when_invalid_group_is_set($value, $error)
    {
        $resolver = User::factory()->resolver()->create();
        $task = Task::factory()->create();

        Livewire::actingAs($resolver)
            ->test(TaskEditForm::class, ['task' => $task])
            ->set('group', $value)
            ->call('save')
            ->assertHasErrors(['group' => $error]);
    }

    /**
     * @dataProvider invalidResolvers
     */
    public function test_it_fails_validation_if_invalid_resolver_is_set($value, $error)
    {
        $resolver = User::factory()->resolver()->create();
        $task = Task::factory()->create();

        Livewire::actingAs($resolver)
            ->test(TaskEditForm::class, ['task' => $task])
            ->set('resolver', $value)
            ->call('save')
            ->assertHasErrors(['resolver' => $error]);
    }

    public function test_resolver_is_able_to_set_on_hold_reason()
    {
        $resolver = User::factory()->resolver()->create();
        $task = Task::factory()->create();

        Livewire::actingAs($resolver)
            ->test(TaskEditForm::class, ['task' => $task])
            ->set('status', Status::ON_HOLD->value)
            ->set('onHoldReason', OnHoldReason::WAITING_FOR_VENDOR->value)
            ->set('comment', 'Test comment')
            ->call('save')
            ->assertSuccessful();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'on_hold_reason' => OnHoldReason::WAITING_FOR_VENDOR,
        ]);
    }

    public function test_it_forbids_to_save_ticket_if_status_on_hold_and_on_hold_reason_empty()
    {
        $resolver = User::factory()->resolver()->create();
        $task = Task::factory()->create();

        Livewire::actingAs($resolver)
            ->test(TaskEditForm::class, ['task' => $task])
            ->set('status', Status::ON_HOLD->value)
            ->call('save')
            ->assertHasErrors(['onHoldReason' => 'required']);
    }

    public function test_non_resolver_user_cannot_set_resolver()
    {
        $user = User::factory()->create();
        $resolver = User::factory()->resolver()->create();
        $task = Task::factory()->create();

        Livewire::actingAs($user)
            ->test(TaskEditForm::class, ['task' => $task])
            ->set('resolver', $resolver->id)
            ->assertForbidden();
    }

    public function test_resolver_user_can_set_resolver()
    {
        $resolver = User::factory()->resolverAllGroups()->create();
        $task = Task::factory()->create();

        Livewire::actingAs($resolver)
            ->test(TaskEditForm::class, ['task' => $task])
            ->set('resolver', $resolver->id)
            ->call('save');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'resolver_id' => $resolver->id,
        ]);
    }

    function test_user_can_change_priority_with_permission()
    {
        $manager = User::factory()->manager()->create();
        $task = Task::factory(['priority' => 4])->create();

        Livewire::actingAs($manager)
            ->test(TaskEditForm::class, ['task' => $task])
            ->set('priority', 2)
            ->set('comment', 'Production issue')
            ->call('save');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'priority' => 2,
        ]);
    }

    function test_user_cannot_change_priority_without_permission()
    {
        $user = User::factory()->create();
        $task = Task::factory(['priority' => 4])->create();

        Livewire::actingAs($user)
            ->test(TaskEditForm::class, ['task' => $task])
            ->set('priority', 2)
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'priority' => 4,
        ]);
    }

    public function test_it_updates_task_when_correct_data_submitted()
    {
        $group = Group::firstOrFail();
        $manager = User::factory()->managerAllGroups()->create();
        $task = Task::factory()->create();
        $status = Status::IN_PROGRESS;
        $priority = Priority::THREE;

        Livewire::actingAs($manager)
            ->test(TaskEditForm::class, ['task' => $task])
            ->set('status', $status->value)
            ->set('priority', $priority->value)
            ->set('comment', 'Production issue')
            ->set('group', $group->id)
            ->set('resolver', $manager->id)
            ->call('save');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'priority' => $priority,
            'status' => $status->value,
            'group_id' => $group->id,
            'resolver_id' => $manager->id,
        ]);
    }

    public function test_task_priority_cannot_be_changed_when_status_is_resolved(){
        $resolver = User::factory()->resolver()->create();
        $task = Task::factory(['priority' => Priority::FOUR])->statusResolved()->create();

        Livewire::actingAs($resolver)
            ->test(TaskEditForm::class, ['task' => $task])
            ->set('priority', Priority::THREE->value)
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'priority' => Priority::FOUR,
        ]);
    }

    public function test_task_status_can_not_be_changed_when_status_is_resolved(){
        $resolver = User::factory()->resolver()->create();
        $task = Task::factory()->statusResolved()->create();

        Livewire::actingAs($resolver)
            ->test(TaskEditForm::class, ['task' => $task])
            ->set('status', Task::DEFAULT_STATUS->value)
            ->assertForbidden();
    }

    public function test_task_resolver_cannot_be_changed_when_status_is_closed(){
        $resolver = User::factory()->resolver()->create();
        $task = Task::factory([
            'status' => Status::RESOLVED,
            'resolver_id' => null,
        ])->create();

        Livewire::actingAs($resolver)
            ->test(TaskEditForm::class, ['task' => $task])
            ->set('resolver', $resolver->id)
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'resolver_id' => null,
        ]);
    }

    public function test_task_priority_cannot_be_changed_when_status_is_cancelled(){
        $resolver = User::factory()->resolver()->create();
        $task = Task::factory(['priority' => Priority::THREE])->statusCancelled()->create();

        Livewire::actingAs($resolver)
            ->test(TaskEditForm::class, ['task' => $task])
            ->set('priority', Priority::TWO->value)
            ->assertForbidden();
    }

    public function test_task_status_cannot_be_changed_when_status_is_cancelled(){
        $resolver = User::factory()->resolver()->create();
        $task = Task::factory()->statusCancelled()->create();

        Livewire::actingAs($resolver)
            ->test(TaskEditForm::class, ['task' => $task])
            ->set('status', Task::DEFAULT_STATUS->value)
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => Status::CANCELLED,
        ]);
    }

    public function test_task_resolver_cannot_be_changed_when_status_is_cancelled(){
        $resolver = User::factory()->resolver()->create();
        $task = Task::factory()->statusCancelled()->create();

        Livewire::actingAs($resolver)
            ->test(TaskEditForm::class, ['task' => $task])
            ->set('resolver', $resolver->id)
            ->assertForbidden();
    }

    public function test_resolver_field_lists_resolvers_based_on_selected_group()
    {
        $resolverOne = User::factory(['name' => 'John Doe'])->resolver()->create();
        $resolverTwo = User::factory(['name' => 'Joe Rogan'])->resolver()->create();
        $resolverThree = User::factory(['name' => 'Fred Flinstone'])->resolver()->create();

        $groupOne = Group::factory()->create();
        $groupOne->resolvers()->attach($resolverOne);
        $groupOne->resolvers()->attach($resolverTwo);

        $groupTwo = Group::factory()->create();
        $groupTwo->resolvers()->attach($resolverThree);

        $task = Task::factory(['group_id' => $groupOne])->create();

        Livewire::actingAs($resolverOne)
            ->test(TaskEditForm::class, ['task' => $task])
            ->set('group', $groupOne->id)
            ->assertSee('John Doe')
            ->assertSee('Joe Rogan')
            ->assertDontSee('Fred Flinstone');

        Livewire::test(TaskEditForm::class, ['task' => $task])
            ->set('group', $groupTwo->id)
            ->assertDontSee('John Doe')
            ->assertDontSee('Joe Rogan')
            ->assertSee('Fred Flinstone');
    }

    public function test_resolver_from_not_selected_group_cannot_be_assigned_to_the_task_as_resolver()
    {
        $resolver = User::factory()->resolver()->create();
        $group = Group::factory()->create();
        $task = Task::factory()->create();

        Livewire::actingAs($resolver)
            ->test(TaskEditForm::class, ['task' => $task])
            ->assertSuccessful()
            ->set('group', $group->id)
            ->set('resolver', $resolver->id)
            ->call('save')
            ->assertHasErrors(['resolver' => 'in']);
    }

    public function test_selected_resolver_is_empty_when_resolver_group_changes()
    {
        $groupOne = Group::factory()->create();
        $groupTwo = Group::factory()->create();
        $resolver = User::factory()->resolverAllGroups()->create();
        $task = Task::factory(['group_id' => $groupOne])->create();

        Livewire::actingAs($resolver)
            ->test(TaskEditForm::class, ['task' => $task])
            ->set('resolver', $resolver->id)
            ->call('save');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'group_id' => $groupOne->id,
            'resolver_id' => $resolver->id,
        ]);

        Livewire::test(TaskEditForm::class, ['task' => $task])
            ->set('group', $groupTwo->id)
            ->call('save');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'group_id' => $groupTwo->id,
            'resolver_id' => null,
        ]);
    }

    /** @test */
    function comment_is_required_if_status_is_on_hold()
    {
        $resolver = User::factory()->resolver()->create();
        $task = Task::factory()->create();

        Livewire::actingAs($resolver)
            ->test(TaskEditForm::class, ['task' => $task])
            ->set('status', Status::ON_HOLD->value)
            ->set('onHoldReason', OnHoldReason::CALLER_RESPONSE->value)
            ->call('save')
            ->assertHasErrors(['comment' => 'required']);
    }

    /** @test */
    function comment_is_required_if_status_is_resolved()
    {
        $resolver = User::factory()->resolver()->create();
        $task = Task::factory()->create();

        Livewire::actingAs($resolver)
            ->test(TaskEditForm::class, ['task' => $task])
            ->set('status', Status::RESOLVED->value)
            ->call('save')
            ->assertHasErrors(['comment' => 'required']);
    }

    /** @test */
    function comment_is_required_if_status_is_cancelled()
    {
        $resolver = User::factory()->resolver()->create();
        $task = Task::factory()->create();

        Livewire::actingAs($resolver)
            ->test(TaskEditForm::class, ['task' => $task])
            ->set('status', Status::CANCELLED->value)
            ->call('save')
            ->assertHasErrors(['comment' => 'required']);
    }

    static function invalidStatuses(){
        return [
            ['word'],
        ];
    }

    static function invalidOnHoldReasons(){
        return [
            ['word', 'in'],
        ];
    }

    static function invalidPriorities(){
        return [
            ['word', 'in'],
        ];
    }

    static function invalidGroups(){
        return [
            ['word', 'exists'],
            ['', 'required'],
        ];
    }

    static function invalidResolvers(){
        return [
            ['word', 'in'],
        ];
    }
}
