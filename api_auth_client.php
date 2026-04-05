<?php

// Récupère le token Bearer depuis les headers HTTP
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

// Vérification JWT locale (pas d'appel HTTP, bloqué sur mutualisé)
function _auth_base64url_decode(string $data): string {
    $padding = strlen($data) % 4;
    if ($padding > 0) {
        $data .= str_repeat('=', 4 - $padding);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

// Récupère le secret JWT depuis les variables d'env
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

// Vérifie le token JWT et retourne le résultat
function verify_token_with_auth_api(?string $token): array {
    if ($token === null || $token === '') {
        return ['valid' => false, 'status' => 401, 'error' => 'Token manquant'];
    }

    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return ['valid' => false, 'status' => 401, 'error' => 'Token invalide (format)'];
    }

    [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
    $unsignedToken = $encodedHeader . '.' . $encodedPayload;

    // Vérif signature HMAC
    $expectedSignature = hash_hmac('sha256', $unsignedToken, _auth_jwt_secret(), true);
    $givenSignature = _auth_base64url_decode($encodedSignature);

    if (!hash_equals($expectedSignature, $givenSignature)) {
        return ['valid' => false, 'status' => 401, 'error' => 'Token invalide (signature)'];
    }

    // Décode le payload
    $payload = json_decode(_auth_base64url_decode($encodedPayload), true);
    if (!is_array($payload)) {
        return ['valid' => false, 'status' => 401, 'error' => 'Token invalide (payload)'];
    }

    // Vérif expiration
    if (!isset($payload['exp']) || time() >= (int)$payload['exp']) {
        return ['valid' => false, 'status' => 401, 'error' => 'Token expire'];
    }

    return ['valid' => true, 'status' => 200, 'payload' => $payload];
}
