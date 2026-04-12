<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatIaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'socio_id'         => $this->socio_id,
            'mensaje_usuario'  => $this->mensaje_usuario,
            'respuesta_ia'     => $this->respuesta_ia,
            'modelo_ia'        => $this->modelo_ia,
            'tokens_usados'    => $this->tokens_usados,
            'created_at'       => $this->created_at->toDateTimeString(),
        ];
    }
}
