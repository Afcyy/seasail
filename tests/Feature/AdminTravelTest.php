<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AdminTravelTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_user_cant_access_adding_travels(): void
    {
        $response = $this->postJson('/api/v1/admin/travels');

        $response->assertUnauthorized();
    }

    public function test_non_admin_cant_access_adding_travels(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'editor')->value('id'));

        $response = $this->actingAs($user)->postJson('/api/v1/admin/travels');
        $response->assertForbidden();
    }

    public function test_adding_travel_works_with_valid_data(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'admin')->value('id'));

        $travel = [
            'is_public' => 1,
            'name' => 'Example travel name',
            'description' => 'Example description for example travel',
            'number_of_days' => 5,
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/admin/travels', $travel);
        $response->assertCreated();

        $response = $this->get(('api/v1/travels'));
        $response->assertJsonFragment(['name' => $travel['name']]);
    }
}
