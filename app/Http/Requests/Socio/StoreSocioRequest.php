<?php

namespace App\Http\Requests\Socio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSocioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $gimnasioId = $this->user()->gimnasio_id;

        return [
            'nombre'           => ['required', 'string', 'max:255'],
            'email'            => [
                'nullable', 'email', 'max:255',
                Rule::unique('socios', 'email')->where('gimnasio_id', $gimnasioId)->whereNull('deleted_at'),
            ],
            'telefono'         => ['nullable', 'string', 'max:30'],
            'estado'           => ['sometimes', 'in:activo,inactivo,suspendido'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
        ];
    }
}
