<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'nombre'           => $this->nombre,
            'email'            => $this->email,
            'telefono'         => $this->telefono,
            'estado'           => $this->estado,
            'fecha_nacimiento' => $this->fecha_nacimiento?->toDateString(),
            'gimnasio_id'      => $this->gimnasio_id,
            'created_at'       => $this->created_at->toDateTimeString(),
        ];
    }
}
