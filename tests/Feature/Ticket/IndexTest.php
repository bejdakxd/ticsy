<?php


namespace Tests\Feature\Ticket;

use App\Http\Controllers\TicketsController;
use App\Models\Resolver;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;
    function testTicketsIndexRedirectsGuestsToLoginPage()
    {
        $response = $this->get(route('tickets.index'));

        $response->assertRedirectToRoute('login');
    }

    function testTicketsIndexDisplaysTicketsCorrectly()
    {
        $user = User::factory(['name' => 'John Doe'])->create();
        $resolver = User::factory(['name' => 'Jeff Wing'])->resolver()->create();
        Ticket::factory([
            'description' => 'Ticket Description',
            'user_id' => $user,
            'resolver_id' => $resolver,
        ])->create();

        $this->actingAs($user);
        $response = $this->get(route('tickets.index'));

        $response->assertSuccessful();
        $response->assertSee('John Doe');
        $response->assertSee('Jeff Wing');
        $response->assertSee('Ticket Description');
    }

    function test_tickets_index_pagination_displays_correct_number_of_tickets()
    {
        $user = User::factory()->create();

        Ticket::factory([
            'description' => 'This ticket is supposed to be on the second pagination page',
            'user_id' => $user,
        ])->create();

        Ticket::factory(TicketsController::DEFAULT_PAGINATION, [
            'user_id' => $user,
        ])->create();

        $this->actingAs($user);

        $response = $this->get(route('tickets.index', ['page' => 1]));
        $response->assertSuccessful();
        $response->assertDontSee('This ticket is supposed to be on the second pagination page');

        $response = $this->get(route('tickets.index', ['page' => 2]));
        $response->assertSuccessful();
        $response->assertSee('This ticket is supposed to be on the second pagination page');
    }
}
