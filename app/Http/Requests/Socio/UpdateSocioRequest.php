<?php

namespace App\Http\Requests\Socio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSocioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $gimnasioId = $this->user()->gimnasio_id;
        $socioId    = $this->route('socio')->id;

        return [
            'nombre'           => ['sometimes', 'string', 'max:255'],
            'email'            => [
                'sometimes', 'nullable', 'email', 'max:255',
                Rule::unique('socios', 'email')
                    ->where('gimnasio_id', $gimnasioId)
                    ->ignore($socioId)
                    ->whereNull('deleted_at'),
            ],
            'telefono'         => ['sometimes', 'nullable', 'string', 'max:30'],
            'estado'           => ['sometimes', 'in:activo,inactivo,suspendido'],
            'fecha_nacimiento' => ['sometimes', 'nullable', 'date', 'before:today'],
            'plan_id'          => [
                'sometimes', 'nullable', 'integer',
                Rule::exists('planes', 'id')->where('gimnasio_id', $gimnasioId)->whereNull('deleted_at'),
            ],
        ];
    }
}
