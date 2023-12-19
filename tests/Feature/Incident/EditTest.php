<?php


namespace Tests\Feature\Incident;

use App\Livewire\Activities;
use App\Livewire\IncidentEditForm;
use App\Models\Comment;
use App\Models\Group;
use App\Models\Incident\Incident;
use App\Models\Incident\IncidentCategory;
use App\Models\Incident\IncidentItem;
use App\Models\Incident\IncidentOnHoldReason;
use App\Models\Status;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class EditTest extends TestCase
{
    use RefreshDatabase;
    function test_it_redirect_guests_to_login_page()
    {
        $response = $this->get(route('incidents.edit', 1));

        $response->assertRedirectToRoute('login');
    }

    function test_it_errors_to_403_to_unauthorized_users()
    {
        $incident = Incident::factory()->create();

        $this->actingAs(User::factory()->create());
        $response = $this->get(route('incidents.edit', $incident));

        $response->assertForbidden();
    }

    function test_it_authorizes_caller_to_view(){
        $user = User::factory()->create();
        $incident = Incident::factory(['caller_id' => $user])->create();

        $this->actingAs($user);
        $response = $this->get(route('incidents.edit', $incident));
        $response->assertSuccessful();
    }

    function test_it_authorizes_resolver_to_view(){
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory()->create();

        $this->actingAs($resolver);
        $response = $this->get(route('incidents.edit', $incident));
        $response->assertSuccessful();
    }

    public function test_it_displays_incident_data()
    {
        $category = IncidentCategory::firstOrFail();
        $item = IncidentItem::firstOrFail();
        $group = Group::firstOrFail();
        $status = Status::firstOrFail();

        $resolver = User::factory(['name' => 'John Doe'])->resolver(true)->create();

        $user = User::factory()->create();
        $incident = Incident::factory([
            'category_id' => $category,
            'item_id' => $item,
            'group_id' => $group,
            'resolver_id' => $resolver,
            'status_id' => $status,
            'caller_id' => $user,
        ])->create();


        $this->actingAs($user);

        $response = $this->get(route('incidents.edit', $incident));
        $response->assertSuccessful();
        $response->assertSee($category->name);
        $response->assertSee($item->name);
        $response->assertSee($group->name);
        $response->assertSee($resolver->name);
        $response->assertSee($status->name);
    }

    public function test_it_displays_comments()
    {
        $user = User::factory()->create();
        $incident = Incident::factory(['caller_id' => $user])->create();
        $this->actingAs($user);
        ActivityService::comment($incident, 'Comment Body');

        $response = $this->get(route('incidents.edit', $incident));

        $response->assertSuccessful();
        $response->assertSee('Comment Body');
    }

    public function test_on_hold_reason_field_is_hidden_when_status_is_not_on_hold()
    {
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory()->statusInProgress()->create();

        Livewire::actingAs($resolver)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->assertDontSee('On hold reason');
    }
    public function test_on_hold_reason_field_is_shown_when_status_is_on_hold()
    {
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory(['on_hold_reason_id' => IncidentOnHoldReason::WAITING_FOR_VENDOR])
            ->statusOnHold()->create();

        Livewire::actingAs($resolver)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->assertSee('On hold reason');
    }

    public function test_status_can_be_set_to_cancelled_if_previous_status_is_different()
    {
        $incident = Incident::factory()->create();
        $resolver = User::factory()->resolver()->create();

        Livewire::actingAs($resolver)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('status', Status::CANCELLED)
            ->call('save')
            ->assertSuccessful();

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'status_id' => Status::CANCELLED,
        ]);
    }

    public function test_it_returns_forbidden_if_user_with_no_permission_sets_priority_one_to_a_incident()
    {
        // resolver does not have a permisssion to set priority one
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory()->create();

        Livewire::actingAs($resolver)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('priority', 1)
            ->assertForbidden();
    }

    public function test_it_does_not_return_forbidden_if_user_with_permission_assigns_priority_one_to_a_incident()
    {
        // manager has a permisssion to set priority one
        $manager = User::factory()->manager()->create();
        $incident = Incident::factory()->create();

        Livewire::actingAs($manager)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('priority', 1)
            ->set('priorityChangeReason', 'Production issue')
            ->call('save')
            ->assertSuccessful();

        $this->assertDatabaseHas('incidents', [
           'id' => $incident->id,
           'priority' => 1,
        ]);
    }

    public function test_it_allows_user_with_permission_to_set_priority_one_to_also_set_lower_priorities()
    {
        // manager has a permisssion to set priority one
        $user = User::factory()->manager()->create();
        $incident = Incident::factory()->create();

        Livewire::actingAs($user)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->set('priority', 2)
            ->set('priorityChangeReason', 'Production issue')
            ->call('save')
            ->assertSuccessful();

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'priority' => 2,
        ]);
    }

    public function test_it_emits_incident_updated_on_save_call()
    {
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory()->create();

        Livewire::actingAs($resolver)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->call('save')
            ->assertDispatched('model-updated');
    }

    public function test_it_displays_incident_created_activity()
    {
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory()->create();

        Livewire::actingAs($resolver)
            ->test(Activities::class, ['model' => $incident])
            ->assertSuccessful()
            ->assertSeeInOrder([
                'Status:', 'Open',
                'Priority', '4',
                'Group:', 'SERVICE-DESK',
            ]);
    }

    public function test_it_displays_changes_activity_dynamically()
    {
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory(['status_id' => Status::OPEN])->create();

        Livewire::actingAs($resolver);

        Livewire::test(IncidentEditForm::class, ['incident' => $incident])
            ->set('status', Status::IN_PROGRESS)
            ->call('save')
            ->assertSuccessful();

        $incident = $incident->refresh();

        Livewire::test(Activities::class, ['model' => $incident])
            ->assertSuccessful()
            ->assertSeeInOrder(['Status:', 'In Progress', 'was', 'Open']);
    }

    public function test_it_displays_multiple_activity_changes()
    {
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory([
            'status_id' => Status::OPEN,
            'group_id' => Group::SERVICE_DESK,
        ])->create();

        Livewire::actingAs($resolver);

        Livewire::test(IncidentEditForm::class, ['incident' => $incident])
            ->set('status', Status::IN_PROGRESS)
            ->set('group', Group::LOCAL_6445_NEW_YORK)
            ->call('save')
            ->assertSuccessful();

        $incident = $incident->refresh();

        Livewire::test(Activities::class, ['model' => $incident])
            ->assertSuccessful()
            ->assertSeeInOrder(['Status:', 'In Progress', 'was', 'Open'])
            ->assertSeeInOrder(['Group:', 'LOCAL-6445-NEW-YORK', 'was', 'SERVICE-DESK']);
    }

    public function test_it_displays_status_changes_activity()
    {
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory(['status_id' => Status::OPEN])->create();

        Livewire::actingAs($resolver);

        Livewire::test(IncidentEditForm::class, ['incident' => $incident])
            ->set('status', Status::IN_PROGRESS)
            ->call('save')
            ->assertSuccessful();

        $incident = $incident->refresh();

        Livewire::test(Activities::class, ['model' => $incident])
            ->assertSuccessful()
            ->assertSeeInOrder(['Status:', 'In Progress', 'was', 'Open']);
    }

    public function test_it_displays_on_hold_reason_changes_activity()
    {
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory()->create();

        Livewire::actingAs($resolver);

        Livewire::test(IncidentEditForm::class, ['incident' => $incident])
            ->set('status', Status::ON_HOLD)
            ->set('onHoldReason', IncidentOnHoldReason::CALLER_RESPONSE)
            ->call('save')
            ->assertSuccessful();

        $incident = $incident->refresh();

        Livewire::test(Activities::class, ['model' => $incident])
            ->assertSuccessful()
            ->assertSeeInOrder(['On hold reason:', 'Caller Response', 'was', 'empty']);
    }

    public function test_it_displays_priority_changes_activity()
    {
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory(['priority' => Incident::DEFAULT_PRIORITY])->create();

        Livewire::actingAs($resolver);

        Livewire::test(IncidentEditForm::class, ['incident' => $incident])
            ->set('priority', 3)
            ->set('priorityChangeReason', 'Production issue')
            ->call('save')
            ->assertSuccessful();

        $incident = $incident->refresh();

        Livewire::test(Activities::class, ['model' => $incident])
            ->assertSuccessful()
            ->assertSeeInOrder(['Priority:', '3', 'was', Incident::DEFAULT_PRIORITY]);
    }

    public function test_it_displays_group_changes_activity()
    {
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory(['group_id' => Group::SERVICE_DESK])->create();

        Livewire::actingAs($resolver);

        Livewire::test(IncidentEditForm::class, ['incident' => $incident])
            ->set('group', Group::LOCAL_6445_NEW_YORK)
            ->call('save')
            ->assertSuccessful();

        $incident = $incident->refresh();

        Livewire::test(Activities::class, ['model' => $incident])
            ->assertSuccessful()
            ->assertSeeInOrder(['Group:', 'LOCAL-6445-NEW-YORK', 'was', 'SERVICE-DESK']);
    }

    public function test_it_displays_resolver_changes_activity()
    {
        $resolver = User::factory(['name' => 'Average Joe'])->resolverAllGroups()->create();
        $incident = Incident::factory()->create();

        Livewire::actingAs($resolver);

        Livewire::test(IncidentEditForm::class, ['incident' => $incident])
            ->set('resolver', $resolver->id)
            ->call('save')
            ->assertSuccessful();

        $incident = $incident->refresh();

        Livewire::test(Activities::class, ['model' => $incident])
            ->assertSuccessful()
            ->assertSeeInOrder(['Resolver:', 'Average Joe', 'was', 'empty']);
    }

    public function test_it_displays_activities_in_descending_order()
    {
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory()->create();

        $incident->status_id = Status::IN_PROGRESS;
        $incident->save();

        ActivityService::comment($incident, 'Test Comment');

        $incident->status_id = Status::MONITORING;
        $incident->save();

        $incident->refresh();

        Livewire::actingAs($resolver)
            ->test(Activities::class, ['model' => $incident])
            ->assertSeeInOrder([
                'Status:', 'Monitoring', 'was', 'In Progress',
                'Test Comment',
                'Status:', 'In Progress', 'was', 'Open',
                'Created', 'Status:', 'Open',
            ]);
    }

    public function test_it_requires_priority_change_reason_if_priority_changes()
    {
        $incident = Incident::factory()->create();
        $resolver = User::factory()->resolver()->create();

        Livewire::actingAs($resolver);

        Livewire::test(IncidentEditForm::class, ['incident' => $incident])
            ->set('priority', 3)
            ->call('save')
            ->assertHasErrors(['priorityChangeReason' => 'required'])
            ->set('priorityChangeReason', 'Production issue')
            ->call('save')
            ->assertSuccessful();
    }

    public function test_sla_bar_shows_correct_minutes()
    {
        $resolver = User::factory()->resolver()->create();
        $incident = Incident::factory()->create();

        $date = Carbon::now()->addMinutes(10);
        Carbon::setTestNow($date);

        Livewire::actingAs($resolver)
            ->test(IncidentEditForm::class, ['incident' => $incident])
            ->assertSee($incident->sla->minutesTillExpires() . ' minutes');
    }
}