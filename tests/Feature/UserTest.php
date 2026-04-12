<?php

namespace Tests\Feature;

use App\Models\Gimnasio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $entrenador;
    private Gimnasio $gimnasio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gimnasio   = Gimnasio::factory()->create();
        $this->admin      = User::factory()->create(['gimnasio_id' => $this->gimnasio->id, 'rol' => 'admin']);
        $this->entrenador = User::factory()->create(['gimnasio_id' => $this->gimnasio->id, 'rol' => 'entrenador']);
    }

    public function test_admin_puede_listar_usuarios(): void
    {
        $this->actingAs($this->admin)
             ->getJson('/api/usuarios')
             ->assertStatus(200);
    }

    public function test_entrenador_no_puede_listar_usuarios(): void
    {
        $this->actingAs($this->entrenador)
             ->getJson('/api/usuarios')
             ->assertStatus(403);
    }

    public function test_admin_puede_crear_entrenador(): void
    {
        $response = $this->actingAs($this->admin)
                         ->postJson('/api/usuarios', [
                             'name'     => 'Nuevo Entrenador',
                             'email'    => 'nuevo@gym.com',
                             'password' => 'password123',
                             'rol'      => 'entrenador',
                         ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['rol' => 'entrenador']);

        $this->assertDatabaseHas('users', [
            'email'       => 'nuevo@gym.com',
            'gimnasio_id' => $this->gimnasio->id,
        ]);
    }

    public function test_admin_puede_desactivar_usuario(): void
    {
        $this->actingAs($this->admin)
             ->putJson("/api/usuarios/{$this->entrenador->id}", ['activo' => false])
             ->assertStatus(200)
             ->assertJsonFragment(['activo' => false]);
    }

    public function test_admin_no_puede_eliminarse_a_si_mismo(): void
    {
        $this->actingAs($this->admin)
             ->deleteJson("/api/usuarios/{$this->admin->id}")
             ->assertStatus(422);
    }

    public function test_admin_puede_eliminar_otro_usuario(): void
    {
        $this->actingAs($this->admin)
             ->deleteJson("/api/usuarios/{$this->entrenador->id}")
             ->assertStatus(200);

        $this->assertSoftDeleted('users', ['id' => $this->entrenador->id]);
    }

    public function test_no_ve_usuarios_de_otro_gimnasio(): void
    {
        $otroGimnasio = Gimnasio::factory()->create();
        $otroUser     = User::factory()->create(['gimnasio_id' => $otroGimnasio->id]);

        $this->actingAs($this->admin)
             ->getJson("/api/usuarios/{$otroUser->id}")
             ->assertStatus(403);
    }
}
