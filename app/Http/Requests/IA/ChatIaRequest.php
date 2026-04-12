<?php

namespace App\Http\Requests\IA;

use Illuminate\Foundation\Http\FormRequest;

class ChatIaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'socio_id'        => ['required', 'integer', 'exists:socios,id'],
            'mensaje_usuario' => ['required', 'string', 'max:2000'],
        ];
    }
}
