<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GimnasioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'nombre'      => $this->nombre,
            'email'       => $this->email,
            'telefono'    => $this->telefono,
            'direccion'   => $this->direccion,
            'activo'      => $this->activo,
            'created_at'  => $this->created_at->toDateString(),
            // Included when withCount() is used
            'usuarios_count' => $this->whenCounted('usuarios'),
            'socios_count'   => $this->whenCounted('socios'),
        ];
    }
}
