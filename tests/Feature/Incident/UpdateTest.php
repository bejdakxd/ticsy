<?php

namespace Tests\Feature\Incident;

use App\Enums\OnHoldReason;
use App\Enums\Priority;
use App\Enums\Status;
use App\Livewire\IncidentEditForm;
use App\Models\Group;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Exceptions\PropertyNotFoundException;
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
        $incident = Incident::factory()->create();

        Livewire::actingAs($user)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('status', Status::IN_PROGRESS->value)
            ->assertForbidden();
    }

    public function test_resolver_can_set_status()
    {
        $resolver = User::factory()->resolverAllGroups()->create();
        $incident = Incident::factory()->create();

        Livewire::actingAs($resolver)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('status', Status::IN_PROGRESS->value)
            ->set('resolver', $resolver->id)
            ->call('save');

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'status' => Status::IN_PROGRESS,
        ]);
    }

    /**
     * @dataProvider invalidStatuses
     */
    public function test_it_throws_value_error_when_invalid_status_is_set($value)
    {
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory()->create();

        $this->expectException(ValueError::class);

        Livewire::actingAs($resolver)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('status', $value);
    }

    /**
     * @dataProvider invalidOnHoldReasons
     */
    public function test_it_throws_value_error_when_invalid_on_hold_reason_set($value)
    {
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory()->create();

        $this->expectException(ValueError::class);

        Livewire::actingAs($resolver)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('status', Status::ON_HOLD->value)
            ->set('onHoldReason', $value);
    }

    public function test_it_fails_validation_if_status_on_hold_and_on_hold_reason_is_null()
    {
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory()->create();

        Livewire::actingAs($resolver)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('status', Status::ON_HOLD->value)
            ->set('onHoldReason', '')
            ->call('save')
            ->assertHasErrors(['onHoldReason' => 'required']);
    }

    /**
     * @dataProvider nonNumericPriorities
     */
    public function test_it_throws_type_error_when_non_numeric_priority_is_set($value)
    {
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory()->create();

        $this->expectException(TypeError::class);

        Livewire::actingAs($resolver)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('priority', $value);
    }

    /**
     * @dataProvider invalidGroups
     */
    public function test_it_fails_validation_when_invalid_group_is_set($value, $error)
    {
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory()->create();

        Livewire::actingAs($resolver)
            ->test(IncidentEditForm::class, ['incident' => $incident])
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
        $incident = Incident::factory()->create();

        Livewire::actingAs($resolver)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('resolver', $value)
            ->call('save')
            ->assertHasErrors(['resolver' => $error]);
    }

    public function test_resolver_is_able_to_set_on_hold_reason()
    {
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory()->create();

        Livewire::actingAs($resolver)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('status', Status::ON_HOLD->value)
            ->set('onHoldReason', OnHoldReason::WAITING_FOR_VENDOR->value)
            ->set('comment', 'Test comment')
            ->call('save')
            ->assertSuccessful();

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'on_hold_reason' => OnHoldReason::WAITING_FOR_VENDOR,
        ]);
    }

    public function test_it_forbids_to_save_incident_if_status_on_hold_and_on_hold_reason_empty()
    {
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory()->create();

        Livewire::actingAs($resolver)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('status', Status::ON_HOLD->value)
            ->call('save')
            ->assertHasErrors(['onHoldReason' => 'required']);
    }

    public function test_non_resolver_user_cannot_set_resolver()
    {
        $user = User::factory()->create();
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory()->create();

        Livewire::actingAs($user)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('resolver', $resolver->id)
            ->assertForbidden();
    }

    public function test_resolver_user_can_set_resolver()
    {
        $user = User::factory()->resolver()->create();
        $group = Group::firstOrFail();
        $resolver = User::factory()->hasAttached($group)->create()->assignRole('resolver');
        $incident = Incident::factory()->create();

        Livewire::actingAs($user)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('resolver', $resolver->id)
            ->call('save');

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'resolver_id' => $resolver->id,
        ]);
    }

    function test_user_can_change_priority_with_permission()
    {
        $manager = User::factory()->manager()->create();
        $incident = Incident::factory(['priority' => 4])->create();

        Livewire::actingAs($manager)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('priority', 2)
            ->set('comment', 'Production issue')
            ->call('save');

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'priority' => 2,
        ]);
    }

    function test_user_cannot_change_priority_without_permission()
    {
        $user = User::factory()->create();
        $incident = Incident::factory(['priority' => 4])->create();

        Livewire::actingAs($user)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('priority', 2)
            ->assertForbidden();

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'priority' => 4,
        ]);
    }

    public function test_it_updates_incident_when_correct_data_submitted()
    {
        $group = Group::firstOrFail();
        $manager = User::factory()->managerAllGroups()->create();
        $incident = Incident::factory(['status' => Status::OPEN])->create();
        $status = Status::IN_PROGRESS;
        $priority = Priority::THREE;

        Livewire::actingAs($manager)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('status', $status->value)
            ->set('priority', $priority->value)
            ->set('comment', 'Production issue')
            ->set('group', $group->id)
            ->set('resolver', $manager->id)
            ->call('save');

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'priority' => $priority,
            'status' => $status->value,
            'group_id' => $group->id,
            'resolver_id' => $manager->id,
        ]);
    }

    public function test_incident_priority_cannot_be_changed_when_status_is_resolved(){
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory(['priority' => Incident::DEFAULT_PRIORITY])->statusResolved()->create();

        Livewire::actingAs($resolver)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('priority', Incident::DEFAULT_PRIORITY->value - 1)
            ->assertForbidden();

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'priority' => Incident::DEFAULT_PRIORITY,
        ]);
    }

    public function test_incident_status_can_be_changed_when_status_is_resolved(){
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory()->statusResolved()->create();

        Livewire::actingAs($resolver)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('status', Incident::DEFAULT_STATUS->value)
            ->call('save')
            ->assertSuccessful();

        $this->assertDatabaseHas('incidents', [
           'id' => $incident->id,
           'status' => Incident::DEFAULT_STATUS,
        ]);
    }

    public function test_incident_resolver_cannot_be_changed_when_status_is_resolved(){
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory([
            'status' => Status::RESOLVED,
            'resolver_id' => null,
        ])->create();

        Livewire::actingAs($resolver)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('resolver', $resolver->id)
            ->assertForbidden();

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'resolver_id' => null,
        ]);
    }

    public function test_incident_priority_cannot_be_changed_when_status_is_cancelled(){
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory()->statusCancelled()->create();

        Livewire::actingAs($resolver)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('priority', Incident::DEFAULT_PRIORITY->value - 1)
            ->assertForbidden();
    }

    public function test_incident_status_cannot_be_changed_when_status_is_cancelled(){
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory()->statusCancelled()->create();

        Livewire::actingAs($resolver)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('status', Incident::DEFAULT_STATUS->value)
            ->assertForbidden();

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'status' => Status::CANCELLED,
        ]);
    }

    public function test_incident_resolver_cannot_be_changed_when_status_is_cancelled(){
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory()->statusCancelled()->create();

        Livewire::actingAs($resolver)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('resolver', $resolver->id)
            ->assertForbidden();
    }

    public function test_resolver_field_lists_resolvers_based_on_selected_group()
    {
        $resolverOne = User::factory(['name' => 'John Doe'])->create()->assignRole('resolver');
        $resolverTwo = User::factory(['name' => 'Joe Rogan'])->create()->assignRole('resolver');
        $resolverThree = User::factory(['name' => 'Fred Flinstone'])->create()->assignRole('resolver');

        $groupOne = Group::factory()->create();
        $groupOne->resolvers()->attach($resolverOne);
        $groupOne->resolvers()->attach($resolverTwo);

        $groupTwo = Group::factory()->create();
        $groupTwo->resolvers()->attach($resolverThree);

        $incident = Incident::factory(['group_id' => $groupOne])->create();

        Livewire::actingAs($resolverOne)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('group', $groupOne->id)
            ->assertSee('John Doe')
            ->assertSee('Joe Rogan')
            ->assertDontSee('Fred Flinstone');

        Livewire::test(IncidentEditForm::class, ['incident' => $incident])
            ->set('group', $groupTwo->id)
            ->assertDontSee('John Doe')
            ->assertDontSee('Joe Rogan')
            ->assertSee('Fred Flinstone');
    }

    public function test_resolver_from_not_selected_group_cannot_be_assigned_to_the_incident_as_resolver()
    {
        $resolver = User::factory()->resolver()->create();
        $group = Group::factory()->create();
        $incident = Incident::factory()->create();

        Livewire::actingAs($resolver)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->assertSuccessful()
            ->set('group', $group->id)
            ->set('resolver', $resolver->id)
            ->call('save')
            ->assertHasErrors(['resolver' => 'in']);
    }

    public function test_selected_resolver_is_empty_when_resolver_group_changes()
    {
        $incident = Incident::factory()->create();
        $groupOne = $incident->group;
        $groupTwo = Group::factory()->create();
        $resolver = User::factory()->resolverAllGroups()->create();

        Livewire::actingAs($resolver)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('resolver', $resolver->id)
            ->call('save');

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'group_id' => $groupOne->id,
            'resolver_id' => $resolver->id,
        ]);

        Livewire::test(IncidentEditForm::class, ['incident' => $incident])
            ->set('group', $groupTwo->id)
            ->call('save');

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'group_id' => $groupTwo->id,
            'resolver_id' => null,
        ]);
    }

    public function test_comment_is_required_if_status_is_on_hold()
    {
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory()->create();

        Livewire::actingAs($resolver)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('status', Status::ON_HOLD->value)
            ->set('onHoldReason', OnHoldReason::CALLER_RESPONSE->value)
            ->call('save')
            ->assertHasErrors(['comment' => 'required']);
    }

    public function test_comment_is_required_if_status_is_resolved()
    {
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory()->create();

        Livewire::actingAs($resolver)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('status', Status::RESOLVED->value)
            ->call('save')
            ->assertHasErrors(['comment' => 'required']);
    }

    public function test_comment_is_required_if_status_is_cancelled()
    {
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory()->create();

        Livewire::actingAs($resolver)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('status', Status::CANCELLED->value)
            ->call('save')
            ->assertHasErrors(['comment' => 'required']);
    }

    public function test_resolver_can_add_comment(){
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory()->create();

        Livewire::actingAs($resolver);

        Livewire::test(IncidentEditForm::class, ['incident' => $incident])
            ->set('comment', 'Test comment')
            ->call('save');

        Livewire::test(IncidentEditForm::class, ['incident' => $incident])
            ->assertSee('Test comment');

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $incident->id,
            'causer_id' => $resolver->id,
            'event' => 'comment',
            'description' => 'Test comment'
        ]);
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

    static function nonNumericPriorities(){
        return [
            ['word'],
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
