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
        $this->server = getenv('DB_HOST') ?: 'localhost';
        $this->db     = getenv('DB_NAME') ?: 'r301';
        $this->login  = getenv('DB_USER') ?: 'r301';
        $this->mdp    = getenv('DB_PASS') ?: '';

        try {
            $pdo = new PDO(
                "mysql:host={$this->server};dbname={$this->db};charset=utf8mb4",
                $this->login,
                $this->mdp
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->linkpdo = $pdo;
        } catch (Exception $e) {
            die("Erreur BDD : {$e->getMessage()}. Configurez DB_HOST, DB_NAME, DB_USER, DB_PASS.");
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