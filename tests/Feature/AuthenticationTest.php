<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_profile_attributes(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'role' => 'user',
            'department' => 'Finance',
            'years_of_experience' => 5,
            'location' => 'Bhubaneswar',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'access_token',
                'token_type',
                'user' => ['id', 'name', 'email', 'department', 'years_of_experience', 'location'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'department' => 'Finance',
            'years_of_experience' => 5,
        ]);
    }

    public function test_user_can_login_and_receive_sanctum_token(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type', 'user']);
    }

    public function test_authenticated_user_can_update_profile_and_trigger_recomputation(): void
    {
        $user = User::factory()->finance(2)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/auth/profile', [
                'years_of_experience' => 6,
                'location' => 'Delhi',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'years_of_experience' => 6,
            'location' => 'Delhi',
        ]);
    }
}
