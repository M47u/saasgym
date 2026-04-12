<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Asistencia\StoreAsistenciaRequest;
use App\Http\Resources\AsistenciaResource;
use App\Models\Asistencia;
use App\Models\Socio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AsistenciaController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $asistencias = Asistencia::with('socio')
            ->where('gimnasio_id', $request->user()->gimnasio_id)
            ->when($request->socio_id, fn($q) => $q->where('socio_id', $request->socio_id))
            ->when($request->fecha, fn($q) => $q->whereDate('fecha_hora_entrada', $request->fecha))
            ->latest('fecha_hora_entrada')
            ->paginate(50);

        return AsistenciaResource::collection($asistencias);
    }

    public function store(StoreAsistenciaRequest $request): JsonResponse
    {
        $socio = Socio::findOrFail($request->socio_id);

        abort_if($socio->gimnasio_id !== $request->user()->gimnasio_id, 403, 'Sin autorización.');

        $asistencia = Asistencia::create([
            'gimnasio_id'        => $request->user()->gimnasio_id,
            'socio_id'           => $request->socio_id,
            'fecha_hora_entrada' => $request->fecha_hora_entrada ?? now(),
        ]);

        return response()->json(new AsistenciaResource($asistencia->load('socio')), 201);
    }
}
