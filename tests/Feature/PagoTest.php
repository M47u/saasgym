<?php

namespace Tests\Feature;

use App\Models\Gimnasio;
use App\Models\Pago;
use App\Models\Plan;
use App\Models\Socio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $entrenador;
    private Gimnasio $gimnasio;
    private Socio $socio;
    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gimnasio   = Gimnasio::factory()->create();
        $this->admin      = User::factory()->create(['gimnasio_id' => $this->gimnasio->id, 'rol' => 'admin']);
        $this->entrenador = User::factory()->create(['gimnasio_id' => $this->gimnasio->id, 'rol' => 'entrenador']);
        $this->plan       = Plan::create([
            'gimnasio_id'      => $this->gimnasio->id,
            'nombre'           => 'Plan mensual',
            'descripcion'      => null,
            'precio_efectivo'  => 5000,
            'precio_digital'   => 5500,
            'activo'           => true,
        ]);
        $this->socio      = Socio::factory()->create([
            'gimnasio_id' => $this->gimnasio->id,
            'plan_id'     => $this->plan->id,
        ]);
    }

    public function test_admin_puede_listar_pagos(): void
    {
        Pago::factory(2)->create(['gimnasio_id' => $this->gimnasio->id, 'socio_id' => $this->socio->id]);

        $this->actingAs($this->admin)
             ->getJson('/api/pagos')
             ->assertStatus(200)
             ->assertJsonCount(2, 'data');
    }

    public function test_entrenador_no_puede_ver_pagos(): void
    {
        $this->actingAs($this->entrenador)
             ->getJson('/api/pagos')
             ->assertStatus(403);
    }

    public function test_admin_puede_registrar_pago(): void
    {
        $response = $this->actingAs($this->admin)
                         ->postJson('/api/pagos', [
                             'socio_id'   => $this->socio->id,
                             'monto'      => 5000,
                             'fecha_pago' => '2026-03-01',
                             'metodo'     => 'efectivo',
                         ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['monto' => '5000.00']);
    }

    public function test_admin_puede_actualizar_pago(): void
    {
        $pago = Pago::factory()->create([
            'gimnasio_id' => $this->gimnasio->id,
            'socio_id'    => $this->socio->id,
            'monto'       => 1000,
        ]);

        $this->actingAs($this->admin)
             ->putJson("/api/pagos/{$pago->id}", ['monto' => 2000])
             ->assertStatus(200)
             ->assertJsonFragment(['monto' => '2000.00']);
    }

    public function test_admin_puede_eliminar_pago(): void
    {
        $pago = Pago::factory()->create([
            'gimnasio_id' => $this->gimnasio->id,
            'socio_id'    => $this->socio->id,
        ]);

        $this->actingAs($this->admin)
             ->deleteJson("/api/pagos/{$pago->id}")
             ->assertStatus(200);

        $this->assertSoftDeleted('pagos', ['id' => $pago->id]);
    }

    public function test_no_puede_registrar_pago_de_socio_de_otro_gimnasio(): void
    {
        $otroGimnasio = Gimnasio::factory()->create();
        $otroSocio    = Socio::factory()->create(['gimnasio_id' => $otroGimnasio->id]);

        $this->actingAs($this->admin)
             ->postJson('/api/pagos', [
                 'socio_id'   => $otroSocio->id,
                 'monto'      => 5000,
                 'fecha_pago' => '2026-03-01',
                 'metodo'     => 'efectivo',
             ])
             ->assertStatus(403);
    }

    public function test_no_puede_registrar_pago_si_socio_no_tiene_plan(): void
    {
        $socioSinPlan = Socio::factory()->create([
            'gimnasio_id' => $this->gimnasio->id,
            'plan_id'     => null,
        ]);

        $this->actingAs($this->admin)
             ->postJson('/api/pagos', [
                 'socio_id'   => $socioSinPlan->id,
                 'monto'      => 5000,
                 'fecha_pago' => '2026-03-01',
                 'metodo'     => 'efectivo',
             ])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['socio_id']);
    }
}
