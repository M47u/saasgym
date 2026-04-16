<?php

namespace Database\Factories;

use App\Models\Gimnasio;
use App\Models\Pago;
use App\Models\Socio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pago>
 */
class PagoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'gimnasio_id'   => Gimnasio::factory(),
            'socio_id'      => Socio::factory(),
            'monto'         => fake()->randomFloat(2, 500, 10000),
            'fecha_pago'    => fake()->dateThisYear()->format('Y-m-d'),
            'metodo'        => fake()->randomElement(['efectivo', 'transferencia', 'tarjeta']),
            'concepto'      => 'Cuota mensual',
            'observaciones' => null,
        ];
    }
}
