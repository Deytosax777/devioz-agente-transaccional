<?php

declare(strict_types=1);

namespace Devioz\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

/**
 * Cliente de la API de Groq (endpoint compatible con OpenAI).
 * Soporta streaming de tokens y acumulacion de tool_calls (function calling).
 */
class GroqService
{
    private const API_URL = 'https://api.groq.com/openai/v1/chat/completions';

    private Client $http;
    private string $apiKey;
    private string $model;
    private int $maxTokens;
    private float $temperature;

    public function __construct()
    {
        $this->apiKey      = (string) env('GROQ_API_KEY', '');
        $this->model       = (string) env('GROQ_MODEL', 'llama-3.3-70b-versatile');
        $this->maxTokens   = (int) env('GROQ_MAX_TOKENS', 1024);
        $this->temperature = (float) env('GROQ_TEMPERATURE', 0.4);

        $this->http = new Client([
            'timeout'         => 90,
            'connect_timeout' => 10,
        ]);
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '' && !str_contains($this->apiKey, 'xxxx');
    }

    /**
     * Ejecuta una completion en streaming.
     *
     * @param array         $messages Historial en formato OpenAI/Groq.
     * @param array         $tools    Definiciones JSON Schema de herramientas.
     * @param callable|null $onToken  fn(string $delta) invocado por cada token de texto.
     *
     * @return array{content: string, tool_calls: array, finish_reason: ?string}
     */
    public function streamChat(array $messages, array $tools, ?callable $onToken = null): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('GROQ_API_KEY no esta configurada en el archivo .env');
        }

        $payload = [
            'model'       => $this->model,
            'messages'    => $messages,
            'max_tokens'  => $this->maxTokens,
            'temperature' => $this->temperature,
            'stream'      => true,
        ];

        if ($tools !== []) {
            $payload['tools']       = $tools;
            $payload['tool_choice'] = 'auto';
        }

        try {
            $response = $this->http->post(self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'text/event-stream',
                ],
                'json'   => $payload,
                'stream' => true,
            ]);
        } catch (GuzzleException $e) {
            throw new RuntimeException('Error al conectar con Groq: ' . $e->getMessage(), 0, $e);
        }

        $body         = $response->getBody();
        $buffer       = '';
        $content      = '';
        $toolCalls    = [];
        $finishReason = null;

        while (!$body->eof()) {
            $buffer .= $body->read(8192);

            // Procesar lineas completas del stream SSE de Groq
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line   = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);

                if ($line === '' || !str_starts_with($line, 'data:')) {
                    continue;
                }

                $data = trim(substr($line, 5));
                if ($data === '[DONE]') {
                    break 2;
                }

                $chunk = json_decode($data, true);
                if (!is_array($chunk)) {
                    continue;
                }

                $choice = $chunk['choices'][0] ?? null;
                if ($choice === null) {
                    continue;
                }

                if (!empty($choice['finish_reason'])) {
                    $finishReason = $choice['finish_reason'];
                }

                $delta = $choice['delta'] ?? [];

                if (isset($delta['content']) && $delta['content'] !== '') {
                    $content .= $delta['content'];
                    if ($onToken !== null) {
                        $onToken($delta['content']);
                    }
                }

                // Acumular tool_calls parciales indexados por posicion
                foreach ($delta['tool_calls'] ?? [] as $tc) {
                    $idx = $tc['index'] ?? 0;

                    if (!isset($toolCalls[$idx])) {
                        $toolCalls[$idx] = [
                            'id'       => $tc['id'] ?? ('call_' . $idx),
                            'type'     => 'function',
                            'function' => ['name' => '', 'arguments' => ''],
                        ];
                    }
                    if (!empty($tc['id'])) {
                        $toolCalls[$idx]['id'] = $tc['id'];
                    }
                    if (!empty($tc['function']['name'])) {
                        $toolCalls[$idx]['function']['name'] .= $tc['function']['name'];
                    }
                    if (isset($tc['function']['arguments'])) {
                        $toolCalls[$idx]['function']['arguments'] .= $tc['function']['arguments'];
                    }
                }
            }
        }

        return [
            'content'       => $content,
            'tool_calls'    => array_values($toolCalls),
            'finish_reason' => $finishReason,
        ];
    }
}
