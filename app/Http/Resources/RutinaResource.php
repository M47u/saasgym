<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RutinaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'nombre'       => $this->nombre,
            'descripcion'  => $this->descripcion,
            'activa'       => $this->activa,
            'entrenador_id' => $this->entrenador_id,
            'entrenador'    => $this->whenLoaded('entrenador', fn() => [
                'id'     => $this->entrenador->id,
                'nombre' => $this->entrenador->name,
            ]),
            'ejercicios'   => RutinaEjercicioResource::collection($this->whenLoaded('ejercicios')),
            'gimnasio_id'  => $this->gimnasio_id,
            'created_at'   => $this->created_at->toDateTimeString(),
        ];
    }
}
