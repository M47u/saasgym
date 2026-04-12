<?php

namespace App\Http\Requests\Asistencia;

use Illuminate\Foundation\Http\FormRequest;

class StoreAsistenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'socio_id'           => ['required', 'integer', 'exists:socios,id'],
            'fecha_hora_entrada' => ['sometimes', 'date_format:Y-m-d H:i:s'],
        ];
    }
}
