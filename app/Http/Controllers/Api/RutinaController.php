<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rutina\StoreRutinaRequest;
use App\Http\Requests\Rutina\UpdateRutinaRequest;
use App\Http\Resources\RutinaResource;
use App\Models\Rutina;
use App\Models\Socio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class RutinaController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $rutinas = Rutina::with(['socio', 'entrenador'])
            ->where('gimnasio_id', $request->user()->gimnasio_id)
            ->when($request->socio_id, fn($q) => $q->where('socio_id', $request->socio_id))
            ->when($request->activa !== null, fn($q) => $q->where('activa', $request->boolean('activa')))
            ->latest()
            ->paginate(20);

        return RutinaResource::collection($rutinas);
    }

    public function store(StoreRutinaRequest $request): JsonResponse
    {
        $socio = Socio::findOrFail($request->socio_id);

        abort_if($socio->gimnasio_id !== $request->user()->gimnasio_id, 403, 'Sin autorización.');

        $rutina = DB::transaction(function () use ($request) {
            $rutina = Rutina::create([
                'gimnasio_id'   => $request->user()->gimnasio_id,
                'socio_id'      => $request->socio_id,
                'entrenador_id' => $request->user()->id,
                'nombre'        => $request->nombre,
                'descripcion'   => $request->descripcion,
                'fecha_inicio'  => $request->fecha_inicio,
                'fecha_fin'     => $request->fecha_fin,
            ]);

            if ($request->has('ejercicios')) {
                foreach ($request->ejercicios as $index => $ejercicio) {
                    $rutina->ejercicios()->create([
                        ...$ejercicio,
                        'orden' => $ejercicio['orden'] ?? $index,
                    ]);
                }
            }

            return $rutina;
        });

        return response()->json(new RutinaResource($rutina->load(['socio', 'entrenador', 'ejercicios'])), 201);
    }

    public function show(Request $request, Rutina $rutina): JsonResponse
    {
        abort_if($rutina->gimnasio_id !== $request->user()->gimnasio_id, 403, 'Sin autorización.');

        return response()->json(new RutinaResource($rutina->load(['socio', 'entrenador', 'ejercicios'])));
    }

    public function update(UpdateRutinaRequest $request, Rutina $rutina): JsonResponse
    {
        abort_if($rutina->gimnasio_id !== $request->user()->gimnasio_id, 403, 'Sin autorización.');

        $rutina->update($request->validated());

        return response()->json(new RutinaResource($rutina->load(['socio', 'entrenador', 'ejercicios'])));
    }

    public function destroy(Request $request, Rutina $rutina): JsonResponse
    {
        abort_if($rutina->gimnasio_id !== $request->user()->gimnasio_id, 403, 'Sin autorización.');

        $rutina->delete();

        return response()->json(['message' => 'Rutina eliminada.']);
    }
}
