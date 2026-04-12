<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RutinaEjercicioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'nombre'             => $this->nombre,
            'grupo_muscular'     => $this->grupo_muscular,
            'series'             => $this->series,
            'repeticiones'       => $this->repeticiones,
            'descanso_segundos'  => $this->descanso_segundos,
            'notas'              => $this->notas,
            'orden'              => $this->orden,
        ];
    }
}
