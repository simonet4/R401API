
<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["username"]) && isset($_POST["password"])) {
    $authLoginUrl = getenv('AUTH_LOGIN_URL');
    if ($authLoginUrl === false || $authLoginUrl === '') {
        $authLoginUrl = $_SERVER['AUTH_LOGIN_URL'] ?? $_ENV['AUTH_LOGIN_URL'] ?? '';
    }
    if ($authLoginUrl === false || $authLoginUrl === '') {
        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $isHttps ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $authLoginUrl = $scheme . '://' . $host . '/auth-api/login';
    }
    error_log("[VUE_LOGIN] Tentative login: url={$authLoginUrl}, username=" . trim((string)$_POST['username']));

    $payload = json_encode([
        'username' => trim((string)$_POST['username']),
        'password' => trim((string)$_POST['password']),
    ], JSON_UNESCAPED_UNICODE);

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $payload,
            'ignore_errors' => true,
            'timeout' => 5,
        ]
    ]);

    $result = @file_get_contents($authLoginUrl, false, $context);
    error_log("[VUE_LOGIN] Reponse brute: " . ($result === false ? 'FALSE (connexion echouee)' : substr((string)$result, 0, 200)));
    $statusCode = 500;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches)) {
        $statusCode = (int)$matches[1];
    }

    $decoded = is_string($result) ? json_decode($result, true) : null;
    error_log("[VUE_LOGIN] Status: {$statusCode}, token_present=" . (is_array($decoded) && isset($decoded['token']) ? 'OUI' : 'NON'));
    if ($statusCode === 200 && is_array($decoded) && isset($decoded['token'])) {
        $_SESSION['auth_token'] = (string)$decoded['token'];
        $_SESSION['auth_role'] = (string)($decoded['role'] ?? '');
        $_SESSION['username'] = trim((string)$_POST['username']);

        header("Location: joueur");
        die();
    }

    $erreur = is_array($decoded) && isset($decoded['error'])
        ? (string)$decoded['error']
        : "Le nom d'Utilisateur ou le mot de passe est incorrect";
    error_log("[VUE_LOGIN] Login ECHOUE: {$erreur}");
}
?>

<body>
    <div class="CentredContainer">
        <h1>Login</h1>
        <div class="container">
            <form action="login" method="post">
                <div class="row">
                    <div class="col-20">
                        <label for="username">Username : </label>
                    </div>
                    <div class="col-80">
                        <input type="text" id="username" name="username"/><br> 
                    </div>
                </div> 
                <div class="row">
                    <div class="col-20">
                        <label for="password">Password : </label>
                    </div>
                    <div class="col-80">
                        <input type="password" id="pass" name="password"/><br>
                    </div>
                </div>
                <div class="row">
                    <input type="submit" value="Login"/>
                </div>
            </form>
        </div>
        <p><?php if (isset($erreur)) { echo $erreur; } ?></p>
    </div>
</body>
</html>
