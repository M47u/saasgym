<?php

namespace App\Http\Requests\SuperAdmin;

use App\Models\Gimnasio;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGimnasioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        $gimnasio   = $this->route('gimnasio');
        $gimnasioId = $gimnasio instanceof Gimnasio ? $gimnasio->id : $gimnasio;

        // Obtener el admin principal del gimnasio para excluirlo de la validación de unicidad
        $adminId = User::where('gimnasio_id', $gimnasioId)
            ->where('rol', 'admin')
            ->orderBy('id')
            ->value('id');

        return [
            'nombre'    => ['sometimes', 'string', 'max:255'],
            'email'     => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('gimnasios', 'email')->ignore($gimnasioId),
                Rule::unique('users', 'email')->ignore($adminId),
            ],
            'telefono'  => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'activo'    => ['sometimes', 'boolean'],
            'admin_password'              => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
            'admin_password_confirmation' => ['sometimes', 'nullable', 'string', 'min:8'],
        ];
    }
}
