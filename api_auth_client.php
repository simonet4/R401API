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

function verify_token_with_auth_api(?string $token): array {
    if ($token === null || $token === '') {
        return [
            'valid' => false,
            'status' => 401,
            'error' => 'Token manquant',
        ];
    }

    $authVerifyUrl = getenv('AUTH_VERIFY_URL');
    if ($authVerifyUrl === false || $authVerifyUrl === '') {
        $authVerifyUrl = 'http://127.0.0.1:8001/verify';
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Authorization: Bearer {$token}\r\n",
            'ignore_errors' => true,
            'timeout' => 2,
        ]
    ]);

    $result = @file_get_contents($authVerifyUrl, false, $context);

    $statusCode = 500;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches)) {
        $statusCode = (int)$matches[1];
    }

    if ($result === false) {
        return [
            'valid' => false,
            'status' => 503,
            'error' => 'Auth API inaccessible',
        ];
    }

    $decoded = json_decode($result, true);
    if ($statusCode !== 200 || !is_array($decoded) || !($decoded['valid'] ?? false)) {
        return [
            'valid' => false,
            'status' => 401,
            'error' => 'Token invalide',
        ];
    }

    return [
        'valid' => true,
        'status' => 200,
        'payload' => $decoded['payload'] ?? [],
    ];
}
