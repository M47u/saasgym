<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $gimnasioId = $this->user()->gimnasio_id;
        $planId     = $this->route('plan')?->id;

        return [
            'nombre'      => [
                'sometimes', 'string', 'max:255',
                Rule::unique('planes', 'nombre')
                    ->where('gimnasio_id', $gimnasioId)
                    ->whereNull('deleted_at')
                    ->ignore($planId),
            ],
            'descripcion'     => ['sometimes', 'nullable', 'string', 'max:2000'],
            'precio_efectivo' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'precio_digital'  => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'activo'          => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe un plan con ese nombre en este gimnasio.',
        ];
    }
}
