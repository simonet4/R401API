<?php

function get_bearer_token(): ?string {
    $headers = function_exists('getallheaders') ? getallheaders() : [];

    $authorization = $headers['Authorization']
        ?? $headers['authorization']
        ?? $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? null;

    if ($authorization === null) {
        return null;
    }

    if (stripos($authorization, 'Bearer ') === 0) {
        return trim(substr($authorization, 7));
    }

    return trim($authorization);
}

// ---- Verification JWT locale (sans appel HTTP) ----
// L'appel HTTP loopback (file_get_contents vers le meme serveur)
// est bloque sur les hebergements mutualises comme Alwaysdata.
// On verifie le token directement avec la meme cle secrete.

function _auth_base64url_decode(string $data): string {
    $padding = strlen($data) % 4;
    if ($padding > 0) {
        $data .= str_repeat('=', 4 - $padding);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

function _auth_jwt_secret(): string {
    $secret = getenv('AUTH_JWT_SECRET');
    if ($secret === false || $secret === '') {
        $secret = $_SERVER['AUTH_JWT_SECRET'] ?? $_ENV['AUTH_JWT_SECRET'] ?? '';
    }
    if ($secret === '') {
        $secret = 'CHANGE_ME_SUPER_SECRET_AUTH_ONLY';
    }
    return $secret;
}

function verify_token_with_auth_api(?string $token): array {
    // DEBUG: log chaque etape de verification
    $debug = [];
    $debug[] = '[AUTH_CLIENT] verify_token_with_auth_api() appelee';
    $debug[] = '[AUTH_CLIENT] Token recu: ' . ($token === null ? 'NULL' : (strlen($token) > 20 ? substr($token, 0, 20) . '...' : $token));

    if ($token === null || $token === '') {
        $debug[] = '[AUTH_CLIENT] ERREUR: token vide ou null';
        error_log(implode(' | ', $debug));
        return [
            'valid' => false,
            'status' => 401,
            'error' => 'Token manquant',
            'debug' => $debug,
        ];
    }

    $parts = explode('.', $token);
    $debug[] = '[AUTH_CLIENT] Nombre de parties JWT: ' . count($parts);

    if (count($parts) !== 3) {
        $debug[] = '[AUTH_CLIENT] ERREUR: format JWT invalide (attendu 3 parties)';
        error_log(implode(' | ', $debug));
        return [
            'valid' => false,
            'status' => 401,
            'error' => 'Token invalide (format)',
            'debug' => $debug,
        ];
    }

    [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
    $unsignedToken = $encodedHeader . '.' . $encodedPayload;

    $secret = _auth_jwt_secret();
    $debug[] = '[AUTH_CLIENT] Secret utilise (4 premiers chars): ' . substr($secret, 0, 4) . '...';

    $expectedSignature = hash_hmac('sha256', $unsignedToken, $secret, true);
    $givenSignature = _auth_base64url_decode($encodedSignature);

    if (!hash_equals($expectedSignature, $givenSignature)) {
        $debug[] = '[AUTH_CLIENT] ERREUR: signature HMAC invalide';
        error_log(implode(' | ', $debug));
        return [
            'valid' => false,
            'status' => 401,
            'error' => 'Token invalide (signature)',
            'debug' => $debug,
        ];
    }

    $debug[] = '[AUTH_CLIENT] Signature OK';

    $payload = json_decode(_auth_base64url_decode($encodedPayload), true);
    if (!is_array($payload)) {
        $debug[] = '[AUTH_CLIENT] ERREUR: payload JSON invalide';
        error_log(implode(' | ', $debug));
        return [
            'valid' => false,
            'status' => 401,
            'error' => 'Token invalide (payload)',
            'debug' => $debug,
        ];
    }

    $debug[] = '[AUTH_CLIENT] Payload decode: sub=' . ($payload['sub'] ?? '?') . ', role=' . ($payload['role'] ?? '?') . ', exp=' . ($payload['exp'] ?? '?');

    if (!isset($payload['exp']) || time() >= (int)$payload['exp']) {
        $debug[] = '[AUTH_CLIENT] ERREUR: token expire (now=' . time() . ', exp=' . ($payload['exp'] ?? 'absent') . ')';
        error_log(implode(' | ', $debug));
        return [
            'valid' => false,
            'status' => 401,
            'error' => 'Token expire',
            'debug' => $debug,
        ];
    }

    $debug[] = '[AUTH_CLIENT] Token VALIDE';
    error_log(implode(' | ', $debug));

    return [
        'valid' => true,
        'status' => 200,
        'payload' => $payload,
        'debug' => $debug,
    ];
}
