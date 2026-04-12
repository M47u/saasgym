<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IA\ChatIaRequest;
use App\Http\Resources\ChatIaResource;
use App\Models\ChatIa;
use App\Models\Socio;
use App\Services\AiChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ChatIaController extends Controller
{
    public function __construct(private AiChatService $aiChatService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $chats = ChatIa::where('gimnasio_id', $request->user()->gimnasio_id)
            ->when($request->socio_id, fn($q) => $q->where('socio_id', $request->socio_id))
            ->latest()
            ->paginate(30);

        return ChatIaResource::collection($chats);
    }

    public function chat(ChatIaRequest $request): JsonResponse
    {
        $socio = Socio::findOrFail($request->socio_id);

        abort_if($socio->gimnasio_id !== $request->user()->gimnasio_id, 403, 'Sin autorización.');

        $resultado = $this->aiChatService->sendMessage($request->mensaje_usuario);

        $registro = ChatIa::create([
            'gimnasio_id'     => $request->user()->gimnasio_id,
            'socio_id'        => $request->socio_id,
            'mensaje_usuario' => $request->mensaje_usuario,
            'respuesta_ia'    => $resultado['respuesta'],
            'modelo_ia'       => $resultado['modelo'],
            'tokens_usados'   => $resultado['tokens_usados'],
        ]);

        return response()->json(new ChatIaResource($registro), 201);
    }
}
