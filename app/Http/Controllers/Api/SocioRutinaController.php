<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RutinaResource;
use App\Models\Rutina;
use App\Models\Socio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SocioRutinaController extends Controller
{
    /**
     * GET /socios/{socio}/rutinas
     * Devuelve las rutinas asignadas al socio.
     * - Admin: ve todas.
     * - Entrenador: solo las que él creó.
     */
    public function index(Request $request, Socio $socio): AnonymousResourceCollection
    {
        abort_if($request->user()->gimnasio_id !== $socio->gimnasio_id, 403, 'Sin autorización.');

        $rutinas = $socio->rutinas()
            ->with(['entrenador', 'ejercicios'])
            ->when($request->user()->isEntrenador(), fn($q) => $q->where('entrenador_id', $request->user()->id))
            ->get();

        return RutinaResource::collection($rutinas);
    }

    /**
     * POST /socios/{socio}/rutinas/{rutina}
     * Asigna una rutina al socio. Solo el entrenador que creó la rutina puede asignarla.
     */
    public function store(Request $request, Socio $socio, Rutina $rutina): JsonResponse
    {
        $user = $request->user();

        abort_if($user->gimnasio_id !== $socio->gimnasio_id, 403, 'Sin autorización.');
        abort_if(! $user->isEntrenador(), 403, 'Solo los entrenadores pueden asignar rutinas.');
        abort_if($rutina->entrenador_id !== $user->id, 403, 'Solo podés asignar rutinas creadas por vos.');
        abort_if($rutina->gimnasio_id !== $user->gimnasio_id, 403, 'Sin autorización.');

        if ($socio->rutinas()->where('rutina_id', $rutina->id)->exists()) {
            return response()->json(['message' => 'La rutina ya está asignada a este socio.'], 422);
        }

        $socio->rutinas()->attach($rutina->id);

        return response()->json(['message' => 'Rutina asignada correctamente.'], 201);
    }

    /**
     * DELETE /socios/{socio}/rutinas/{rutina}
     * Quita la asignación de una rutina del socio.
     * - Entrenador: solo puede quitar rutinas que él creó.
     * - Admin: puede quitar cualquiera.
     */
    public function destroy(Request $request, Socio $socio, Rutina $rutina): JsonResponse
    {
        $user = $request->user();

        abort_if($user->gimnasio_id !== $socio->gimnasio_id, 403, 'Sin autorización.');
        abort_if($rutina->gimnasio_id !== $user->gimnasio_id, 403, 'Sin autorización.');

        if ($user->isEntrenador()) {
            abort_if($rutina->entrenador_id !== $user->id, 403, 'Solo podés quitar rutinas creadas por vos.');
        }

        $socio->rutinas()->detach($rutina->id);

        return response()->json(['message' => 'Rutina desasignada correctamente.']);
    }
}
