<?php
// Client HTTP pour appeler l'API backend

// Chemin de base de l'appli
function app_base_path(): string {
    $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    $dir = str_replace('\\', '/', dirname($scriptName));

    if ($dir === '/' || $dir === '.' || $dir === '') {
        return '';
    }

    return rtrim($dir, '/');
}

// Construit l'URL de base de l'API
function api_base_url(): string {
    $configured = getenv('TEAM_API_BASE_URL');
    if ($configured === false || $configured === '') {
        $configured = $_SERVER['TEAM_API_BASE_URL'] ?? $_ENV['TEAM_API_BASE_URL'] ?? '';
    }
    if (is_string($configured) && $configured !== '') {
        return rtrim($configured, '/');
    }

    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . '://' . $host . app_base_path();
}

// Requete HTTP générique vers l'API
function api_request(string $method, string $path, ?array $body = null, bool $authRequired = true): array {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $url = api_base_url() . $path;

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];

    if ($authRequired) {
        $token = $_SESSION['auth_token'] ?? null;
        if (!is_string($token) || $token === '') {
            return [
                'ok' => false,
                'status' => 401,
                'data' => null,
                'error' => 'Utilisateur non authentifie',
            ];
        }

        $headers[] = 'Authorization: Bearer ' . $token;
    }

    $options = [
        'http' => [
            'method' => strtoupper($method),
            'header' => implode("\r\n", $headers) . "\r\n",
            'ignore_errors' => true,
            'timeout' => 5,
        ],
    ];

    if ($body !== null) {
        $options['http']['content'] = json_encode($body, JSON_UNESCAPED_UNICODE);
    }

    $context = stream_context_create($options);
    $raw = @file_get_contents($url, false, $context);

    $status = 500;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
        $status = (int)$m[1];
    }

    if ($raw === false) {
        return [
            'ok' => false,
            'status' => 503,
            'data' => null,
            'error' => 'API inaccessible',
        ];
    }

    $decoded = json_decode($raw, true);
    $data = is_array($decoded) ? $decoded : null;

    $result = [
        'ok' => $status >= 200 && $status < 300,
        'status' => $status,
        'data' => $data,
        'error' => is_array($data)
            ? (string)($data['erreur'] ?? $data['error'] ?? ('HTTP ' . $status))
            : ('HTTP ' . $status),
    ];

    // Si 401, on redirige vers le login
    if ($authRequired && $status === 401) {
        unset($_SESSION['auth_token'], $_SESSION['auth_role'], $_SESSION['username']);

        $currentPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? '';
        if ($currentPath !== '/login') {
            header('Location: /login');
            exit();
        }
    }

    return $result;
}

function api_get(string $path, bool $authRequired = true): array {
    return api_request('GET', $path, null, $authRequired);
}

function api_post(string $path, array $body, bool $authRequired = true): array {
    return api_request('POST', $path, $body, $authRequired);
}

function api_put(string $path, array $body, bool $authRequired = true): array {
    return api_request('PUT', $path, $body, $authRequired);
}

function api_patch(string $path, array $body, bool $authRequired = true): array {
    return api_request('PATCH', $path, $body, $authRequired);
}

function api_delete(string $path, bool $authRequired = true): array {
    return api_request('DELETE', $path, null, $authRequired);
}
