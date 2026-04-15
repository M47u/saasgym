<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGimnasioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'nombre'    => ['required', 'string', 'max:255'],
            'email'     => [
                'required',
                'email',
                'max:255',
                Rule::unique('gimnasios', 'email'),
                Rule::unique('users', 'email'),
            ],
            'telefono'  => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'activo'    => ['sometimes', 'boolean'],
            'admin_nombre'                => ['nullable', 'string', 'max:255'],
            'admin_password'              => ['required', 'string', 'min:8', 'confirmed'],
            'admin_password_confirmation' => ['required', 'string', 'min:8'],
        ];
    }
}
