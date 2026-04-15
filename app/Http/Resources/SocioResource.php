<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocioResource extends JsonResource
{
    private function calcularProximoPago(?string $fechaUltimoPago): ?string
    {
        if (! $fechaUltimoPago) {
            return null;
        }

        $proximo = Carbon::parse($fechaUltimoPago)->addMonthNoOverflow();

        // Si cae sábado/domingo, mover al siguiente día hábil.
        while ($proximo->isWeekend()) {
            $proximo->addDay();
        }

        return $proximo->toDateString();
    }

    public function toArray(Request $request): array
    {
        $fechaUltimoPago = $this->relationLoaded('ultimoPago')
            ? $this->ultimoPago?->fecha_pago?->toDateString()
            : null;

        return [
            'id'               => $this->id,
            'nombre'           => $this->nombre,
            'email'            => $this->email,
            'telefono'         => $this->telefono,
            'estado'           => $this->estado,
            'fecha_nacimiento' => $this->fecha_nacimiento?->toDateString(),
            'gimnasio_id'      => $this->gimnasio_id,
            'plan_id'          => $this->plan_id,
            'plan'             => $this->whenLoaded('plan', fn() => $this->plan ? [
                'id'              => $this->plan->id,
                'nombre'          => $this->plan->nombre,
                'precio_efectivo' => $this->plan->precio_efectivo !== null ? (float) $this->plan->precio_efectivo : null,
                'precio_digital'  => $this->plan->precio_digital  !== null ? (float) $this->plan->precio_digital  : null,
                'activo'          => $this->plan->activo,
            ] : null),
            'created_at'       => $this->created_at->toDateTimeString(),
            'fecha_ultimo_pago'  => $fechaUltimoPago,
            'fecha_proximo_pago' => $this->calcularProximoPago($fechaUltimoPago),
        ];
    }
}
