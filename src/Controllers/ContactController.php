<?php

declare(strict_types=1);

namespace Devioz\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ContactController
{
    public function send(Request $request, Response $response): Response
    {
        $body = (string) $request->getBody();
        $data = json_decode($body, true) ?? [];

        $nombre   = trim((string) ($data['nombre']   ?? ''));
        $email    = trim((string) ($data['email']    ?? ''));
        $empresa  = trim((string) ($data['empresa']  ?? ''));
        $telefono = trim((string) ($data['telefono'] ?? ''));
        $servicio = trim((string) ($data['servicio'] ?? ''));
        $mensaje  = trim((string) ($data['mensaje']  ?? ''));

        if ($nombre === '' || $email === '' || $mensaje === '') {
            return $this->json($response, ['success' => false, 'message' => 'Nombre, email y mensaje son obligatorios.'], 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json($response, ['success' => false, 'message' => 'El email no es válido.'], 422);
        }

        $to      = $_ENV['MAIL_REPLY_TO'] ?? $_ENV['MAIL_FROM'] ?? 'contacto@devioz.com';
        $from    = $_ENV['MAIL_FROM']     ?? 'noreply@devioz.com';
        $subject = "Nuevo contacto web: {$nombre}" . ($empresa ? " ({$empresa})" : '');

        $lines = [
            "Nombre:   {$nombre}",
            "Email:    {$email}",
            "Empresa:  {$empresa}",
            "Teléfono: {$telefono}",
            "Servicio: {$servicio}",
            "",
            "Mensaje:",
            $mensaje,
        ];

        $body = implode("\r\n", $lines);

        $headers = implode("\r\n", [
            "From: {$from}",
            "Reply-To: {$email}",
            "Content-Type: text/plain; charset=UTF-8",
            "X-Mailer: Devioz-Web/1.0",
        ]);

        $sent = @mail($to, $subject, $body, $headers);

        if (!$sent) {
            // Si mail() falla (ej. en desarrollo sin MTA), logueamos y respondemos éxito de todas formas
            // para no bloquear al usuario. En producción configurar SMTP en php.ini.
            error_log("[ContactController] mail() falló. Datos: " . json_encode($data));
        }

        return $this->json($response, ['success' => true]);
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write((string) json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status);
    }
}
