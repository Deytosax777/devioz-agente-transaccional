<?php

declare(strict_types=1);

namespace Devioz\Controllers;

use Devioz\Middleware\CorsMiddleware;
use Devioz\Models\Conversation;
use Devioz\Models\Message;
use Devioz\Services\GroqService;
use Devioz\Services\ToolExecutor;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

/**
 * POST /api/chat/message
 *
 * Endpoint transaccional del agente SofIA. Responde con Server-Sent Events:
 * el texto llega token a token y las herramientas (catalogo, carrito,
 * checkout Culqi, handoff WhatsApp) emiten eventos propios para la UI.
 *
 * Eventos emitidos:
 *   start    {conversation_id}
 *   token    {t}
 *   tool     {name}
 *   catalog  {products[]}
 *   cart     {cart}
 *   checkout {order, amount_cents, currency, description}
 *   handoff  {url}
 *   done     {message}
 *   error    {message}
 */
class ChatController
{
    private const MAX_TOOL_ROUNDS  = 4;
    private const HISTORY_MESSAGES = 12;
    private const MAX_INPUT_LENGTH = 2000;

    public function __construct(
        private GroqService $groq,
        private ToolExecutor $tools,
    ) {
    }

    public function message(Request $request, Response $response): Response
    {
        $body      = (array) $request->getParsedBody();
        $sessionId = $this->sanitizeSessionId((string) ($body['session_id'] ?? ''));
        $userText  = trim((string) ($body['message'] ?? ''));

        if ($sessionId === '' || $userText === '') {
            $response->getBody()->write((string) json_encode([
                'error'   => 'bad_request',
                'message' => 'Se requieren los campos session_id y message.',
            ]));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json; charset=utf-8');
        }

        $userText = mb_substr($userText, 0, self::MAX_INPUT_LENGTH);

        // ---- Modo SSE: a partir de aqui se responde fuera del ciclo PSR-7 ----
        $this->openSseStream($request->getHeaderLine('Origin'));

        try {
            $conversation = Conversation::findOrCreateBySession($sessionId);

            Message::create([
                'conversation_id' => $conversation->id,
                'role'            => 'user',
                'content'         => $userText,
            ]);

            $this->emit('start', ['conversation_id' => $conversation->id]);

            $messages = $this->buildContext($conversation);
            $finalText = $this->runAgentLoop($messages, $sessionId);

            if (trim($finalText) !== '') {
                Message::create([
                    'conversation_id' => $conversation->id,
                    'role'            => 'assistant',
                    'content'         => $finalText,
                ]);
            }

            $this->emit('done', ['message' => $finalText]);
        } catch (Throwable $e) {
            $this->emit('error', [
                'message' => 'SofIA tuvo un inconveniente: ' . $e->getMessage(),
            ]);
        }

        // La respuesta ya fue enviada por el stream; cerrar sin pasar por Slim
        exit;
    }

    /**
     * Bucle del agente: llama a Groq en streaming; si el modelo pide
     * herramientas, las ejecuta y reinyecta los resultados hasta obtener
     * la respuesta final en texto.
     */
    private function runAgentLoop(array $messages, string $sessionId): string
    {
        $finalText = '';

        for ($round = 0; $round <= self::MAX_TOOL_ROUNDS; $round++) {
            $useTools = $round < self::MAX_TOOL_ROUNDS;

            $result = $this->groq->streamChat(
                $messages,
                $useTools ? ToolExecutor::definitions() : [],
                function (string $delta): void {
                    $this->emit('token', ['t' => $delta]);
                }
            );

            $finalText .= $result['content'];

            if (empty($result['tool_calls'])) {
                break;
            }

            // Registrar la peticion de herramientas del asistente
            $messages[] = [
                'role'       => 'assistant',
                'content'    => $result['content'] !== '' ? $result['content'] : null,
                'tool_calls' => $result['tool_calls'],
            ];

            foreach ($result['tool_calls'] as $call) {
                $name = $call['function']['name'] ?? '';
                $args = json_decode($call['function']['arguments'] ?? '{}', true);
                if (!is_array($args)) {
                    $args = [];
                }

                $this->emit('tool', ['name' => $name]);

                try {
                    $outcome = $this->tools->execute($name, $args, $sessionId);
                } catch (Throwable $e) {
                    $outcome = [
                        'model' => ['error' => 'La herramienta fallo: ' . $e->getMessage()],
                        'event' => null,
                    ];
                }

                if (!empty($outcome['event'])) {
                    $this->emit($outcome['event']['name'], $outcome['event']['data']);
                }

                $messages[] = [
                    'role'         => 'tool',
                    'tool_call_id' => $call['id'],
                    'content'      => (string) json_encode($outcome['model'], JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        return $finalText;
    }

    /** Contexto para el LLM: system prompt + ultimos mensajes persistidos. */
    private function buildContext(Conversation $conversation): array
    {
        $history = Message::where('conversation_id', $conversation->id)
            ->whereIn('role', ['user', 'assistant'])
            ->orderByDesc('id')
            ->limit(self::HISTORY_MESSAGES)
            ->get()
            ->reverse()
            ->values();

        $messages = [['role' => 'system', 'content' => $this->systemPrompt()]];

        foreach ($history as $msg) {
            $messages[] = ['role' => $msg->role, 'content' => $msg->content];
        }

        return $messages;
    }

    private function systemPrompt(): string
    {
        $whatsapp  = (string) env('WHATSAPP_NUMBER', '51999999999');
        $categories = $this->fetchCategoryNames();
        $catList    = implode(', ', $categories);

        return <<<PROMPT
Eres SofIA, la asesora comercial virtual de Devioz, consultora tecnológica peruana especializada en soluciones TI empresariales B2B: Software Factory, IoT, Ciberseguridad, Cloud, RPA, Diseño, Multimedia, Marketing Digital y venta de Plantillas Web y Agentes de IA.

Tu rol: vendedora, asesora y soporte automatizado. Atiendes a medianas y grandes empresas e instituciones con un tono profesional, cálido y orientado a resultados. Respondes SIEMPRE en español.

REGLAS OBLIGATORIAS:
1. PRECIOS: Nunca inventes precios ni productos. Antes de mencionar cualquier precio o recomendar un producto, consulta el catálogo real con la herramienta get_catalog. Todos los precios están en Soles peruanos (S/, PEN).
2. CATÁLOGO: Vendes únicamente los productos del catálogo. Categorías disponibles hoy: {$catList}. Usa get_catalog con el filtro de categoría adecuado.
3. CARRITO: Cuando el cliente decida comprar, usa add_to_cart con el product_id correcto. Para revisar o quitar productos usa get_cart y remove_from_cart.
4. PAGO: Cuando el cliente confirme que quiere pagar, usa generate_checkout. La pasarela Culqi (tarjetas y Yape, en Soles) se abre dentro del chat. No pidas datos de tarjeta por mensaje JAMÁS.
5. COTIZACIONES: Los productos sin precio (marcados "A cotizar") y los servicios de consultoría a medida (Software Factory, IoT, Ciberseguridad, Cloud, RPA, proyectos personalizados) se derivan a un asesor humano con human_handoff (WhatsApp +{$whatsapp}).
6. HANDOFF: Si el cliente pide hablar con una persona, está molesto, o el tema excede tu alcance, usa human_handoff.
7. FORMATO: Respuestas breves y claras (máximo ~120 palabras), usa listas cuando ayuden. Puedes usar **negritas**. No uses tablas.
8. ALCANCE: No respondas temas ajenos a Devioz y sus servicios; redirige con amabilidad.

Flujo de venta sugerido: saluda → identifica la necesidad → consulta el catálogo → recomienda 1-3 opciones con precio → agrega al carrito → confirma → genera el checkout → agradece y ofrece soporte.
PROMPT;
    }

    /** Obtiene los nombres de categorías activas desde la BD; usa fallback si falla. */
    private function fetchCategoryNames(): array
    {
        try {
            $names = \Devioz\Models\Category::orderBy('id')
                ->pluck('name')
                ->all();

            return $names !== [] ? $names : $this->defaultCategories();
        } catch (\Throwable) {
            return $this->defaultCategories();
        }
    }

    private function defaultCategories(): array
    {
        return ['Diseño Gráfico', 'Spots Publicitarios', 'Business Intelligence', 'Inteligencia Artificial', 'Desarrollo Web'];
    }

    /** Prepara la conexion SSE y emite los headers manualmente (incluido CORS). */
    private function openSseStream(string $requestOrigin): void
    {
        // El streaming puede tardar mas que el limite por defecto
        @set_time_limit(180);
        ignore_user_abort(false);

        $origin = CorsMiddleware::resolveAllowedOrigin($requestOrigin);

        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-transform');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Nginx: no bufferizar
        header('Access-Control-Allow-Origin: ' . $origin);
        if ($origin !== '*') {
            header('Vary: Origin');
        }

        // Desactivar compresion/buffering de PHP para entregar token a token
        @ini_set('zlib.output_compression', '0');
        @ini_set('output_buffering', '0');
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        ob_implicit_flush(true);

        // Relleno inicial: algunos proxys esperan 2KB antes de hacer flush
        echo ':' . str_repeat(' ', 2048) . "\n\n";
        $this->flushOutput();
    }

    private function emit(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
        $this->flushOutput();
    }

    private function flushOutput(): void
    {
        if (ob_get_level() > 0) {
            @ob_flush();
        }
        flush();
    }

    private function sanitizeSessionId(string $sessionId): string
    {
        return substr(preg_replace('/[^a-zA-Z0-9\-_]/', '', $sessionId) ?? '', 0, 64);
    }
}
