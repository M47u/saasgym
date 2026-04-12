<?php

namespace Database\Factories;

use App\Models\Gimnasio;
use App\Models\Socio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Socio>
 */
class SocioFactory extends Factory
{
    public function definition(): array
    {
        return [
            'gimnasio_id'      => Gimnasio::factory(),
            'nombre'           => fake()->name(),
            'email'            => fake()->unique()->safeEmail(),
            'telefono'         => fake()->phoneNumber(),
            'estado'           => 'activo',
            'fecha_nacimiento' => fake()->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
        ];
    }
}
