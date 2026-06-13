<?php

declare(strict_types=1);

namespace Devioz\Controllers;

use Devioz\Models\Order;
use Devioz\Models\WebhookEvent;
use Devioz\Services\CulqiPaymentService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * POST /api/webhooks/culqi
 *
 * NUNCA se confia ciegamente en el payload recibido:
 *  1. Si hay CULQI_WEBHOOK_SECRET configurado, se valida la firma HMAC.
 *  2. Ademas, el cargo se re-consulta server-to-server en la API de Culqi
 *     y solo se actualiza la orden con los datos confirmados por la API.
 * Esto evita ataques de suplantacion de pagos exitosos.
 */
class WebhookController
{
    public function __construct(private CulqiPaymentService $culqi)
    {
    }

    public function culqi(Request $request, Response $response): Response
    {
        $rawBody = (string) $request->getBody();

        $signature = $request->getHeaderLine('Culqi-Signature');
        if ($signature === '') {
            $signature = $request->getHeaderLine('X-Culqi-Signature');
        }

        $signatureValid = $this->culqi->verifyWebhookSignature($rawBody, $signature !== '' ? $signature : null);
        $hasSecret      = (string) env('CULQI_WEBHOOK_SECRET', '') !== '';

        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            return $this->json($response, ['received' => false, 'message' => 'Payload inválido'], 400);
        }

        // Culqi puede enviar "data" como objeto o como JSON serializado en string
        $data = $payload['data'] ?? [];
        if (is_string($data)) {
            $data = json_decode($data, true) ?: [];
        }

        $eventType = (string) ($payload['type'] ?? $payload['object'] ?? 'unknown');
        $chargeId  = (string) ($data['id'] ?? '');

        $event = WebhookEvent::create([
            'provider'        => 'culqi',
            'event_type'      => $eventType,
            'external_id'     => $chargeId !== '' ? $chargeId : null,
            'payload'         => $rawBody,
            'signature_valid' => $signatureValid,
            'processed'       => false,
        ]);

        // Firma invalida con secreto configurado => rechazar de plano
        if ($hasSecret && !$signatureValid) {
            $event->update(['notes' => 'Firma HMAC inválida: evento rechazado']);
            return $this->json($response, ['received' => false, 'message' => 'Firma inválida'], 401);
        }

        if ($chargeId === '' || !str_starts_with($chargeId, 'chr_')) {
            $event->update(['notes' => 'Evento sin charge id procesable']);
            return $this->json($response, ['received' => true, 'message' => 'Evento registrado sin acción']);
        }

        // Confirmacion server-to-server: la fuente de verdad es la API de Culqi
        $charge = $this->culqi->getCharge($chargeId);
        if ($charge === null) {
            $event->update(['notes' => 'El cargo no existe en la API de Culqi: posible suplantación']);
            return $this->json($response, ['received' => false, 'message' => 'Cargo no verificable'], 400);
        }

        $orderCode = (string) ($charge['metadata']['order_code'] ?? '');

        /** @var Order|null $order */
        $order = null;
        if ($orderCode !== '') {
            $order = Order::where('code', $orderCode)->first();
        }
        if ($order === null) {
            $order = Order::where('culqi_charge_id', $chargeId)->first();
        }

        if ($order === null) {
            $event->update(['notes' => 'Sin orden asociada al cargo ' . $chargeId]);
            return $this->json($response, ['received' => true, 'message' => 'Cargo sin orden asociada']);
        }

        $this->reconcile($order, $charge, $eventType);

        $event->update(['processed' => true, 'notes' => 'Orden ' . $order->code . ' conciliada']);

        return $this->json($response, ['received' => true]);
    }

    /** Actualiza la orden de forma idempotente segun el estado real del cargo. */
    private function reconcile(Order $order, array $charge, string $eventType): void
    {
        Capsule::connection()->transaction(function () use ($order, $charge, $eventType) {
            /** @var Order $locked */
            $locked = Order::where('id', $order->id)->lockForUpdate()->first();

            $amountMatches = (int) ($charge['amount'] ?? -1) === $locked->amountInCents();
            $isPaid        = ($charge['outcome']['type'] ?? '') === 'venta_exitosa'
                || (bool) ($charge['paid'] ?? false)
                || (($charge['state'] ?? '') === 'paid');

            if (str_contains($eventType, 'refund') || ($charge['state'] ?? '') === 'refunded') {
                $locked->update(['status' => Order::STATUS_REFUNDED]);
                return;
            }

            if ($isPaid && $amountMatches && $locked->status !== Order::STATUS_PAID) {
                $locked->update([
                    'status'          => Order::STATUS_PAID,
                    'culqi_charge_id' => $charge['id'],
                    'customer_email'  => $locked->customer_email ?? ($charge['email'] ?? null),
                    'paid_at'         => date('Y-m-d H:i:s'),
                ]);
                return;
            }

            if (!$isPaid && $locked->status === Order::STATUS_PENDING) {
                $locked->update(['status' => Order::STATUS_FAILED]);
            }
        });
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write((string) json_encode($data, JSON_UNESCAPED_UNICODE));

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
