<?php

function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode(string $data): string {
    $padding = strlen($data) % 4;
    if ($padding > 0) {
        $data .= str_repeat('=', 4 - $padding);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_secret(): string {
    $secret = getenv('AUTH_JWT_SECRET');
    if ($secret === false || $secret === '') {
        $secret = 'CHANGE_ME_SUPER_SECRET_AUTH_ONLY';
    }
    return $secret;
}

function create_jwt(array $payload, int $ttlSeconds = 3600): string {
    $now = time();

    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT'
    ];

    $payload['iat'] = $now;
    $payload['exp'] = $now + $ttlSeconds;

    $encodedHeader = base64url_encode(json_encode($header, JSON_UNESCAPED_UNICODE));
    $encodedPayload = base64url_encode(json_encode($payload, JSON_UNESCAPED_UNICODE));

    $unsignedToken = $encodedHeader . '.' . $encodedPayload;
    $signature = hash_hmac('sha256', $unsignedToken, jwt_secret(), true);

    return $unsignedToken . '.' . base64url_encode($signature);
}

function verify_jwt(string $token): array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return ['valid' => false, 'reason' => 'format'];
    }

    [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
    $unsignedToken = $encodedHeader . '.' . $encodedPayload;

    $expectedSignature = hash_hmac('sha256', $unsignedToken, jwt_secret(), true);
    $givenSignature = base64url_decode($encodedSignature);

    if (!hash_equals($expectedSignature, $givenSignature)) {
        return ['valid' => false, 'reason' => 'signature'];
    }

    $payload = json_decode(base64url_decode($encodedPayload), true);
    if (!is_array($payload)) {
        return ['valid' => false, 'reason' => 'payload'];
    }

    if (!isset($payload['exp']) || time() >= (int)$payload['exp']) {
        return ['valid' => false, 'reason' => 'expired'];
    }

    return [
        'valid' => true,
        'payload' => $payload,
    ];
}
