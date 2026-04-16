<?php

namespace App\Http\Requests\Pago;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto'         => ['sometimes', 'numeric', 'min:0.01'],
            'fecha_pago'    => ['sometimes', 'date'],
            'metodo'        => ['sometimes', 'in:efectivo,transferencia,tarjeta'],
            'concepto'      => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string'],
        ];
    }
}
