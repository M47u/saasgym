<?php

namespace Tests\Feature;

use App\Models\Gimnasio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_crea_gimnasio_y_admin(): void
    {
        $response = $this->postJson('/api/register', [
            'gimnasio_nombre'        => 'Gym Test',
            'gimnasio_email'         => 'gym@test.com',
            'admin_nombre'           => 'Admin',
            'admin_email'            => 'admin@test.com',
            'admin_password'         => 'password123',
            'admin_password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['token', 'user']);

        $this->assertDatabaseHas('gimnasios', ['email' => 'gym@test.com']);
        $this->assertDatabaseHas('users', ['email' => 'admin@test.com', 'rol' => 'admin']);
    }

    public function test_register_falla_si_email_gimnasio_duplicado(): void
    {
        Gimnasio::factory()->create(['email' => 'gym@test.com']);

        $response = $this->postJson('/api/register', [
            'gimnasio_nombre'        => 'Otro Gym',
            'gimnasio_email'         => 'gym@test.com',
            'admin_nombre'           => 'Admin',
            'admin_email'            => 'otro@test.com',
            'admin_password'         => 'password123',
            'admin_password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_retorna_token(): void
    {
        $gimnasio = Gimnasio::factory()->create();
        $user = User::factory()->create([
            'gimnasio_id' => $gimnasio->id,
            'password'    => bcrypt('password123'),
            'activo'      => true,
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'user']);
    }

    public function test_login_rechaza_credenciales_incorrectas(): void
    {
        $gimnasio = Gimnasio::factory()->create();
        User::factory()->create(['gimnasio_id' => $gimnasio->id]);

        $response = $this->postJson('/api/login', [
            'email'    => 'noexiste@test.com',
            'password' => 'wrongpass',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_rechaza_usuario_inactivo(): void
    {
        $gimnasio = Gimnasio::factory()->create();
        $user = User::factory()->create([
            'gimnasio_id' => $gimnasio->id,
            'password'    => bcrypt('password123'),
            'activo'      => false,
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
    }

    public function test_logout_invalida_token(): void
    {
        $gimnasio = Gimnasio::factory()->create();
        $user = User::factory()->create(['gimnasio_id' => $gimnasio->id]);

        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
             ->postJson('/api/logout')
             ->assertStatus(200);

        $this->withToken($token)
             ->getJson('/api/me')
             ->assertStatus(401);
    }

    public function test_me_retorna_usuario_autenticado(): void
    {
        $gimnasio = Gimnasio::factory()->create();
        $user = User::factory()->create(['gimnasio_id' => $gimnasio->id]);

        $this->actingAs($user)
             ->getJson('/api/me')
             ->assertStatus(200)
             ->assertJsonFragment(['email' => $user->email]);
    }
}
