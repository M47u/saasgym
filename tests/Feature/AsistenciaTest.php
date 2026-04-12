<?php

namespace Tests\Feature;

use App\Models\Gimnasio;
use App\Models\Socio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsistenciaTest extends TestCase
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

    public function test_registra_asistencia_con_timestamp_automatico(): void
    {
        $response = $this->actingAs($this->admin)
                         ->postJson('/api/asistencias', [
                             'socio_id' => $this->socio->id,
                         ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['data' => ['id', 'socio_id', 'fecha_hora_entrada']]);

        $this->assertDatabaseHas('asistencias', ['socio_id' => $this->socio->id]);
    }

    public function test_registra_asistencia_con_timestamp_manual(): void
    {
        $response = $this->actingAs($this->admin)
                         ->postJson('/api/asistencias', [
                             'socio_id'           => $this->socio->id,
                             'fecha_hora_entrada' => '2026-03-01 09:00:00',
                         ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['fecha_hora_entrada' => '2026-03-01 09:00:00']);
    }

    public function test_lista_asistencias_filtradas_por_socio(): void
    {
        $otroSocio = Socio::factory()->create(['gimnasio_id' => $this->gimnasio->id]);

        $this->actingAs($this->admin)->postJson('/api/asistencias', ['socio_id' => $this->socio->id]);
        $this->actingAs($this->admin)->postJson('/api/asistencias', ['socio_id' => $otroSocio->id]);

        $this->actingAs($this->admin)
             ->getJson("/api/asistencias?socio_id={$this->socio->id}")
             ->assertStatus(200)
             ->assertJsonCount(1, 'data');
    }

    public function test_no_registra_asistencia_de_socio_de_otro_gimnasio(): void
    {
        $otroGimnasio = Gimnasio::factory()->create();
        $otroSocio    = Socio::factory()->create(['gimnasio_id' => $otroGimnasio->id]);

        $this->actingAs($this->admin)
             ->postJson('/api/asistencias', ['socio_id' => $otroSocio->id])
             ->assertStatus(403);
    }
}
