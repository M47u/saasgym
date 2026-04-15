<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\StorePlanRequest;
use App\Http\Requests\Plan\UpdatePlanRequest;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlanController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $planes = Plan::where('gimnasio_id', $request->user()->gimnasio_id)
            ->when($request->boolean('solo_activos'), fn($q) => $q->where('activo', true))
            ->orderBy('nombre')
            ->get();

        return PlanResource::collection($planes);
    }

    public function store(StorePlanRequest $request): JsonResponse
    {
        $plan = Plan::create([
            ...$request->validated(),
            'gimnasio_id' => $request->user()->gimnasio_id,
        ]);

        return response()->json(new PlanResource($plan), 201);
    }

    public function show(Request $request, Plan $plan): JsonResponse
    {
        $this->authorizeGimnasio($request, $plan->gimnasio_id);

        return response()->json(new PlanResource($plan));
    }

    public function update(UpdatePlanRequest $request, Plan $plan): JsonResponse
    {
        $this->authorizeGimnasio($request, $plan->gimnasio_id);

        $plan->update($request->validated());

        return response()->json(new PlanResource($plan));
    }

    public function destroy(Request $request, Plan $plan): JsonResponse
    {
        $this->authorizeGimnasio($request, $plan->gimnasio_id);

        $plan->delete();

        return response()->json(['message' => 'Plan eliminado.']);
    }

    private function authorizeGimnasio(Request $request, int $gimnasioId): void
    {
        abort_if($request->user()->gimnasio_id !== $gimnasioId, 403, 'Sin autorización.');
    }
}
