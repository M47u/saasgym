<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AsistenciaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'socio_id'           => $this->socio_id,
            'socio'              => $this->whenLoaded('socio', fn() => [
                'id'     => $this->socio->id,
                'nombre' => $this->socio->nombre,
            ]),
            'fecha_hora_entrada' => $this->fecha_hora_entrada->toDateTimeString(),
            'gimnasio_id'        => $this->gimnasio_id,
        ];
    }
}
