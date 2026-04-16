<?php

namespace Database\Factories;

use App\Models\Gimnasio;
use App\Models\Rutina;
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
            'entrenador_id' => User::factory(),
            'nombre'        => fake()->words(3, true),
            'descripcion'   => fake()->sentence(),
            'activa'        => true,
        ];
    }
}
