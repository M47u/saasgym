<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Gimnasio
            'gimnasio_nombre'    => ['required', 'string', 'max:255'],
            'gimnasio_email'     => ['required', 'email', 'max:255', 'unique:gimnasios,email'],
            'gimnasio_telefono'  => ['nullable', 'string', 'max:30'],
            'gimnasio_direccion' => ['nullable', 'string', 'max:255'],

            // Admin
            'admin_nombre'    => ['required', 'string', 'max:255'],
            'admin_email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password'  => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function attributes(): array
    {
        return [
            'gimnasio_nombre'   => 'nombre del gimnasio',
            'gimnasio_email'    => 'email del gimnasio',
            'admin_nombre'      => 'nombre del administrador',
            'admin_email'       => 'email del administrador',
            'admin_password'    => 'contraseña',
        ];
    }
}
