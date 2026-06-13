<?php

declare(strict_types=1);

namespace Devioz\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

/**
 * Integracion con la API de Culqi v2 (cargos en Soles: tarjetas y Yape)
 * y validacion de autenticidad de webhooks.
 */
class CulqiPaymentService
{
    private const API_BASE = 'https://api.culqi.com/v2';

    private Client $http;
    private string $secretKey;
    private string $publicKey;
    private string $webhookSecret;

    public function __construct()
    {
        $this->secretKey     = (string) env('CULQI_SECRET_KEY', '');
        $this->publicKey     = (string) env('CULQI_PUBLIC_KEY', '');
        $this->webhookSecret = (string) env('CULQI_WEBHOOK_SECRET', '');

        $this->http = new Client([
            'base_uri'        => self::API_BASE . '/',
            'timeout'         => 30,
            'connect_timeout' => 10,
        ]);
    }

    public function publicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Crea un cargo en Culqi a partir de un token generado por Culqi Checkout v4.
     *
     * @param int $amountCents Monto en centimos de Sol (S/ 10.00 => 1000).
     *
     * @throws RuntimeException con mensaje legible si Culqi rechaza el cargo.
     */
    public function createCharge(
        string $tokenId,
        int $amountCents,
        string $email,
        string $description,
        array $metadata = []
    ): array {
        if ($this->secretKey === '' || str_contains($this->secretKey, 'xxxx')) {
            throw new RuntimeException('CULQI_SECRET_KEY no esta configurada en el archivo .env');
        }

        try {
            $response = $this->http->post('charges', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->secretKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'amount'        => $amountCents,
                    'currency_code' => 'PEN',
                    'email'         => $email,
                    'source_id'     => $tokenId,
                    'description'   => mb_substr($description, 0, 80),
                    'metadata'      => $metadata,
                ],
            ]);
        } catch (ClientException $e) {
            // Culqi responde 4xx con un JSON {user_message, merchant_message}
            $body    = (string) $e->getResponse()->getBody();
            $decoded = json_decode($body, true);
            $message = $decoded['user_message']
                ?? $decoded['merchant_message']
                ?? 'El pago fue rechazado por la pasarela.';
            throw new RuntimeException($message, 0, $e);
        } catch (GuzzleException $e) {
            throw new RuntimeException('No se pudo conectar con Culqi: ' . $e->getMessage(), 0, $e);
        }

        $charge = json_decode((string) $response->getBody(), true);
        if (!is_array($charge) || empty($charge['id'])) {
            throw new RuntimeException('Respuesta invalida de Culqi al crear el cargo.');
        }

        return $charge;
    }

    /**
     * Consulta un cargo directamente en la API de Culqi.
     * Se usa para confirmar server-to-server la autenticidad de los webhooks.
     */
    public function getCharge(string $chargeId): ?array
    {
        try {
            $response = $this->http->get('charges/' . rawurlencode($chargeId), [
                'headers' => ['Authorization' => 'Bearer ' . $this->secretKey],
            ]);
        } catch (GuzzleException) {
            return null;
        }

        $charge = json_decode((string) $response->getBody(), true);

        return is_array($charge) ? $charge : null;
    }

    /**
     * Valida la firma HMAC-SHA256 del webhook contra el secreto compartido.
     * Devuelve false si no hay secreto configurado o la firma no coincide.
     */
    public function verifyWebhookSignature(string $rawBody, ?string $signatureHeader): bool
    {
        if ($this->webhookSecret === '' || $signatureHeader === null || $signatureHeader === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $this->webhookSecret);

        // Algunos emisores envian la firma con prefijo "sha256="
        $received = str_starts_with($signatureHeader, 'sha256=')
            ? substr($signatureHeader, 7)
            : $signatureHeader;

        return hash_equals($expected, strtolower(trim($received)));
    }
}
