<?php

declare(strict_types=1);

namespace Devioz\Controllers;

use Devioz\Models\AdminUser;
use Devioz\Services\TokenService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/** Login del panel de administracion. */
class AuthController
{
    public function __construct(private TokenService $tokens)
    {
    }

    /** POST /api/admin/login  {email, password} */
    public function login(Request $request, Response $response): Response
    {
        $body     = (array) $request->getParsedBody();
        $email    = trim((string) ($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        if ($email === '' || $password === '') {
            return $this->json($response, [
                'error'   => 'bad_request',
                'message' => 'Ingresa email y contraseña.',
            ], 422);
        }

        $admin = AdminUser::where('email', $email)->first();

        if ($admin === null || !$admin->verifyPassword($password)) {
            // Mensaje generico: no revelar si el email existe
            return $this->json($response, [
                'error'   => 'invalid_credentials',
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        $token = $this->tokens->issue(['admin_id' => $admin->id, 'email' => $admin->email]);

        return $this->json($response, [
            'token' => $token,
            'admin' => ['id' => $admin->id, 'name' => $admin->name, 'email' => $admin->email],
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
