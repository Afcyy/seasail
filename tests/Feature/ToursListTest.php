<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Models\Travel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToursListTest extends TestCase
{
    use RefreshDatabase;

    public function test_tours_list_by_travel_slug_returns_correct_tours(): void
    {
        $travel = Travel::factory()->create();
        $tour = Tour::factory()->create(['travel_id' => $travel->id]);

        $response = $this->get('/api/v1/travels/'.$travel->slug.'/tours');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $tour->id]);
    }

    public function test_price_is_shown_correctly(): void
    {
        $travel = Travel::factory()->create();
        Tour::factory()->create([
            'travel_id' => $travel->id,
            'price' => 123.45,
        ]);

        $response = $this->get('/api/v1/travels/'.$travel->slug.'/tours');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['price' => '123.45']);
    }

    public function test_travels_list_returns_paginated_data(): void
    {
        $pagination = config('app.pagination.tours');

        $travel = Travel::factory()->create(['is_public' => true]);
        Tour::factory( $pagination + 1)->create(['travel_id' => $travel->id]);

        $response = $this->get('/api/v1/travels/'.$travel->slug.'/tours');

        $response->assertOk();
        $response->assertJsonCount($pagination, 'data');
        $response->assertJsonPath('meta.last_page', 2);
    }

    public function test_tour_list_sorts_by_starting_date_correctly(): void
    {
        $travel = Travel::factory()->create();

        $lateTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'starting_date' => now()->addDays(2),
            'ending_date' => now()->addDays(3),
        ]);
        $earlyTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'starting_date' => now(),
            'ending_date' => now()->addDay(),
        ]);

        $response = $this->get('/api/v1/travels/'.$travel->slug.'/tours');

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $earlyTour->id);
        $response->assertJsonPath('data.1.id', $lateTour->id);
    }

    public function test_tour_list_sorts_by_price_correctly(): void
    {
        $travel = Travel::factory()->create();

        $expensiveTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price' => 999,
        ]);
        $cheapLateTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price' => 100,
            'starting_date' => now()->addDays(2),
            'ending_date' => now()->addDays(3),
        ]);
        $cheapEarlyTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price' => 100,
            'starting_date' => now(),
            'ending_date' => now()->addDay(),
        ]);

        $response = $this->get('/api/v1/travels/'.$travel->slug.'/tours?sortBy=price&sortOrder=asc');

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $cheapEarlyTour->id);
        $response->assertJsonPath('data.1.id', $cheapLateTour->id);
        $response->assertJsonPath('data.2.id', $expensiveTour->id);
    }

    public function test_tour_price_filters_work_correctly(): void
    {
        $travel = Travel::factory()->create();

        $expensiveTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price' => 999,
        ]);
        $cheapTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price' => 100,
        ]);

        $endpoint = '/api/v1/travels/'.$travel->slug.'/tours';

        $response = $this->get($endpoint.'?priceFrom=100');
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $cheapTour->id]);
        $response->assertJsonFragment(['id' => $expensiveTour->id]);

        $response = $this->get($endpoint.'?priceFrom=150');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonMissing(['id' => $cheapTour->id]);
        $response->assertJsonFragment(['id' => $expensiveTour->id]);

        $response = $this->get($endpoint.'?priceFrom=1000');
        $response->assertJsonCount(0, 'data');

        $response = $this->get($endpoint.'?priceTo=1000');
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $cheapTour->id]);
        $response->assertJsonFragment(['id' => $expensiveTour->id]);

        $response = $this->get($endpoint.'?priceTo=150');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $cheapTour->id]);
        $response->assertJsonMissing(['id' => $expensiveTour->id]);

        $response = $this->get($endpoint.'?priceTo=99');
        $response->assertJsonCount(0, 'data');
    }

    public function test_tour_date_filters_work_correctly(): void
    {
        $travel = Travel::factory()->create();

        $lateTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'starting_date' => now()->addDays(2),
            'ending_date' => now()->addDays(3),
        ]);
        $earlyTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'starting_date' => now(),
            'ending_date' => now()->addDay(),
        ]);

        $endpoint = '/api/v1/travels/'.$travel->slug.'/tours';

        $response = $this->get($endpoint.'?dateFrom='.now());
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $earlyTour->id]);
        $response->assertJsonFragment(['id' => $lateTour->id]);

        $response = $this->get($endpoint.'?dateFrom='.now()->addDay());
        $response->assertJsonCount(1, 'data');
        $response->assertJsonMissing(['id' => $earlyTour->id]);
        $response->assertJsonFragment(['id' => $lateTour->id]);

        $response = $this->get($endpoint.'?dateFrom='.now()->addDays(10));
        $response->assertJsonCount(0, 'data');

        $response = $this->get($endpoint.'?dateTo='.now()->addDays(10));
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $earlyTour->id]);
        $response->assertJsonFragment(['id' => $lateTour->id]);

        $response = $this->get($endpoint.'?dateTo='.now()->addDay());
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $earlyTour->id]);
        $response->assertJsonMissing(['id' => $lateTour->id]);

        $response = $this->get($endpoint.'?dateTo='.now());
        $response->assertJsonCount(0, 'data');
    }

    public function test_tour_validation_works(): void
    {
        $travel = Travel::factory()->create();

        $response = $this->getJson('/api/v1/travels/'.$travel->slug.'/tours?dateFrom=abc');
        $response->assertUnprocessable();

        $response = $this->getJson('/api/v1/travels/'.$travel->slug.'/tours?priceTo=abc');
        $response->assertUnprocessable();
    }
}
