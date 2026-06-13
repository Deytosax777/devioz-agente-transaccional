<?php

declare(strict_types=1);

namespace Devioz\Services;

/**
 * Tokens firmados HMAC-SHA256 (estilo JWT compacto) para el panel admin.
 * Formato: base64url(json_claims) . "." . hmac
 */
class TokenService
{
    private string $secret;

    public function __construct()
    {
        $this->secret = (string) env('APP_KEY', '');
    }

    public function issue(array $claims, int $ttlSeconds = 28800): string
    {
        $claims['exp'] = time() + $ttlSeconds;
        $claims['iat'] = time();

        $payload   = $this->base64UrlEncode((string) json_encode($claims));
        $signature = hash_hmac('sha256', $payload, $this->secret);

        return $payload . '.' . $signature;
    }

    /** Devuelve los claims si el token es valido y vigente; null en caso contrario. */
    public function verify(?string $token): ?array
    {
        if ($token === null || $this->secret === '' || substr_count($token, '.') !== 1) {
            return null;
        }

        [$payload, $signature] = explode('.', $token, 2);

        $expected = hash_hmac('sha256', $payload, $this->secret);
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $claims = json_decode($this->base64UrlDecode($payload), true);
        if (!is_array($claims) || ($claims['exp'] ?? 0) < time()) {
            return null;
        }

        return $claims;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return (string) base64_decode(strtr($data, '-_', '+/'));
    }
}
