<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::where('gimnasio_id', $request->user()->gimnasio_id)
            ->when($request->rol, fn($q) => $q->where('rol', $request->rol))
            ->when($request->has('activo'), fn($q) => $q->where('activo', $request->boolean('activo')))
            ->latest()
            ->get();

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create([
            'gimnasio_id' => $request->user()->gimnasio_id,
            'name'        => $request->name,
            'email'       => $request->email,
            'password'    => $request->password,
            'rol'         => $request->rol ?? 'entrenador',
            'activo'      => true,
        ]);

        return response()->json(new UserResource($user), 201);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        abort_if($user->gimnasio_id !== $request->user()->gimnasio_id, 403, 'Sin autorización.');

        return response()->json(new UserResource($user));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        abort_if($user->gimnasio_id !== $request->user()->gimnasio_id, 403, 'Sin autorización.');

        $data = $request->validated();

        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }

        $user->update($data);

        return response()->json(new UserResource($user));
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        abort_if($user->gimnasio_id !== $request->user()->gimnasio_id, 403, 'Sin autorización.');
        abort_if($user->id === $request->user()->id, 422, 'No puedes eliminar tu propia cuenta.');

        $user->delete();

        return response()->json(['message' => 'Usuario eliminado.']);
    }
}
