<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rutina\StoreRutinaRequest;
use App\Http\Requests\Rutina\UpdateRutinaRequest;
use App\Http\Resources\RutinaResource;
use App\Models\Rutina;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class RutinaController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $rutinas = Rutina::with(['entrenador', 'ejercicios'])
            ->where('gimnasio_id', $user->gimnasio_id)
            ->when($user->isEntrenador(), fn($q) => $q->where('entrenador_id', $user->id))
            ->when($request->activa !== null, fn($q) => $q->where('activa', $request->boolean('activa')))
            ->latest()
            ->paginate(20);

        return RutinaResource::collection($rutinas);
    }

    public function store(StoreRutinaRequest $request): JsonResponse
    {
        abort_if(! $request->user()->isEntrenador(), 403, 'Solo los entrenadores pueden crear rutinas.');

        $data = $request->validated();

        $rutina = DB::transaction(function () use ($request, $data) {
            $rutina = Rutina::create([
                'gimnasio_id'   => $request->user()->gimnasio_id,
                'entrenador_id' => $request->user()->id,
                'nombre'        => $data['nombre'],
                'descripcion'   => $data['descripcion'] ?? null,
                'activa'        => $data['activa'] ?? true,
            ]);

            if (!empty($data['ejercicios'])) {
                foreach ($data['ejercicios'] as $index => $ejercicio) {
                    $rutina->ejercicios()->create([
                        ...$ejercicio,
                        'orden' => $ejercicio['orden'] ?? $index,
                    ]);
                }
            }

            return $rutina;
        });

        return response()->json(new RutinaResource($rutina->load(['entrenador', 'ejercicios'])), 201);
    }

    public function show(Request $request, Rutina $rutina): JsonResponse
    {
        $this->authorizeRutinaView($request->user(), $rutina);

        return response()->json(new RutinaResource($rutina->load(['entrenador', 'ejercicios'])));
    }

    public function update(UpdateRutinaRequest $request, Rutina $rutina): JsonResponse
    {
        $this->authorizeRutinaManage($request->user(), $rutina);

        $data = $request->validated();

        DB::transaction(function () use ($rutina, $data) {
            $rutina->update([
                'nombre'      => $data['nombre'] ?? $rutina->nombre,
                'descripcion' => array_key_exists('descripcion', $data) ? $data['descripcion'] : $rutina->descripcion,
                'activa'      => $data['activa'] ?? $rutina->activa,
            ]);

            if (array_key_exists('ejercicios', $data)) {
                $rutina->ejercicios()->delete();

                foreach (($data['ejercicios'] ?? []) as $index => $ejercicio) {
                    $rutina->ejercicios()->create([
                        ...$ejercicio,
                        'orden' => $ejercicio['orden'] ?? $index,
                    ]);
                }
            }
        });

        return response()->json(new RutinaResource($rutina->load(['entrenador', 'ejercicios'])));
    }

    public function destroy(Request $request, Rutina $rutina): JsonResponse
    {
        $this->authorizeRutinaManage($request->user(), $rutina);

        $rutina->delete();

        return response()->json(['message' => 'Rutina eliminada.']);
    }

    private function authorizeRutinaView(User $user, Rutina $rutina): void
    {
        abort_if($rutina->gimnasio_id !== $user->gimnasio_id, 403, 'Sin autorización.');
        abort_if(! $user->isAdmin() && ! $user->isEntrenador(), 403, 'Sin autorización.');
        abort_if($user->isEntrenador() && $rutina->entrenador_id !== $user->id, 403, 'Sin autorización.');
    }

    private function authorizeRutinaManage(User $user, Rutina $rutina): void
    {
        abort_if($rutina->gimnasio_id !== $user->gimnasio_id, 403, 'Sin autorización.');
        abort_if(! $user->isEntrenador(), 403, 'Solo los entrenadores pueden modificar rutinas.');
        abort_if($rutina->entrenador_id !== $user->id, 403, 'Solo podés modificar rutinas creadas por vos.');
    }
}
