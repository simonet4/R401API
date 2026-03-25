<?php

require_once __DIR__ . '/jwt_utils.php';

header("Content-Type: application/json; charset=UTF-8");

$method = $_SERVER["REQUEST_METHOD"];
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$path = rtrim($path, "/");

// Support both direct deployment (/login, /verify)
// and subfolder deployment (/auth-api/login, /auth-api/verify).
$path = preg_replace('#^.*?/auth-api#', '', $path);

if ($path === "") {
	$path = "/";
}

function respond(int $status, array $data): void
{
	http_response_code($status);
	echo json_encode($data, JSON_UNESCAPED_UNICODE);
	exit;
}

function auth_pdo(): PDO {
	$host = getenv('AUTH_DB_HOST') ?: 'localhost';
	$dbName = getenv('AUTH_DB_NAME') ?: 'r401_auth';
	$user = getenv('AUTH_DB_USER') ?: 'root';
	$pass = getenv('AUTH_DB_PASS') ?: '';

	$pdo = new PDO(
		"mysql:host={$host};dbname={$dbName};charset=utf8mb4",
		$user,
		$pass
	);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	return $pdo;
}

if ($path === "/ping") {
	if ($method !== "GET") {
		respond(405, ["error" => "Méthode non autorisée. Utilise GET."]);
	}

	respond(200, ["message" => "Auth API OK"]);
}

if ($path === "/login") {
	if ($method !== "POST") {
		respond(405, ["error" => "Méthode non autorisée. Utilise POST."]);
	}

	$raw = file_get_contents("php://input");
	$body = json_decode($raw, true);

	if (!is_array($body)) {
		respond(400, ["error" => "Body JSON invalide."]);
	}

	$username = $body["username"] ?? "";
	$password = $body["password"] ?? "";

	if ($username === "" || $password === "") {
		respond(400, ["error" => "username et password sont obligatoires."]);
	}

	try {
		$pdo = auth_pdo();

		$stmt = $pdo->prepare("SELECT username, password_hash, role FROM utilisateur WHERE username = :username LIMIT 1");
		$stmt->execute(["username" => $username]);
		$user = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$user) {
			respond(401, ["error" => "Identifiants invalides."]);
		}

		$passwordFromDb = $user["password_hash"];

		$motDePasseValide = false;

		if (is_string($passwordFromDb) && preg_match('/^\$2y\$|^\$argon2/', $passwordFromDb)) {
			$motDePasseValide = password_verify($password, $passwordFromDb);
		} else {
			// Fallback legacy pour faciliter la migration progressive.
			$motDePasseValide = ($passwordFromDb === $password);
		}

		if (!$motDePasseValide) {
			respond(401, ["error" => "Identifiants invalides."]);
		}

		$jwt = create_jwt([
			"sub" => $username,
			"role" => $user["role"],
		]);

		respond(200, [
			"message" => "Connexion réussie",
			"token" => $jwt,
			"role" => $user["role"],
		]);
	} catch (Throwable $e) {
		respond(500, [
			"error" => "Erreur serveur",
			"detail" => "Configuration base auth invalide. Verifie AUTH_DB_HOST, AUTH_DB_NAME, AUTH_DB_USER, AUTH_DB_PASS.",
		]);
	}
}

if ($path === "/verify") {
	if (!in_array($method, ["GET", "POST"], true)) {
		respond(405, ["error" => "Méthode non autorisée. Utilise GET ou POST."]);
	}

	$token = null;

	$headers = function_exists('getallheaders') ? getallheaders() : [];
	if (isset($headers['Authorization'])) {
		$token = str_replace('Bearer ', '', $headers['Authorization']);
	}

	if ($token === null || trim($token) === '') {
		$raw = file_get_contents("php://input");
		$body = json_decode($raw, true);
		if (is_array($body) && isset($body['token'])) {
			$token = $body['token'];
		}
	}

	if ($token === null || trim($token) === '') {
		respond(400, ["error" => "token obligatoire"]);
	}

	$result = verify_jwt($token);
	if (!$result['valid']) {
		respond(401, [
			"valid" => false,
			"error" => "Token invalide",
			"reason" => $result['reason'] ?? 'unknown'
		]);
	}

	respond(200, [
		"valid" => true,
		"payload" => $result['payload']
	]);
}

respond(404, ["error" => "Route inconnue"]);
