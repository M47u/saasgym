<?php

namespace Tests\Feature;

use App\Models\Gimnasio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $admin;
    private User $entrenador;
    private Gimnasio $gimnasio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->gimnasio   = Gimnasio::factory()->create();
        $this->admin      = User::factory()->create(['gimnasio_id' => $this->gimnasio->id, 'rol' => 'admin']);
        $this->entrenador = User::factory()->create(['gimnasio_id' => $this->gimnasio->id, 'rol' => 'entrenador']);
    }

    // ─── Super Admin: CRUD de gimnasios ──────────────────────────────────

    public function test_super_admin_puede_listar_gimnasios(): void
    {
        Gimnasio::factory(3)->create();

        $this->actingAs($this->superAdmin)
             ->getJson('/api/super-admin/gimnasios')
             ->assertStatus(200)
             ->assertJsonStructure(['data', 'meta']);
    }

    public function test_super_admin_puede_crear_gimnasio(): void
    {
        $response = $this->actingAs($this->superAdmin)
                         ->postJson('/api/super-admin/gimnasios', [
                             'nombre'                        => 'Nuevo Gym',
                             'email'                         => 'nuevo@gym.com',
                             'admin_nombre'                  => 'Admin Nuevo Gym',
                             'admin_email'                   => 'admin.nuevo@gym.com',
                             'admin_password'                => 'password123',
                             'admin_password_confirmation'   => 'password123',
                         ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['nombre' => 'Nuevo Gym']);

        $this->assertDatabaseHas('gimnasios', ['email' => 'nuevo@gym.com']);
        $this->assertDatabaseHas('users', [
            'email' => 'admin.nuevo@gym.com',
            'rol'   => 'admin',
            'activo' => true,
        ]);
    }

    public function test_super_admin_puede_ver_gimnasio(): void
    {
        $this->actingAs($this->superAdmin)
             ->getJson("/api/super-admin/gimnasios/{$this->gimnasio->id}")
             ->assertStatus(200)
             ->assertJsonFragment(['id' => $this->gimnasio->id]);
    }

    public function test_super_admin_puede_editar_gimnasio(): void
    {
        $this->actingAs($this->superAdmin)
             ->putJson("/api/super-admin/gimnasios/{$this->gimnasio->id}", [
                 'nombre' => 'Gym Actualizado',
             ])
             ->assertStatus(200)
             ->assertJsonFragment(['nombre' => 'Gym Actualizado']);
    }

    public function test_super_admin_puede_soft_delete_gimnasio(): void
    {
        $this->actingAs($this->superAdmin)
             ->deleteJson("/api/super-admin/gimnasios/{$this->gimnasio->id}")
             ->assertStatus(200);

        $this->assertSoftDeleted('gimnasios', ['id' => $this->gimnasio->id]);
    }

    public function test_super_admin_puede_desactivar_gimnasio(): void
    {
        $this->actingAs($this->superAdmin)
             ->postJson("/api/super-admin/gimnasios/{$this->gimnasio->id}/desactivar")
             ->assertStatus(200)
             ->assertJsonFragment(['activo' => false]);

        $this->assertDatabaseHas('gimnasios', ['id' => $this->gimnasio->id, 'activo' => false]);
    }

    public function test_super_admin_puede_activar_gimnasio(): void
    {
        $this->gimnasio->update(['activo' => false]);

        $this->actingAs($this->superAdmin)
             ->postJson("/api/super-admin/gimnasios/{$this->gimnasio->id}/activar")
             ->assertStatus(200)
             ->assertJsonFragment(['activo' => true]);

        $this->assertDatabaseHas('gimnasios', ['id' => $this->gimnasio->id, 'activo' => true]);
    }

    // ─── Acceso denegado: admin/entrenador en rutas super_admin ──────────

    public function test_admin_no_puede_acceder_rutas_super_admin(): void
    {
        $this->actingAs($this->admin)
             ->getJson('/api/super-admin/gimnasios')
             ->assertStatus(403);
    }

    public function test_entrenador_no_puede_acceder_rutas_super_admin(): void
    {
        $this->actingAs($this->entrenador)
             ->getJson('/api/super-admin/gimnasios')
             ->assertStatus(403);
    }

    public function test_unauthenticated_no_puede_acceder_rutas_super_admin(): void
    {
        $this->getJson('/api/super-admin/gimnasios')
             ->assertStatus(401);
    }

    // ─── Acceso denegado: super_admin en rutas de gimnasio ───────────────

    public function test_super_admin_no_puede_acceder_rutas_de_gimnasio(): void
    {
        $this->actingAs($this->superAdmin)
             ->getJson('/api/socios')
             ->assertStatus(403);
    }

    public function test_super_admin_no_puede_acceder_pagos(): void
    {
        $this->actingAs($this->superAdmin)
             ->getJson('/api/pagos')
             ->assertStatus(403);
    }

    public function test_super_admin_no_puede_acceder_asistencias(): void
    {
        $this->actingAs($this->superAdmin)
             ->getJson('/api/asistencias')
             ->assertStatus(403);
    }

    // ─── Bloqueo por gimnasio inactivo ────────────────────────────────────

    public function test_usuario_de_gimnasio_inactivo_no_puede_operar(): void
    {
        $this->gimnasio->update(['activo' => false]);

        $this->actingAs($this->admin)
             ->getJson('/api/socios')
             ->assertStatus(403);
    }

    public function test_entrenador_de_gimnasio_inactivo_bloqueado(): void
    {
        $this->gimnasio->update(['activo' => false]);

        $this->actingAs($this->entrenador)
             ->getJson('/api/asistencias')
             ->assertStatus(403);
    }

    public function test_login_rechaza_usuario_de_gimnasio_inactivo(): void
    {
        $this->gimnasio->update(['activo' => false]);
        $user = User::factory()->create([
            'gimnasio_id' => $this->gimnasio->id,
            'password'    => bcrypt('password123'),
            'activo'      => true,
        ]);

        $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ])->assertStatus(403)
          ->assertJsonFragment(['message' => 'El gimnasio está desactivado. Contactá al super administrador.']);
    }

    public function test_super_admin_puede_login_sin_gimnasio(): void
    {
        $sa = User::factory()->superAdmin()->create([
            'password' => bcrypt('password123'),
        ]);

        $this->postJson('/api/login', [
            'email'    => $sa->email,
            'password' => 'password123',
        ])->assertStatus(200)
          ->assertJsonStructure(['token', 'user'])
          ->assertJsonFragment(['rol' => 'super_admin']);
    }

    // ─── Aislamiento tenant ───────────────────────────────────────────────

    public function test_gimnasios_no_son_visibles_entre_si(): void
    {
        $otroGimnasio = Gimnasio::factory()->create();
        /** @var User $otroAdmin */
        $otroAdmin    = User::factory()->create(['gimnasio_id' => $otroGimnasio->id, 'rol' => 'admin']);

        // Super admin ve ambos
        $response = $this->actingAs($this->superAdmin)
                         ->getJson('/api/super-admin/gimnasios');
        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(2, $response->json('meta.total'));

        // Admin de un gimnasio no afecta al otro
        $this->actingAs($this->admin)
             ->getJson('/api/socios')
             ->assertStatus(200)
             ->assertJsonCount(0, 'data');

        $this->actingAs($otroAdmin)
             ->getJson('/api/socios')
             ->assertStatus(200)
             ->assertJsonCount(0, 'data');
    }

    public function test_email_duplicado_de_gimnasio_es_rechazado(): void
    {
        $this->actingAs($this->superAdmin)
             ->postJson('/api/super-admin/gimnasios', [
                 'nombre'                        => 'Otro',
                 'email'                         => $this->gimnasio->email, // already exists
                 'admin_nombre'                  => 'Admin Otro',
                 'admin_email'                   => 'admin.otro@gym.com',
                 'admin_password'                => 'password123',
                 'admin_password_confirmation'   => 'password123',
             ])
             ->assertStatus(422);
    }
}
