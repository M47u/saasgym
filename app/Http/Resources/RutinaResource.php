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
            'fecha_inicio' => $this->fecha_inicio?->toDateString(),
            'fecha_fin'    => $this->fecha_fin?->toDateString(),
            'activa'       => $this->activa,
            'socio_id'     => $this->socio_id,
            'socio'        => $this->whenLoaded('socio', fn() => [
                'id'     => $this->socio->id,
                'nombre' => $this->socio->nombre,
            ]),
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
