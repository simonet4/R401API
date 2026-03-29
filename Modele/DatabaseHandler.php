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

    private function __construct(){
        $host = $_SERVER['DB_HOST'] ?? getenv('DB_HOST');
        $db   = $_SERVER['DB_NAME'] ?? getenv('DB_NAME');
        $user = $_SERVER['DB_USER'] ?? getenv('DB_USER');
        $pass = $_SERVER['DB_PASS'] ?? getenv('DB_PASS');

        $candidats = [
            [$defaultServer, $defaultDb, $defaultLogin, $defaultMdp],
            ['localhost', 'r301', 'root', ''],
            ['localhost', 'r301', 'root', 'root'],
        ];

        $derniereException = null;

        foreach ($candidats as [$server, $db, $login, $mdp]) {
            try {
                $pdo = new PDO(
                    "mysql:host={$server};dbname={$db};charset=utf8mb4",
                    $login,
                    $mdp
                );
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $this->server = $server;
                $this->db = $db;
                $this->login = $login;
                $this->mdp = $mdp;
                $this->linkpdo = $pdo;
                return;
            } catch (Exception $e) {
                $derniereException = $e;
            }
        }

        $message = $derniereException ? $derniereException->getMessage() : 'Connexion impossible';
        die("Erreur : {$message}. Configurez DB_HOST, DB_NAME, DB_USER, DB_PASS ou créez la base r301.");
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