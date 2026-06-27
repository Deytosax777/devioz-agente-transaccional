<?php

declare(strict_types=1);

namespace Devioz\Controllers;

use Devioz\Models\Cart;
use Devioz\Models\Conversation;
use Devioz\Models\Message;
use Devioz\Models\Order;
use Devioz\Services\CulqiPaymentService;
use Devioz\Services\EmailService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Throwable;

/**
 * Procesa el pago de una orden con el token generado por Culqi Checkout v4
 * en el widget. El monto SIEMPRE se toma de la orden en BD, nunca del cliente.
 */
class PaymentController
{
    public function __construct(
        private CulqiPaymentService $culqi,
        private EmailService $email,
    ) {
    }

    /** GET /api/config — configuracion publica para el widget. */
    public function config(Request $request, Response $response): Response
    {
        return $this->json($response, [
            'culqi_public_key' => $this->culqi->publicKey(),
            'whatsapp_number'  => preg_replace('/\D+/', '', (string) env('WHATSAPP_NUMBER', '')),
            'currency'         => 'PEN',
            'company'          => 'Devioz',
        ]);
    }

    /** POST /api/checkout — cobra la orden con el token de Culqi. */
    public function checkout(Request $request, Response $response): Response
    {
        $body      = (array) $request->getParsedBody();
        $orderCode = trim((string) ($body['order_code'] ?? ''));
        $tokenId   = trim((string) ($body['token_id'] ?? ''));
        $email     = trim((string) ($body['email'] ?? ''));
        $name      = trim((string) ($body['customer_name'] ?? ''));

        if ($orderCode === '' || $tokenId === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Datos incompletos: se requieren order_code, token_id y un email válido.',
            ], 422);
        }

        /** @var Order|null $order */
        $order = Order::where('code', $orderCode)->first();

        if ($order === null) {
            return $this->json($response, ['success' => false, 'message' => 'La orden no existe.'], 404);
        }

        if ($order->status === Order::STATUS_PAID) {
            return $this->json($response, [
                'success' => true,
                'message' => 'Esta orden ya fue pagada.',
                'order'   => $order->toSummary(),
            ]);
        }

        if ($order->status !== Order::STATUS_PENDING || $order->amountInCents() < 300) {
            // Culqi exige un minimo de S/ 3.00 por cargo
            return $this->json($response, [
                'success' => false,
                'message' => 'La orden no es válida para pago (monto mínimo S/ 3.00 o estado incorrecto).',
            ], 422);
        }

        try {
            $charge = $this->culqi->createCharge(
                $tokenId,
                $order->amountInCents(),
                $email,
                'Devioz - Orden ' . $order->code,
                ['order_code' => $order->code]
            );
        } catch (RuntimeException $e) {
            return $this->json($response, ['success' => false, 'message' => $e->getMessage()], 402);
        }

        try {
            // Transaccion ACID: confirmar orden + cerrar carrito de forma atomica
            Capsule::connection()->transaction(function () use ($order, $charge, $email, $name) {
                $locked = Order::where('id', $order->id)->lockForUpdate()->first();

                $locked->update([
                    'status'          => Order::STATUS_PAID,
                    'culqi_charge_id' => $charge['id'],
                    'customer_email'  => $email,
                    'customer_name'   => $name !== '' ? $name : null,
                    'paid_at'         => date('Y-m-d H:i:s'),
                ]);

                Cart::where('session_id', $locked->session_id)
                    ->where('status', Cart::STATUS_OPEN)
                    ->update(['status' => Cart::STATUS_CHECKED_OUT]);
            });

            // Dejar constancia del pago en la conversacion del agente
            $conversation = Conversation::where('session_id', $order->session_id)->first();
            if ($conversation !== null) {
                Message::create([
                    'conversation_id' => $conversation->id,
                    'role'            => 'assistant',
                    'content'         => "✅ Pago confirmado. Orden {$order->code} pagada correctamente por S/ "
                        . number_format((float) $order->total, 2)
                        . ". Recibirás los entregables en {$email}.",
                ]);
            }

            // Email de confirmación al comprador (no bloquea aunque falle)
            $this->email->sendPaymentConfirmation($order->fresh(['items']));

        } catch (Throwable $e) {
            // El cargo ya existe en Culqi: registrarlo aunque falle el guardado local
            return $this->json($response, [
                'success'   => false,
                'message'   => 'El pago fue procesado pero hubo un error al registrar la orden. Contacta a soporte con el código ' . $orderCode . '.',
                'charge_id' => $charge['id'] ?? null,
            ], 500);
        }

        $order->refresh();

        return $this->json($response, [
            'success' => true,
            'message' => '¡Pago exitoso! Gracias por confiar en Devioz.',
            'order'   => $order->toSummary(),
            'charge'  => [
                'id'            => $charge['id'],
                'reference_code' => $charge['reference_code'] ?? null,
            ],
        ]);
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write((string) json_encode($data, JSON_UNESCAPED_UNICODE));

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
