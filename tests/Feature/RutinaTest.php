<?php

namespace Tests\Feature;

use App\Models\Gimnasio;
use App\Models\Rutina;
use App\Models\Socio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RutinaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Gimnasio $gimnasio;
    private Socio $socio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gimnasio = Gimnasio::factory()->create();
        $this->admin    = User::factory()->create(['gimnasio_id' => $this->gimnasio->id, 'rol' => 'admin']);
        $this->socio    = Socio::factory()->create(['gimnasio_id' => $this->gimnasio->id]);
    }

    public function test_crea_rutina_con_ejercicios(): void
    {
        $response = $this->actingAs($this->admin)
                         ->postJson('/api/rutinas', [
                             'socio_id'    => $this->socio->id,
                             'nombre'      => 'Rutina Full Body',
                             'fecha_inicio'=> '2026-04-01',
                             'ejercicios'  => [
                                 ['nombre' => 'Sentadilla', 'series' => 4, 'repeticiones' => 12],
                                 ['nombre' => 'Press banca', 'series' => 3, 'repeticiones' => 10],
                             ],
                         ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['nombre' => 'Rutina Full Body'])
                 ->assertJsonCount(2, 'data.ejercicios');
    }

    public function test_lista_rutinas_del_gimnasio(): void
    {
        Rutina::factory(3)->create([
            'gimnasio_id'   => $this->gimnasio->id,
            'socio_id'      => $this->socio->id,
            'entrenador_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
             ->getJson('/api/rutinas')
             ->assertStatus(200)
             ->assertJsonCount(3, 'data');
    }

    public function test_puede_actualizar_rutina(): void
    {
        $rutina = Rutina::factory()->create([
            'gimnasio_id'   => $this->gimnasio->id,
            'socio_id'      => $this->socio->id,
            'entrenador_id' => $this->admin->id,
            'activa'        => true,
        ]);

        $this->actingAs($this->admin)
             ->putJson("/api/rutinas/{$rutina->id}", ['activa' => false])
             ->assertStatus(200)
             ->assertJsonFragment(['activa' => false]);
    }

    public function test_elimina_rutina_con_soft_delete(): void
    {
        $rutina = Rutina::factory()->create([
            'gimnasio_id'   => $this->gimnasio->id,
            'socio_id'      => $this->socio->id,
            'entrenador_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
             ->deleteJson("/api/rutinas/{$rutina->id}")
             ->assertStatus(200);

        $this->assertSoftDeleted('rutinas', ['id' => $rutina->id]);
    }

    public function test_no_accede_a_rutina_de_otro_gimnasio(): void
    {
        $otroGimnasio = Gimnasio::factory()->create();
        $otroUser     = User::factory()->create(['gimnasio_id' => $otroGimnasio->id]);
        $otroSocio    = Socio::factory()->create(['gimnasio_id' => $otroGimnasio->id]);
        $rutina       = Rutina::factory()->create([
            'gimnasio_id'   => $otroGimnasio->id,
            'socio_id'      => $otroSocio->id,
            'entrenador_id' => $otroUser->id,
        ]);

        $this->actingAs($this->admin)
             ->getJson("/api/rutinas/{$rutina->id}")
             ->assertStatus(403);
    }
}
