<?php

namespace Database\Factories;

use App\Models\Gimnasio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gimnasio>
 */
class GimnasioFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre'    => fake()->company() . ' Gym',
            'email'     => fake()->unique()->companyEmail(),
            'telefono'  => fake()->phoneNumber(),
            'direccion' => fake()->address(),
            'activo'    => true,
        ];
    }
}
