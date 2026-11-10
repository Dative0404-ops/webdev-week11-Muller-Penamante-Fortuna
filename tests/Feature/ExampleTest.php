<?php

namespace Tests\Feature;

use App\Models\Venue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_event_pages_show_the_lazy_and_eager_query_counts(): void
    {
        $venue = Venue::create([
            'name' => 'Test Hall',
            'address' => 'Campus',
            'capacity' => 100,
        ]);

        foreach (['First Event', 'Second Event'] as $title) {
            $venue->events()->create([
                'title' => $title,
                'description' => 'A test event.',
            ]);
        }

        $this->get('/events/naive')
            ->assertOk()
            ->assertSeeText('5 queries for 2 events')
            ->assertSee('Attempted to lazy load [venue]');

        $this->assertTrue(Model::preventsLazyLoading());

        $this->get('/events')
            ->assertOk()
            ->assertSeeText('2 queries for 2 events');
    }
}
