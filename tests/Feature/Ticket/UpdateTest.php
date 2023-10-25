<?php

namespace Tests\Feature\Ticket;

use App\Livewire\TicketForm;
use App\Models\Group;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\TicketConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_redirects_guest_to_login_page()
    {
        $ticket = Ticket::factory()->create();

        $response = $this->patch(route('tickets.set-status', $ticket), [
            'status' => TicketConfig::STATUSES['in_progress'],
        ]);

        $response->assertRedirectToRoute('login');
    }

    public function test_non_resolver_user_cannot_set_status()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        Livewire::actingAs($user);

        Livewire::test(TicketForm::class, ['ticket' => $ticket])
            ->set('status', TicketConfig::STATUSES['in_progress'])
            ->assertForbidden();
    }

    public function test_resolver_can_set_status()
    {
        $resolver = User::factory()->resolver()->create();
        $ticket = Ticket::factory()->create();

        Livewire::actingAs($resolver);

        Livewire::test(TicketForm::class, ['ticket' => $ticket])
            ->set('status', TicketConfig::STATUSES['in_progress'])
            ->call('save');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status_id' => TicketConfig::STATUSES['in_progress'],
        ]);
    }

    public function test_it_fails_validation_when_unknown_status_is_set()
    {
        $resolver = User::factory()->resolver()->create();
        $ticket = Ticket::factory()->create();

        Livewire::actingAs($resolver)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->set('status', count(TicketConfig::STATUSES) + 1)
            ->call('save')
            ->assertHasErrors(['status' => 'max']);
    }

    public function test_it_fails_validation_when_unknown_priority_is_set()
    {
        $resolver = User::factory()->resolver()->create();
        $ticket = Ticket::factory()->create();

        Livewire::actingAs($resolver)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->set('priority', count(TicketConfig::PRIORITIES) + 1)
            ->call('save')
            ->assertHasErrors(['priority' => 'max']);
    }

    public function test_it_fails_validation_when_unknown_group_is_set()
    {
        $resolver = User::factory()->resolver()->create();
        $ticket = Ticket::factory()->create();

        Livewire::actingAs($resolver)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->set('group', count(Group::GROUPS) + 1)
            ->call('save')
            ->assertHasErrors(['group' => 'max']);
    }

    public function test_it_fails_validation_when_unknown_resolver_is_set()
    {
        $resolver = User::factory()->resolver()->create();
        $ticket = Ticket::factory()->create();

        Livewire::actingAs($resolver)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->set('resolver', User::max('id') + 1)
            ->call('save')
            ->assertHasErrors(['resolver' => 'max']);
    }

    public function test_guest_is_redirected_to_login_page()
    {
        $resolver = User::factory()->resolver()->create();
        $ticket = Ticket::factory()->create();

        $response = $this->patch(route('tickets.set-resolver', $ticket), [
            'resolver' => $resolver
        ]);

        $response->assertRedirectToRoute('login');
    }

    public function test_non_resolver_user_cannot_set_resolver()
    {
        $user = User::factory()->create();
        $resolver = User::factory()->resolver()->create();
        $ticket = Ticket::factory()->create();

        Livewire::actingAs($user)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->set('resolver', $resolver->id)
            ->assertForbidden();
    }

    public function test_resolver_user_can_set_resolver()
    {
        $user = User::factory()->resolver()->create();
        $group = Group::findOrFail(Group::DEFAULT);
        $resolver = User::factory()->hasAttached($group)->create()->assignRole('resolver');
        $ticket = Ticket::factory()->create();

        Livewire::actingAs($user)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->set('resolver', $resolver->id)
            ->call('save');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'resolver_id' => $resolver->id,
        ]);
    }

    function test_user_can_change_priority_with_permission()
    {
        $resolver = User::factory()->create()->givePermissionTo('set_priority');
        $ticket = Ticket::factory(['priority' => 4])->create();

        Livewire::actingAs($resolver)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->set('priority', 2)
            ->call('save');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'priority' => 2,
        ]);
    }

    function test_user_cannot_change_priority_without_permission()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory(['priority' => 4])->create();

        Livewire::actingAs($user)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->set('priority', 2)
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'priority' => 4,
        ]);
    }

    public function test_it_updates_ticket_when_correct_data_submitted()
    {
        $resolver = User::factory()->resolver()->create();
        $group = Group::find(Group::GROUPS['LOCAL-6445-NEW-YORK']);
        $group->resolvers()->attach($resolver);
        $ticket = Ticket::factory()->create();
        $status = Status::findOrFail(TicketConfig::STATUSES['in_progress']);
        $priority = TicketConfig::DEFAULT_PRIORITY - 1;

        Livewire::actingAs($resolver)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->set('status', $status->id)
            ->set('priority', $priority)
            ->set('group', $group->id)
            ->set('resolver', $resolver->id)
            ->call('save');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'priority' => $priority,
            'status_id' => $status->id,
            'group_id' => $group->id,
            'resolver_id' => $resolver->id,
        ]);
    }

    public function test_ticket_priority_cannot_be_changed_when_status_is_resolved(){
        $resolver = User::factory()->resolver()->create();
        $ticket = Ticket::factory([
            'priority' => TicketConfig::DEFAULT_PRIORITY,
            'status_id' => TicketConfig::STATUSES['resolved']
        ])->create();

        Livewire::actingAs($resolver)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->set('priority', TicketConfig::DEFAULT_PRIORITY - 1)
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'priority' => TicketConfig::DEFAULT_PRIORITY,
        ]);
    }

    public function test_ticket_status_can_be_changed_when_status_is_resolved(){
        $resolver = User::factory()->resolver()->create();
        $ticket = Ticket::factory(['status_id' => TicketConfig::STATUSES['resolved']])->create();

        Livewire::actingAs($resolver)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->set('status', TicketConfig::DEFAULT_STATUS)
            ->call('save')
            ->assertSuccessful();

        $this->assertDatabaseHas('tickets', [
           'id' => $ticket->id,
           'status_id' => TicketConfig::DEFAULT_STATUS,
        ]);
    }

    public function test_ticket_resolver_cannot_be_changed_when_status_is_resolved(){
        $resolver = User::factory()->resolver()->create();
        $ticket = Ticket::factory([
            'status_id' => TicketConfig::STATUSES['resolved'],
            'resolver_id' => null,
        ])->create();

        Livewire::actingAs($resolver)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->set('resolver', $resolver->id)
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'resolver_id' => null,
        ]);
    }

    public function test_ticket_priority_cannot_be_changed_when_status_is_cancelled(){
        $resolver = User::factory()->resolver()->create();
        $ticket = Ticket::factory(['status_id' => TicketConfig::STATUSES['cancelled']])->create();

        Livewire::actingAs($resolver)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->set('priority', TicketConfig::DEFAULT_PRIORITY - 1)
            ->assertForbidden();
    }

    public function test_ticket_status_cannot_be_changed_when_status_is_cancelled(){
        $resolver = User::factory()->resolver()->create();
        $ticket = Ticket::factory(['status_id' => TicketConfig::STATUSES['cancelled']])->create();

        Livewire::actingAs($resolver)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->set('status', TicketConfig::DEFAULT_STATUS)
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status_id' => TicketConfig::STATUSES['cancelled'],
        ]);
    }

    public function test_ticket_resolver_cannot_be_changed_when_status_is_cancelled(){
        $resolver = User::factory()->resolver()->create();
        $ticket = Ticket::factory(['status_id' => TicketConfig::STATUSES['cancelled']])->create();

        Livewire::actingAs($resolver)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->set('resolver', $resolver->id)
            ->assertForbidden();
    }

    public function test_resolver_field_lists_resolvers_based_on_selected_group()
    {
        $resolverOne = User::factory(['name' => 'John Doe'])->create()->assignRole('resolver');
        $resolverTwo = User::factory(['name' => 'Joe Rogan'])->create()->assignRole('resolver');
        $resolverThree = User::factory(['name' => 'Fred Flinstone'])->create()->assignRole('resolver');

        $groupOne = Group::findOrFail(Group::GROUPS['SERVICE-DESK']);
        $groupOne->resolvers()->attach($resolverOne);
        $groupOne->resolvers()->attach($resolverTwo);

        $groupTwo = Group::findOrFail(Group::GROUPS['LOCAL-6445-NEW-YORK']);
        $groupTwo->resolvers()->attach($resolverThree);

        $ticket = Ticket::factory(['group_id' => $groupOne])->create();

        Livewire::actingAs($resolverOne)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->set('group', $groupOne->id)
            ->assertSee('John Doe')
            ->assertSee('Joe Rogan')
            ->assertDontSee('Fred Flinstone');

        Livewire::test(TicketForm::class, ['ticket' => $ticket])
            ->set('group', $groupTwo->id)
            ->assertDontSee('John Doe')
            ->assertDontSee('Joe Rogan')
            ->assertSee('Fred Flinstone');
    }

    public function test_resolver_from_not_selected_group_cannot_be_assigned_to_the_ticket_as_resolver()
    {
        $resolverOne = User::factory()->resolver()->create();
        $resolverTwo = User::factory()->resolver()->create();

        $groupOne = Group::findOrFail(Group::GROUPS['SERVICE-DESK']);
        $groupOne->resolvers()->attach($resolverOne);

        $groupTwo = Group::findOrFail(Group::GROUPS['LOCAL-6445-NEW-YORK']);
        $groupTwo->resolvers()->attach($resolverTwo);

        $ticket = Ticket::factory(['group_id' => $groupOne])->create();

        Livewire::actingAs($resolverOne)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->assertSuccessful()
            ->set('resolver', $resolverTwo->id)
            ->assertForbidden();
    }

    public function test_selected_resolver_is_empty_when_resolver_group_changes()
    {
        $resolverOne = User::factory()->resolver()->create();
        $resolverTwo = User::factory()->resolver()->create();

        $groupOne = Group::findOrFail(Group::GROUPS['SERVICE-DESK']);
        $groupOne->resolvers()->attach($resolverOne);

        $groupTwo = Group::findOrFail(Group::GROUPS['LOCAL-6445-NEW-YORK']);
        $groupTwo->resolvers()->attach($resolverTwo);

        $ticket = Ticket::factory(['group_id' => $groupOne])->create();

        Livewire::actingAs($resolverOne)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->set('resolver', $resolverOne->id)
            ->call('save');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'group_id' => $groupOne->id,
            'resolver_id' => $resolverOne->id,
        ]);

        Livewire::test(TicketForm::class, ['ticket' => $ticket])
            ->set('group', $groupTwo->id)
            ->call('save');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'group_id' => $groupTwo->id,
            'resolver_id' => null,
        ]);
    }
}