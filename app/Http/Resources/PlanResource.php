<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'nombre'          => $this->nombre,
            'descripcion'     => $this->descripcion,
            'precio_efectivo' => $this->precio_efectivo !== null ? (float) $this->precio_efectivo : null,
            'precio_digital'  => $this->precio_digital  !== null ? (float) $this->precio_digital  : null,
            'activo'          => $this->activo,
            'gimnasio_id'     => $this->gimnasio_id,
            'created_at'      => $this->created_at->toDateTimeString(),
        ];
    }
}
