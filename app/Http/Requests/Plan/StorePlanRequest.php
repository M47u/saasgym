<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $gimnasioId = $this->user()->gimnasio_id;

        return [
            'nombre'      => [
                'required', 'string', 'max:255',
                Rule::unique('planes', 'nombre')
                    ->where('gimnasio_id', $gimnasioId)
                    ->whereNull('deleted_at'),
            ],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'precio'      => ['nullable', 'numeric', 'min:0'],
            'activo'      => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe un plan con ese nombre en este gimnasio.',
        ];
    }
}
