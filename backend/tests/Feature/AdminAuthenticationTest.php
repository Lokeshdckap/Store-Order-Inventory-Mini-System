<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_with_correct_credentials(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin12345'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'admin12345',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Login successful')
            ->assertJsonPath('user.name', 'Admin')
            ->assertJsonPath('user.email', 'admin@example.com')
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email'],
                'token',
            ]);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_admin_cannot_login_with_invalid_credentials(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('correctpassword'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Invalid credentials');
    }

    public function test_validation_errors_on_login(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'invalid-email',
            'password' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_authenticated_admin_can_fetch_own_profile(): void
    {
        $admin = User::create([
            'name' => 'Admin Store',
            'email' => 'storeadmin@example.com',
            'password' => Hash::make('admin12345'),
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Admin Store')
            ->assertJsonPath('email', 'storeadmin@example.com');
    }

    public function test_authenticated_admin_can_logout_and_revoke_token(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin12345'),
        ]);

        $token = $admin->createToken('admin-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Logged out successfully');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_unauthenticated_requests_to_protected_endpoints_are_rejected(): void
    {
        $response = $this->getJson('/api/products');
        $response->assertStatus(401);

        $response = $this->postJson('/api/orders', []);
        $response->assertStatus(401);

        $response = $this->getJson('/api/user');
        $response->assertStatus(401);
    }
}
