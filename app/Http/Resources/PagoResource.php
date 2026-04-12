<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PagoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'socio_id'      => $this->socio_id,
            'socio'         => $this->whenLoaded('socio', fn() => [
                'id'     => $this->socio->id,
                'nombre' => $this->socio->nombre,
            ]),
            'monto'         => (float) $this->monto,
            'fecha_pago'    => $this->fecha_pago->toDateString(),
            'metodo'        => $this->metodo,
            'concepto'      => $this->concepto,
            'observaciones' => $this->observaciones,
            'gimnasio_id'   => $this->gimnasio_id,
            'created_at'    => $this->created_at->toDateTimeString(),
        ];
    }
}
