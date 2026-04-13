<?php

namespace Database\Seeders;

use App\Models\Gimnasio;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SuperAdminSeeder::class);

        // Gimnasio de prueba
        $gimnasio = Gimnasio::create([
            'nombre'    => 'Gimnasio Demo',
            'email'     => 'gym@demo.com',
            'telefono'  => '2616000001',
            'direccion' => 'Av. Siempre Viva 123',
            'activo'    => true,
        ]);

        // Admin
        User::create([
            'gimnasio_id' => $gimnasio->id,
            'name'        => 'Administrador',
            'email'       => 'admin@gym.com',
            'password'    => Hash::make('password'),
            'rol'         => 'admin',
            'activo'      => true,
        ]);

        // Entrenador
        User::create([
            'gimnasio_id' => $gimnasio->id,
            'name'        => 'Juan Entrenador',
            'email'       => 'entrenador@gym.com',
            'password'    => Hash::make('password'),
            'rol'         => 'entrenador',
            'activo'      => true,
        ]);
    }
}
