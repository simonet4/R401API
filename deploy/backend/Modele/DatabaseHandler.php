<?php

namespace R301\Modele;

use Exception;
use PDO;

class DatabaseHandler {
    private static ?DatabaseHandler $instance = null;
    private readonly PDO $linkpdo;
    private readonly string $server;
    private readonly string $db;
    private readonly string $login;
    private readonly string $mdp;

    private static function env(string $key, string $default = ''): string {
        $val = getenv($key);
        if ($val !== false && $val !== '') return $val;
        return $_SERVER[$key] ?? $_ENV[$key] ?? $default;
    }

    private function __construct(){
        $defaultServer = self::env('DB_HOST', 'localhost');
        $defaultDb = self::env('DB_NAME', 'backendalwaysdata_R401');
        $defaultLogin = self::env('DB_USER', 'root');
        $defaultMdp = self::env('DB_PASS', '');

        error_log("[DB] Tentative connexion: host={$defaultServer}, db={$defaultDb}, user={$defaultLogin}");

        try {
            $pdo = new PDO(
                "mysql:host={$defaultServer};dbname={$defaultDb};charset=utf8mb4",
                $defaultLogin,
                $defaultMdp
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $this->server = $defaultServer;
            $this->db = $defaultDb;
            $this->login = $defaultLogin;
            $this->mdp = $defaultMdp;
            $this->linkpdo = $pdo;
            error_log("[DB] Connexion reussie a {$defaultDb}");
        } catch (Exception $e) {
            error_log("[DB] ERREUR connexion: " . $e->getMessage());
            die("Erreur BDD : " . $e->getMessage() . ". Verifiez DB_HOST, DB_NAME, DB_USER, DB_PASS. Base tentee: {$defaultDb}");
        }
    }

    public static function getInstance(): DatabaseHandler
    {
        if (self::$instance == null) {
            self::$instance = new DatabaseHandler();
        }
        return self::$instance;
    }

    public function pdo(): PDO {
        return $this->linkpdo;
    }
}