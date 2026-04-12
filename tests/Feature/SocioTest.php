<?php

namespace Tests\Feature;

use App\Models\Gimnasio;
use App\Models\Socio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocioTest extends TestCase
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

    public function test_admin_puede_listar_socios(): void
    {
        Socio::factory(3)->create(['gimnasio_id' => $this->gimnasio->id]);

        $this->actingAs($this->admin)
             ->getJson('/api/socios')
             ->assertStatus(200)
             ->assertJsonCount(3, 'data');
    }

    public function test_no_ve_socios_de_otro_gimnasio(): void
    {
        $otroGimnasio = Gimnasio::factory()->create();
        Socio::factory(2)->create(['gimnasio_id' => $otroGimnasio->id]);
        Socio::factory(1)->create(['gimnasio_id' => $this->gimnasio->id]);

        $this->actingAs($this->admin)
             ->getJson('/api/socios')
             ->assertStatus(200)
             ->assertJsonCount(1, 'data');
    }

    public function test_admin_puede_crear_socio(): void
    {
        $response = $this->actingAs($this->admin)
                         ->postJson('/api/socios', [
                             'nombre' => 'Juan Pérez',
                             'email'  => 'juan@test.com',
                         ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['nombre' => 'Juan Pérez']);

        $this->assertDatabaseHas('socios', ['email' => 'juan@test.com', 'gimnasio_id' => $this->gimnasio->id]);
    }

    public function test_no_permite_email_duplicado_en_mismo_gimnasio(): void
    {
        Socio::factory()->create(['gimnasio_id' => $this->gimnasio->id, 'email' => 'dup@test.com']);

        $this->actingAs($this->admin)
             ->postJson('/api/socios', ['nombre' => 'Otro', 'email' => 'dup@test.com'])
             ->assertStatus(422);
    }

    public function test_permite_mismo_email_en_diferente_gimnasio(): void
    {
        $otroGimnasio = Gimnasio::factory()->create();
        Socio::factory()->create(['gimnasio_id' => $otroGimnasio->id, 'email' => 'shared@test.com']);

        $this->actingAs($this->admin)
             ->postJson('/api/socios', ['nombre' => 'Socio', 'email' => 'shared@test.com'])
             ->assertStatus(201);
    }

    public function test_admin_puede_editar_socio(): void
    {
        $socio = Socio::factory()->create(['gimnasio_id' => $this->gimnasio->id]);

        $this->actingAs($this->admin)
             ->putJson("/api/socios/{$socio->id}", ['nombre' => 'Nuevo Nombre'])
             ->assertStatus(200)
             ->assertJsonFragment(['nombre' => 'Nuevo Nombre']);
    }

    public function test_admin_puede_eliminar_socio(): void
    {
        $socio = Socio::factory()->create(['gimnasio_id' => $this->gimnasio->id]);

        $this->actingAs($this->admin)
             ->deleteJson("/api/socios/{$socio->id}")
             ->assertStatus(200);

        $this->assertSoftDeleted('socios', ['id' => $socio->id]);
    }

    public function test_entrenador_no_puede_eliminar_socio(): void
    {
        $socio = Socio::factory()->create(['gimnasio_id' => $this->gimnasio->id]);

        $this->actingAs($this->entrenador)
             ->deleteJson("/api/socios/{$socio->id}")
             ->assertStatus(403);
    }

    public function test_no_puede_acceder_sin_autenticar(): void
    {
        $this->getJson('/api/socios')->assertStatus(401);
    }
}
