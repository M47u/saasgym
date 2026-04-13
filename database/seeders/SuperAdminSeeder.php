<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@saasgym.com'],
            [
                'gimnasio_id' => null,
                'name'        => 'Super Admin',
                'password'    => Hash::make('password'),
                'rol'         => 'super_admin',
                'activo'      => true,
            ]
        );
    }
}
