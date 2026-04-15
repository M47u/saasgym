<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'nombre'      => $this->nombre,
            'descripcion' => $this->descripcion,
            'precio'      => $this->precio !== null ? (float) $this->precio : null,
            'activo'      => $this->activo,
            'gimnasio_id' => $this->gimnasio_id,
            'created_at'  => $this->created_at->toDateTimeString(),
        ];
    }
}
