<?php

namespace App\Http\Requests\Pago;

use Illuminate\Foundation\Http\FormRequest;

class StorePagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'socio_id'      => ['required', 'integer', 'exists:socios,id'],
            'monto'         => ['required', 'numeric', 'min:0.01'],
            'fecha_pago'    => ['required', 'date'],
            'metodo'        => ['required', 'in:efectivo,transferencia,tarjeta'],
            'concepto'      => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string'],
        ];
    }
}
