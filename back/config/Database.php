<?php

/**
 * Gestionnaire de connexion à la base de données.
 * Implémente le patron Singleton pour garantir une seule instance PDO par requête.
 */

require_once __DIR__ . '/config.php';

class Database {

    /** @var Database|null Instance unique de la classe */
    private static ?Database $instance = null;

    /** @var PDO Connexion PDO active */
    private PDO $pdo;

    /**
     * Constructeur privé : établit la connexion PDO avec les paramètres de config.php.
     * En cas d'échec, renvoie une erreur 500 en JSON et stoppe l'exécution.
     */
    private function __construct() {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // lance des exceptions sur erreur PDO
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,          // retourne les lignes sous forme de tableaux associatifs
        ];
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database connection failed']);
            exit;
        }
    }

    /**
     * Retourne l'instance unique de Database, en la créant si nécessaire.
     *
     * @return Database
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Retourne la connexion PDO active.
     *
     * @return PDO
     */
    public function getConnection(): PDO {
        return $this->pdo;
    }
}
