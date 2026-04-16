<?php

namespace App\Http\Requests\Rutina;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRutinaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'       => ['sometimes', 'string', 'max:255'],
            'descripcion'  => ['sometimes', 'nullable', 'string'],
            'activa'       => ['sometimes', 'boolean'],
            'ejercicios'            => ['sometimes', 'array'],
            'ejercicios.*.nombre'   => ['required_with:ejercicios', 'string', 'max:255'],
            'ejercicios.*.series'   => ['nullable', 'integer', 'min:1'],
            'ejercicios.*.repeticiones' => ['nullable', 'integer', 'min:1'],
            'ejercicios.*.descanso_segundos' => ['nullable', 'integer', 'min:0'],
            'ejercicios.*.grupo_muscular' => ['nullable', 'string', 'max:100'],
            'ejercicios.*.notas'    => ['nullable', 'string'],
            'ejercicios.*.orden'    => ['nullable', 'integer', 'min:0'],
        ];
    }
}
