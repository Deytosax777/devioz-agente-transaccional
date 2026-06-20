<?php

declare(strict_types=1);

namespace Devioz\Services;

use Devioz\Models\Order;

/**
 * Envía emails transaccionales mediante la función mail() nativa de PHP.
 * En producción apunta MAIL_FROM a una dirección validada en tu proveedor
 * (Resend, SendGrid, Mailgun, etc.) o configura el MTA del servidor.
 */
class EmailService
{
    private string $fromEmail;
    private string $fromName;
    private string $replyTo;
    private string $appUrl;
    private string $whatsapp;

    public function __construct()
    {
        $this->fromEmail = (string) env('MAIL_FROM',      'noreply@devioz.pe');
        $this->fromName  = (string) env('MAIL_FROM_NAME', 'Devioz');
        $this->replyTo   = (string) env('MAIL_REPLY_TO',  'contacto@devioz.pe');
        $this->appUrl    = rtrim((string) env('APP_URL', 'http://localhost:8080'), '/');
        $this->whatsapp  = preg_replace('/\D+/', '', (string) env('WHATSAPP_NUMBER', '51999999999'));
    }

    /**
     * Envía la confirmación de pago al cliente.
     * No lanza excepciones — un fallo de correo no debe romper el flujo de pago.
     */
    public function sendPaymentConfirmation(Order $order): bool
    {
        if ($order->customer_email === null || $order->customer_email === '') {
            return false;
        }

        $order->loadMissing('items');

        $subject = "✅ Orden {$order->code} confirmada — Devioz";
        $html    = $this->buildConfirmationHtml($order);
        $headers = $this->buildHeaders($order->customer_email);

        try {
            return mail(
                $order->customer_email,
                '=?UTF-8?B?' . base64_encode($subject) . '?=',
                $html,
                implode("\r\n", $headers)
            );
        } catch (\Throwable) {
            return false;
        }
    }

    // -------------------------------------------------------------------------

    /** @return string[] */
    private function buildHeaders(string $toEmail): array
    {
        $encoded = '=?UTF-8?B?' . base64_encode($this->fromName) . '?=';

        return [
            "From: {$encoded} <{$this->fromEmail}>",
            "Reply-To: {$this->replyTo}",
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            'X-Mailer: Devioz/1.0',
        ];
    }

    private function buildConfirmationHtml(Order $order): string
    {
        $code      = htmlspecialchars($order->code, ENT_QUOTES, 'UTF-8');
        $name      = htmlspecialchars($order->customer_name ?? 'Cliente', ENT_QUOTES, 'UTF-8');
        $total     = 'S/ ' . number_format((float) $order->total, 2);
        $paidAt    = $order->paid_at ? $order->paid_at->format('d/m/Y H:i') : date('d/m/Y H:i');
        $whatsUrl  = "https://wa.me/{$this->whatsapp}?text=" . rawurlencode("Hola Devioz, mi orden es {$order->code}. Necesito ayuda con los entregables.");

        // Filas de productos
        $rows = '';
        foreach ($order->items as $item) {
            $itemName  = htmlspecialchars($item->product_name, ENT_QUOTES, 'UTF-8');
            $itemPrice = number_format((float) $item->unit_price * $item->quantity, 2);
            $rows .= <<<ROW
            <tr>
              <td style="padding:10px 16px;border-bottom:1px solid #e8f0eb;font-size:14px;color:#1a2e2b;">{$itemName}</td>
              <td style="padding:10px 16px;border-bottom:1px solid #e8f0eb;font-size:14px;color:#475569;text-align:center;">{$item->quantity}</td>
              <td style="padding:10px 16px;border-bottom:1px solid #e8f0eb;font-size:14px;color:#1a2e2b;text-align:right;font-weight:600;">S/ {$itemPrice}</td>
            </tr>
            ROW;
        }

        $logoUrl = $this->appUrl . '/assets/images/logo.svg';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Orden confirmada — Devioz</title>
</head>
<body style="margin:0;padding:0;background:#f0f7f5;font-family:'Inter',system-ui,Arial,sans-serif;">

  <!-- Wrapper -->
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f7f5;padding:32px 16px;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

        <!-- Header teal -->
        <tr>
          <td style="background:linear-gradient(135deg,#0B6F60,#053D35);border-radius:18px 18px 0 0;padding:36px 40px;text-align:center;">
            <img src="{$logoUrl}" alt="Devioz" width="48" height="48" style="margin-bottom:12px;display:block;margin-left:auto;margin-right:auto;">
            <div style="font-size:11px;font-weight:700;letter-spacing:0.2em;color:rgba(255,255,255,0.6);text-transform:uppercase;margin-bottom:6px;">Devioz · Consultora Tecnológica</div>
            <h1 style="margin:0;font-size:26px;font-weight:800;color:#fff;letter-spacing:-0.02em;">¡Pago confirmado!</h1>
            <p style="margin:10px 0 0;font-size:15px;color:rgba(255,255,255,0.78);">Tu orden ha sido procesada exitosamente.</p>
          </td>
        </tr>

        <!-- Cuerpo blanco -->
        <tr>
          <td style="background:#fff;padding:36px 40px;">

            <!-- Saludo -->
            <p style="margin:0 0 24px;font-size:16px;color:#1a2e2b;">Hola, <strong>{$name}</strong> 👋</p>

            <!-- Caja de orden -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4faf8;border:1.5px solid #c4e8df;border-radius:14px;margin-bottom:28px;">
              <tr>
                <td style="padding:20px 24px;">
                  <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                      <td style="font-size:11px;font-weight:700;letter-spacing:0.12em;color:#0B6F60;text-transform:uppercase;">Número de orden</td>
                      <td style="font-size:11px;font-weight:700;letter-spacing:0.12em;color:#0B6F60;text-transform:uppercase;text-align:right;">Fecha</td>
                    </tr>
                    <tr>
                      <td style="font-size:22px;font-weight:800;color:#053D35;letter-spacing:-0.01em;padding-top:4px;">{$code}</td>
                      <td style="font-size:14px;color:#475569;text-align:right;padding-top:6px;">{$paidAt}</td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <!-- Tabla de productos -->
            <p style="margin:0 0 10px;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#64748b;">Detalle de la compra</p>
            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;margin-bottom:28px;">
              <thead>
                <tr style="background:#f8fafc;">
                  <th style="padding:10px 16px;text-align:left;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.08em;">Producto</th>
                  <th style="padding:10px 16px;text-align:center;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.08em;">Cant.</th>
                  <th style="padding:10px 16px;text-align:right;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.08em;">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                {$rows}
              </tbody>
              <tfoot>
                <tr style="background:#f4faf8;">
                  <td colspan="2" style="padding:14px 16px;font-size:15px;font-weight:800;color:#053D35;">Total pagado</td>
                  <td style="padding:14px 16px;font-size:18px;font-weight:800;color:#0B6F60;text-align:right;">{$total}</td>
                </tr>
              </tfoot>
            </table>

            <!-- Mensaje de entregables -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#fffbeb;border:1.5px solid #fde68a;border-radius:12px;margin-bottom:28px;">
              <tr>
                <td style="padding:16px 20px;font-size:14px;color:#92400e;line-height:1.6;">
                  📦 <strong>Próximos pasos:</strong> Nuestro equipo revisará tu orden y te enviará los entregables a este correo en un plazo de <strong>24 horas hábiles</strong>. Si tienes alguna duda, contáctanos directamente.
                </td>
              </tr>
            </table>

            <!-- Botón WhatsApp -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:8px;">
              <tr>
                <td align="center">
                  <a href="{$whatsUrl}" style="display:inline-block;background:#16a34a;color:#fff;font-weight:700;font-size:15px;padding:14px 32px;border-radius:999px;text-decoration:none;letter-spacing:-0.01em;">
                    📱 Contactar soporte por WhatsApp
                  </a>
                </td>
              </tr>
            </table>

          </td>
        </tr>

        <!-- Footer oscuro -->
        <tr>
          <td style="background:#053D35;border-radius:0 0 18px 18px;padding:24px 40px;text-align:center;">
            <p style="margin:0 0 6px;font-size:13px;color:rgba(255,255,255,0.55);">
              © {$this->year()} Devioz · Lima, Perú · Pagos procesados con Culqi
            </p>
            <p style="margin:0;font-size:12px;color:rgba(255,255,255,0.35);">
              Recibes este correo porque realizaste una compra en devioz.pe
            </p>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>

</body>
</html>
HTML;
    }

    private function year(): string
    {
        return date('Y');
    }
}
