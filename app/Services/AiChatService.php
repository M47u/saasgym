<?php

namespace App\Services;

/**
 * AiChatService
 *
 * Servicio preparado para integrar un proveedor de IA (OpenAI, Anthropic, etc.).
 * Por ahora retorna una respuesta simulada. Para activar IA real:
 *   1. Agregar la API key en .env (OPENAI_API_KEY, ANTHROPIC_API_KEY, etc.)
 *   2. Instalar el SDK correspondiente (composer require openai-php/client)
 *   3. Reemplazar el método sendMessage() con la llamada real a la API.
 */
class AiChatService
{
    /**
     * Envía un mensaje al proveedor de IA y retorna la respuesta.
     *
     * @return array{respuesta: string, tokens_usados: int|null, modelo: string}
     */
    public function sendMessage(string $mensaje, array $contexto = []): array
    {
        // --- INTEGRACIÓN FUTURA ---
        // Ejemplo con OpenAI:
        //
        // $client = \OpenAI::client(config('services.openai.key'));
        // $response = $client->chat()->create([
        //     'model'    => 'gpt-4o-mini',
        //     'messages' => [
        //         ['role' => 'system', 'content' => 'Eres un asistente de gimnasio.'],
        //         ['role' => 'user',   'content' => $mensaje],
        //     ],
        // ]);
        // return [
        //     'respuesta'     => $response->choices[0]->message->content,
        //     'tokens_usados' => $response->usage->totalTokens,
        //     'modelo'        => $response->model,
        // ];

        // Respuesta simulada para el MVP
        return [
            'respuesta'     => "Hola! Recibí tu mensaje: \"{$mensaje}\". Pronto estaré conectado a IA real.",
            'tokens_usados' => null,
            'modelo'        => 'simulado',
        ];
    }
}
