<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pago\StorePagoRequest;
use App\Http\Requests\Pago\UpdatePagoRequest;
use App\Http\Resources\PagoResource;
use App\Models\Pago;
use App\Models\Socio;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

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

        $fechaPagoSolicitada = Carbon::parse($request->fecha_pago)->startOfMonth();

        $ultimoPago = Pago::where('gimnasio_id', $request->user()->gimnasio_id)
            ->where('socio_id', $request->socio_id)
            ->latest('fecha_pago')
            ->first();

        if ($ultimoPago) {
            $ultimoMesPagado = Carbon::parse($ultimoPago->fecha_pago)->startOfMonth();

            // Solo bloqueamos si ya existe un pago en el mismo mes o posterior.
            if ($fechaPagoSolicitada->lessThanOrEqualTo($ultimoMesPagado)) {
                throw ValidationException::withMessages([
                    'fecha_pago' => [
                        sprintf(
                            'El socio ya tiene pago registrado hasta %s. Para pagar meses posteriores, seleccioná una fecha de un mes siguiente.',
                            $ultimoMesPagado->locale('es')->translatedFormat('F Y')
                        ),
                    ],
                ]);
            }
        }

        $data = $request->validated();

        $pago = Pago::create([
            ...$data,
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

        $data = $request->validated();

        $pago->update($data);

        return response()->json(new PagoResource($pago->load('socio')));
    }

    public function destroy(Request $request, Pago $pago): JsonResponse
    {
        abort_if($pago->gimnasio_id !== $request->user()->gimnasio_id, 403, 'Sin autorización.');

        $pago->delete();

        return response()->json(['message' => 'Pago eliminado.']);
    }
}
