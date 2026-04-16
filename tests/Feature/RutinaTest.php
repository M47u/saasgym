<?php

namespace Tests\Feature;

use App\Models\Gimnasio;
use App\Models\Rutina;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RutinaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $entrenador;
    private User $entrenadorDos;
    private Gimnasio $gimnasio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gimnasio = Gimnasio::factory()->create();
        $this->admin    = User::factory()->create(['gimnasio_id' => $this->gimnasio->id, 'rol' => 'admin']);
        $this->entrenador = User::factory()->create(['gimnasio_id' => $this->gimnasio->id, 'rol' => 'entrenador']);
        $this->entrenadorDos = User::factory()->create(['gimnasio_id' => $this->gimnasio->id, 'rol' => 'entrenador']);
    }

    public function test_entrenador_crea_rutina_con_ejercicios(): void
    {
        $response = $this->actingAs($this->entrenador)
                         ->postJson('/api/rutinas', [
                             'nombre'      => 'Rutina Full Body',
                             'ejercicios'  => [
                                 ['nombre' => 'Sentadilla', 'series' => 4, 'repeticiones' => 12],
                                 ['nombre' => 'Press banca', 'series' => 3, 'repeticiones' => 10],
                             ],
                         ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['nombre' => 'Rutina Full Body'])
                 ->assertJsonCount(2, 'data.ejercicios');
    }

    public function test_admin_no_puede_crear_rutina(): void
    {
        $this->actingAs($this->admin)
             ->postJson('/api/rutinas', [
                 'nombre' => 'Rutina Admin',
             ])
             ->assertStatus(403);
    }

    public function test_admin_lista_todas_las_rutinas_del_gimnasio(): void
    {
        Rutina::factory(2)->create([
            'gimnasio_id'   => $this->gimnasio->id,
            'entrenador_id' => $this->entrenador->id,
        ]);

        Rutina::factory()->create([
            'gimnasio_id'   => $this->gimnasio->id,
            'entrenador_id' => $this->entrenadorDos->id,
        ]);

        $this->actingAs($this->admin)
             ->getJson('/api/rutinas')
             ->assertStatus(200)
             ->assertJsonCount(3, 'data');
    }

    public function test_entrenador_puede_actualizar_su_rutina(): void
    {
        $rutina = Rutina::factory()->create([
            'gimnasio_id'   => $this->gimnasio->id,
            'entrenador_id' => $this->entrenador->id,
            'activa'        => true,
        ]);

        $this->actingAs($this->entrenador)
             ->putJson("/api/rutinas/{$rutina->id}", ['activa' => false])
             ->assertStatus(200)
             ->assertJsonFragment(['activa' => false]);
    }

    public function test_entrenador_puede_eliminar_su_rutina(): void
    {
        $rutina = Rutina::factory()->create([
            'gimnasio_id'   => $this->gimnasio->id,
            'entrenador_id' => $this->entrenador->id,
        ]);

        $this->actingAs($this->entrenador)
             ->deleteJson("/api/rutinas/{$rutina->id}")
             ->assertStatus(200);

        $this->assertSoftDeleted('rutinas', ['id' => $rutina->id]);
    }

    public function test_no_accede_a_rutina_de_otro_gimnasio(): void
    {
        $otroGimnasio = Gimnasio::factory()->create();
        $otroUser     = User::factory()->create(['gimnasio_id' => $otroGimnasio->id, 'rol' => 'entrenador']);
        $rutina       = Rutina::factory()->create([
            'gimnasio_id'   => $otroGimnasio->id,
            'entrenador_id' => $otroUser->id,
        ]);

        $this->actingAs($this->admin)
             ->getJson("/api/rutinas/{$rutina->id}")
             ->assertStatus(403);
    }

    public function test_entrenador_solo_ve_sus_rutinas(): void
    {
        Rutina::factory()->create([
            'gimnasio_id'   => $this->gimnasio->id,
            'entrenador_id' => $this->entrenador->id,
        ]);

        Rutina::factory()->create([
            'gimnasio_id'   => $this->gimnasio->id,
            'entrenador_id' => $this->entrenadorDos->id,
        ]);

        $this->actingAs($this->entrenador)
             ->getJson('/api/rutinas')
             ->assertStatus(200)
             ->assertJsonCount(1, 'data');
    }

    public function test_entrenador_no_puede_ver_rutina_de_otro_entrenador(): void
    {
        $rutinaOtroEntrenador = Rutina::factory()->create([
            'gimnasio_id'   => $this->gimnasio->id,
            'entrenador_id' => $this->entrenadorDos->id,
        ]);

        $this->actingAs($this->entrenador)
             ->getJson("/api/rutinas/{$rutinaOtroEntrenador->id}")
             ->assertStatus(403);
    }

    public function test_admin_no_puede_actualizar_rutina(): void
    {
        $rutina = Rutina::factory()->create([
            'gimnasio_id'   => $this->gimnasio->id,
            'entrenador_id' => $this->entrenador->id,
        ]);

        $this->actingAs($this->admin)
             ->putJson("/api/rutinas/{$rutina->id}", ['nombre' => 'Cambio admin'])
             ->assertStatus(403);
    }
}
