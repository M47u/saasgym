<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreGimnasioRequest;
use App\Http\Requests\SuperAdmin\UpdateGimnasioRequest;
use App\Http\Resources\GimnasioResource;
use App\Models\Gimnasio;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class GimnasioController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $gimnasios = Gimnasio::withCount(['usuarios', 'socios'])
            ->when(
                $request->search,
                fn($q) => $q->where('nombre', 'like', "%{$request->search}%")
                            ->orWhere('email', 'like', "%{$request->search}%")
            )
            ->when(
                $request->has('activo'),
                fn($q) => $q->where('activo', $request->boolean('activo'))
            )
            ->latest()
            ->paginate(20);

        return GimnasioResource::collection($gimnasios);
    }

    public function store(StoreGimnasioRequest $request): JsonResponse
    {
        $gimnasio = DB::transaction(function () use ($request) {
            $gimnasio = Gimnasio::create([
                'nombre'    => $request->nombre,
                'email'     => $request->email,
                'telefono'  => $request->telefono,
                'direccion' => $request->direccion,
                'activo'    => $request->boolean('activo', true),
            ]);

            User::create([
                'gimnasio_id' => $gimnasio->id,
                'name'        => $request->admin_nombre,
                'email'       => $request->email,
                'password'    => $request->admin_password,
                'rol'         => 'admin',
                'activo'      => true,
            ]);

            return $gimnasio;
        });

        return response()->json(new GimnasioResource($gimnasio), 201);
    }

    public function show(Gimnasio $gimnasio): JsonResponse
    {
        $gimnasio->loadCount(['usuarios', 'socios']);

        return response()->json(new GimnasioResource($gimnasio));
    }

    public function update(UpdateGimnasioRequest $request, Gimnasio $gimnasio): JsonResponse
    {
        DB::transaction(function () use ($request, $gimnasio) {
            $data = $request->validated();

            $gimnasioData = array_intersect_key($data, array_flip([
                'nombre',
                'email',
                'telefono',
                'direccion',
                'activo',
            ]));

            $gimnasio->update($gimnasioData);

            // El email del gimnasio y el del admin principal siempre van sincronizados
            $admin = $gimnasio->usuarios()
                ->where('rol', 'admin')
                ->orderBy('id')
                ->first();

            if ($admin) {
                $adminData = [];

                if (isset($data['email'])) {
                    $adminData['email'] = $data['email'];
                }

                if (! empty($data['admin_password'])) {
                    $adminData['password'] = $data['admin_password'];
                }

                if (! empty($adminData)) {
                    $admin->update($adminData);
                }
            }
        });

        return response()->json(new GimnasioResource($gimnasio->fresh()));
    }

    public function destroy(Gimnasio $gimnasio): JsonResponse
    {
        $gimnasio->delete(); // soft delete

        return response()->json(['message' => 'Gimnasio eliminado.']);
    }

    public function activar(Gimnasio $gimnasio): JsonResponse
    {
        $gimnasio->update(['activo' => true]);

        return response()->json(['message' => 'Gimnasio activado.', 'activo' => true]);
    }

    public function desactivar(Gimnasio $gimnasio): JsonResponse
    {
        $gimnasio->update(['activo' => false]);

        return response()->json(['message' => 'Gimnasio desactivado.', 'activo' => false]);
    }
}
