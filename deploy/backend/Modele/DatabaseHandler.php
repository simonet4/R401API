<?php
// Singleton PDO pour la connexion BDD
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

    // Récupère une variable d'env (getenv -> $_SERVER -> $_ENV)
    private static function env(string $key, string $default = ''): string {
        $val = getenv($key);
        if ($val !== false && $val !== '') return $val;
        return $_SERVER[$key] ?? $_ENV[$key] ?? $default;
    }

    private function __construct(){
        $defaultServer = self::env('DB_HOST', 'localhost');
        $defaultDb = self::env('DB_NAME', 'backendalwaysdata_r401');
        $defaultLogin = self::env('DB_USER', 'root');
        $defaultMdp = self::env('DB_PASS', '');

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
        } catch (Exception $e) {
            die("Erreur BDD : " . $e->getMessage());
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