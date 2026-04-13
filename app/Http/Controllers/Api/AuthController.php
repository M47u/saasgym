<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Gimnasio;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $gimnasio = Gimnasio::create([
                'nombre'    => $request->gimnasio_nombre,
                'email'     => $request->gimnasio_email,
                'telefono'  => $request->gimnasio_telefono,
                'direccion' => $request->gimnasio_direccion,
                'activo'    => true,
            ]);

            return User::create([
                'gimnasio_id' => $gimnasio->id,
                'name'        => $request->admin_nombre,
                'email'       => $request->admin_email,
                'password'    => $request->admin_password,
                'rol'         => 'admin',
                'activo'      => true,
            ]);
        });

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => new UserResource($user),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Credenciales incorrectas.'], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $user->activo) {
            Auth::logout();
            return response()->json(['message' => 'Tu cuenta está desactivada.'], 403);
        }

        // For gym users, also verify the gimnasio is active
        if ($user->isGimnasioUser()) {
            $user->load('gimnasio');
            if ($user->gimnasio === null || ! $user->gimnasio->activo) {
                Auth::logout();
                return response()->json(['message' => 'El gimnasio está desactivado. Contactá al super administrador.'], 403);
            }
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => new UserResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(new UserResource($request->user()));
    }
}
