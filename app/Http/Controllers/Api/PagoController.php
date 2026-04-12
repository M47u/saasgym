<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pago\StorePagoRequest;
use App\Http\Requests\Pago\UpdatePagoRequest;
use App\Http\Resources\PagoResource;
use App\Models\Pago;
use App\Models\Socio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PagoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $pagos = Pago::with('socio')
            ->where('gimnasio_id', $request->user()->gimnasio_id)
            ->when($request->socio_id, fn($q) => $q->where('socio_id', $request->socio_id))
            ->when($request->metodo, fn($q) => $q->where('metodo', $request->metodo))
            ->latest('fecha_pago')
            ->paginate(20);

        return PagoResource::collection($pagos);
    }

    public function store(StorePagoRequest $request): JsonResponse
    {
        $socio = Socio::findOrFail($request->socio_id);

        abort_if($socio->gimnasio_id !== $request->user()->gimnasio_id, 403, 'Sin autorización.');

        $pago = Pago::create([
            ...$request->validated(),
            'gimnasio_id' => $request->user()->gimnasio_id,
        ]);

        return response()->json(new PagoResource($pago->load('socio')), 201);
    }

    public function show(Request $request, Pago $pago): JsonResponse
    {
        abort_if($pago->gimnasio_id !== $request->user()->gimnasio_id, 403, 'Sin autorización.');

        return response()->json(new PagoResource($pago->load('socio')));
    }

    public function update(UpdatePagoRequest $request, Pago $pago): JsonResponse
    {
        abort_if($pago->gimnasio_id !== $request->user()->gimnasio_id, 403, 'Sin autorización.');

        $pago->update($request->validated());

        return response()->json(new PagoResource($pago->load('socio')));
    }

    public function destroy(Request $request, Pago $pago): JsonResponse
    {
        abort_if($pago->gimnasio_id !== $request->user()->gimnasio_id, 403, 'Sin autorización.');

        $pago->delete();

        return response()->json(['message' => 'Pago eliminado.']);
    }
}
