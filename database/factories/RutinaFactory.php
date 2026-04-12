<?php

namespace Database\Factories;

use App\Models\Gimnasio;
use App\Models\Rutina;
use App\Models\Socio;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rutina>
 */
class RutinaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'gimnasio_id'   => Gimnasio::factory(),
            'socio_id'      => Socio::factory(),
            'entrenador_id' => User::factory(),
            'nombre'        => fake()->words(3, true),
            'descripcion'   => fake()->sentence(),
            'fecha_inicio'  => now()->format('Y-m-d'),
            'fecha_fin'     => now()->addMonths(2)->format('Y-m-d'),
            'activa'        => true,
        ];
    }
}
