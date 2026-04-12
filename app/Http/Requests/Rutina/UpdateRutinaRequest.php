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
            'fecha_inicio' => ['sometimes', 'nullable', 'date'],
            'fecha_fin'    => ['sometimes', 'nullable', 'date', 'after_or_equal:fecha_inicio'],
            'activa'       => ['sometimes', 'boolean'],
        ];
    }
}
