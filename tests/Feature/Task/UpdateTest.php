<?php

namespace Tests\Feature\Request;

use App\Livewire\RequestEditForm;
use App\Models\Group;
use App\Models\Request;
use App\Models\OnHoldReason;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_resolver_user_cannot_set_status()
    {
        $user = User::factory()->create();
        $request = Request::factory()->create();

        Livewire::actingAs($user)
            ->test(RequestEditForm::class, ['request' => $request])
            ->set('status', Status::IN_PROGRESS)
            ->assertForbidden();
    }

    public function test_resolver_can_set_status()
    {
        $resolver = User::factory()->resolver()->create();
        $request = Request::factory()->create();

        Livewire::actingAs($resolver)
            ->test(RequestEditForm::class, ['request' => $request])
            ->set('status', Status::IN_PROGRESS)
            ->call('save');

        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'status_id' => Status::IN_PROGRESS,
        ]);
    }

    /**
     * @dataProvider invalidStatuses
     */
    public function test_it_fails_validation_when_invalid_status_is_set($value, $error)
    {
        $resolver = User::factory()->resolver()->create();
        $request = Request::factory()->create();

        Livewire::actingAs($resolver)
            ->test(RequestEditForm::class, ['request' => $request])
            ->set('status', $value)
            ->call('save')
            ->assertHasErrors(['status' => $error]);
    }

    /**
     * @dataProvider invalidOnHoldReasons
     */
    public function test_it_fails_validation_when_invalid_on_hold_reason_set($value, $error)
    {
        $resolver = User::factory()->resolver()->create();
        $request = Request::factory()->create();

        Livewire::actingAs($resolver)
            ->test(RequestEditForm::class, ['request' => $request])
            ->set('status', Status::ON_HOLD)
            ->set('onHoldReason', $value)
            ->call('save')
            ->assertHasErrors(['onHoldReason' => $error]);
    }

    public function test_it_fails_validation_if_status_on_hold_and_on_hold_reason_is_null()
    {
        $resolver = User::factory()->resolver()->create();
        $request = Request::factory()->create();

        Livewire::actingAs($resolver)
            ->test(RequestEditForm::class, ['request' => $request])
            ->set('status', Status::ON_HOLD)
            ->set('onHoldReason', '')
            ->call('save')
            ->assertHasErrors(['onHoldReason' => 'required_if']);
    }

    /**
     * @dataProvider invalidPriorities
     */
    public function test_it_fails_validation_when_invalid_priority_is_set($value, $error)
    {
        $resolver = User::factory()->resolver()->create();
        $request = Request::factory()->create();

        Livewire::actingAs($resolver)
            ->test(RequestEditForm::class, ['request' => $request])
            ->set('priority', $value)
            ->call('save')
            ->assertHasErrors(['priority' => $error]);
    }

    /**
     * @dataProvider invalidGroups
     */
    public function test_it_fails_validation_when_invalid_group_is_set($value, $error)
    {
        $resolver = User::factory()->resolver()->create();
        $request = Request::factory()->create();

        Livewire::actingAs($resolver)
            ->test(RequestEditForm::class, ['request' => $request])
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
        $request = Request::factory()->create();

        Livewire::actingAs($resolver)
            ->test(RequestEditForm::class, ['request' => $request])
            ->set('resolver', $value)
            ->call('save')
            ->assertHasErrors(['resolver' => $error]);
    }

    public function test_resolver_is_able_to_set_on_hold_reason()
    {
        $resolver = User::factory()->resolver()->create();
        $request = Request::factory()->create();

        Livewire::actingAs($resolver)
            ->test(RequestEditForm::class, ['request' => $request])
            ->set('status', Status::ON_HOLD)
            ->set('onHoldReason', OnHoldReason::WAITING_FOR_VENDOR)
            ->call('save')
            ->assertSuccessful();

        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'on_hold_reason_id' => OnHoldReason::WAITING_FOR_VENDOR,
        ]);
    }

    public function test_it_forbids_to_save_ticket_if_status_on_hold_and_on_hold_reason_empty()
    {
        $resolver = User::factory()->resolver()->create();
        $request = Request::factory()->create();

        Livewire::actingAs($resolver)
            ->test(RequestEditForm::class, ['request' => $request])
            ->set('status', Status::ON_HOLD)
            ->call('save')
            ->assertHasErrors(['onHoldReason' => 'required_if:status,' . Status::ON_HOLD]);
    }

    public function test_non_resolver_user_cannot_set_resolver()
    {
        $user = User::factory()->create();
        $resolver = User::factory()->resolver()->create();
        $request = Request::factory()->create();

        Livewire::actingAs($user)
            ->test(RequestEditForm::class, ['request' => $request])
            ->set('resolver', $resolver->id)
            ->assertForbidden();
    }

    public function test_resolver_user_can_set_resolver()
    {
        $resolver = User::factory()->resolverAllGroups()->create();
        $request = Request::factory()->create();

        Livewire::actingAs($resolver)
            ->test(RequestEditForm::class, ['request' => $request])
            ->set('resolver', $resolver->id)
            ->call('save');

        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'resolver_id' => $resolver->id,
        ]);
    }

    function test_user_can_change_priority_with_permission()
    {
        $resolver = User::factory()->resolver()->create();
        $request = Request::factory(['priority' => 4])->create();

        Livewire::actingAs($resolver)
            ->test(RequestEditForm::class, ['request' => $request])
            ->set('priority', 2)
            ->set('priorityChangeReason', 'Production issue')
            ->call('save');

        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'priority' => 2,
        ]);
    }

    function test_user_cannot_change_priority_without_permission()
    {
        $user = User::factory()->create();
        $request = Request::factory(['priority' => 4])->create();

        Livewire::actingAs($user)
            ->test(RequestEditForm::class, ['request' => $request])
            ->set('priority', 2)
            ->assertForbidden();

        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'priority' => 4,
        ]);
    }

    public function test_it_updates_request_when_correct_data_submitted()
    {
        $group = Group::firstOrFail();
        $resolver = User::factory()->resolverAllGroups()->create();
        $request = Request::factory()->create();
        $status = Status::findOrFail(Status::IN_PROGRESS);
        $priority = Request::DEFAULT_PRIORITY - 1;

        Livewire::actingAs($resolver)
            ->test(RequestEditForm::class, ['request' => $request])
            ->set('status', $status->id)
            ->set('priority', $priority)
            ->set('priorityChangeReason', 'Production issue')
            ->set('group', $group->id)
            ->set('resolver', $resolver->id)
            ->call('save');

        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'priority' => $priority,
            'status_id' => $status->id,
            'group_id' => $group->id,
            'resolver_id' => $resolver->id,
        ]);
    }

    public function test_request_priority_cannot_be_changed_when_status_is_closed(){
        $resolver = User::factory()->resolver()->create();
        $request = Request::factory()->statusResolved()->create();

        Livewire::actingAs($resolver)
            ->test(RequestEditForm::class, ['request' => $request])
            ->set('priority', Request::DEFAULT_PRIORITY - 1)
            ->assertForbidden();

        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'priority' => Request::DEFAULT_PRIORITY,
        ]);
    }

    public function test_request_status_can_be_changed_when_status_is_closed(){
        $resolver = User::factory()->resolver()->create();
        $request = Request::factory()->statusResolved()->create();

        Livewire::actingAs($resolver)
            ->test(RequestEditForm::class, ['request' => $request])
            ->set('status', Request::DEFAULT_STATUS)
            ->call('save')
            ->assertSuccessful();

        $this->assertDatabaseHas('requests', [
           'id' => $request->id,
           'status_id' => Request::DEFAULT_STATUS,
        ]);
    }

    public function test_request_resolver_cannot_be_changed_when_status_is_closed(){
        $resolver = User::factory()->resolver()->create();
        $request = Request::factory([
            'status_id' => Status::RESOLVED,
            'resolver_id' => null,
        ])->create();

        Livewire::actingAs($resolver)
            ->test(RequestEditForm::class, ['request' => $request])
            ->set('resolver', $resolver->id)
            ->assertForbidden();

        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'resolver_id' => null,
        ]);
    }

    public function test_request_priority_cannot_be_changed_when_status_is_cancelled(){
        $resolver = User::factory()->resolver()->create();
        $request = Request::factory()->statusCancelled()->create();

        Livewire::actingAs($resolver)
            ->test(RequestEditForm::class, ['request' => $request])
            ->set('priority', Request::DEFAULT_PRIORITY - 1)
            ->assertForbidden();
    }

    public function test_request_status_cannot_be_changed_when_status_is_cancelled(){
        $resolver = User::factory()->resolver()->create();
        $request = Request::factory()->statusCancelled()->create();

        Livewire::actingAs($resolver)
            ->test(RequestEditForm::class, ['request' => $request])
            ->set('status', Request::DEFAULT_STATUS)
            ->assertForbidden();

        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'status_id' => Status::CANCELLED,
        ]);
    }

    public function test_request_resolver_cannot_be_changed_when_status_is_cancelled(){
        $resolver = User::factory()->resolver()->create();
        $request = Request::factory()->statusCancelled()->create();

        Livewire::actingAs($resolver)
            ->test(RequestEditForm::class, ['request' => $request])
            ->set('resolver', $resolver->id)
            ->assertForbidden();
    }

    public function test_resolver_field_lists_resolvers_based_on_selected_group()
    {
        $resolverOne = User::factory(['name' => 'John Doe'])->resolver()->create();
        $resolverTwo = User::factory(['name' => 'Joe Rogan'])->resolver()->create();
        $resolverThree = User::factory(['name' => 'Fred Flinstone'])->resolver()->create();

        $groupOne = Group::findOrFail(Group::SERVICE_DESK);
        $groupOne->resolvers()->attach($resolverOne);
        $groupOne->resolvers()->attach($resolverTwo);

        $groupTwo = Group::findOrFail(Group::LOCAL_6445_NEW_YORK);
        $groupTwo->resolvers()->attach($resolverThree);

        $request = Request::factory(['group_id' => $groupOne])->create();

        Livewire::actingAs($resolverOne)
            ->test(RequestEditForm::class, ['request' => $request])
            ->set('group', $groupOne->id)
            ->assertSee('John Doe')
            ->assertSee('Joe Rogan')
            ->assertDontSee('Fred Flinstone');

        Livewire::test(RequestEditForm::class, ['request' => $request])
            ->set('group', $groupTwo->id)
            ->assertDontSee('John Doe')
            ->assertDontSee('Joe Rogan')
            ->assertSee('Fred Flinstone');
    }

    public function test_resolver_from_not_selected_group_cannot_be_assigned_to_the_request_as_resolver()
    {
        $resolver = User::factory()->resolver()->create();
        $group = Group::findOrFail(Group::LOCAL_6445_NEW_YORK);
        $request = Request::factory()->create();

        Livewire::actingAs($resolver)
            ->test(RequestEditForm::class, ['request' => $request])
            ->assertSuccessful()
            ->set('group', $group->id)
            ->set('resolver', $resolver->id)
            ->assertForbidden();
    }

    public function test_selected_resolver_is_empty_when_resolver_group_changes()
    {
        $resolver = User::factory()->resolverAllGroups()->create();

        $groupOne = Group::findOrFail(Group::SERVICE_DESK);
        $groupTwo = Group::findOrFail(Group::LOCAL_6445_NEW_YORK);

        $request = Request::factory(['group_id' => $groupOne])->create();

        Livewire::actingAs($resolver)
            ->test(RequestEditForm::class, ['request' => $request])
            ->set('resolver', $resolver->id)
            ->call('save');

        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'group_id' => $groupOne->id,
            'resolver_id' => $resolver->id,
        ]);

        Livewire::test(RequestEditForm::class, ['request' => $request])
            ->set('group', $groupTwo->id)
            ->call('save');

        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'group_id' => $groupTwo->id,
            'resolver_id' => null,
        ]);
    }

    static function invalidStatuses(){
        return [
            ['word', 'numeric'],
            ['', 'required'],
        ];
    }

    static function invalidOnHoldReasons(){
        return [
            ['word', 'numeric'],
        ];
    }

    static function invalidPriorities(){
        return [
            ['word', 'numeric'],
            ['', 'required'],
        ];
    }

    static function invalidGroups(){
        return [
            ['word', 'numeric'],
            ['', 'required'],
        ];
    }

    static function invalidResolvers(){
        return [
            ['word', 'numeric'],
        ];
    }
}
