<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Travel;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AdminTourTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_user_cant_access_adding_tours(): void
    {
        $travel = Travel::factory()->create();
        $response = $this->postJson('/api/v1/admin/travels/'.$travel->slug.'/tours');

        $response->assertUnauthorized();
    }

    public function test_non_admin_cant_access_adding_tours(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'editor')->value('id'));

        $travel = Travel::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/admin/travels/'.$travel->slug.'/tours');
        $response->assertForbidden();
    }

    public function test_adding_tours_works_with_valid_data(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'admin')->value('id'));

        $travel = Travel::factory()->create();

        $tour = [
            'name' => 'Example tour name',
            'starting_date' => now()->toDateString(),
            'ending_date' => now()->addDay()->toDateString(),
            'price' => 123.45,
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/admin/travels/'.$travel->slug.'/tours', $tour);
        $response->assertCreated();

        $response = $this->get(('api/v1/travels/'.$travel->slug.'/tours'));
        $response->assertJsonFragment(['name' => $tour['name']]);
    }
}
