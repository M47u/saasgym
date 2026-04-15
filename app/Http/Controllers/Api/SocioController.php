<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Socio\StoreSocioRequest;
use App\Http\Requests\Socio\UpdateSocioRequest;
use App\Http\Resources\SocioResource;
use App\Models\Socio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SocioController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $socios = Socio::where('gimnasio_id', $request->user()->gimnasio_id)
            ->with(['ultimoPago', 'plan'])
            ->when($request->estado, fn($q) => $q->where('estado', $request->estado))
            ->when($request->search, fn($q) => $q->where('nombre', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(20);

        return SocioResource::collection($socios);
    }

    public function store(StoreSocioRequest $request): JsonResponse
    {
        $socio = Socio::create([
            ...$request->validated(),
            'gimnasio_id' => $request->user()->gimnasio_id,
        ]);

        return response()->json(new SocioResource($socio), 201);
    }

    public function show(Request $request, Socio $socio): JsonResponse
    {
        $this->authorizeGimnasio($request, $socio->gimnasio_id);

        $socio->load('plan');

        return response()->json(new SocioResource($socio));
    }

    public function update(UpdateSocioRequest $request, Socio $socio): JsonResponse
    {
        $this->authorizeGimnasio($request, $socio->gimnasio_id);

        $socio->update($request->validated());

        return response()->json(new SocioResource($socio));
    }

    public function destroy(Request $request, Socio $socio): JsonResponse
    {
        $this->authorizeGimnasio($request, $socio->gimnasio_id);

        $socio->delete();

        return response()->json(['message' => 'Socio eliminado.']);
    }

    private function authorizeGimnasio(Request $request, int $gimnasioId): void
    {
        abort_if($request->user()->gimnasio_id !== $gimnasioId, 403, 'Sin autorización.');
    }
}
