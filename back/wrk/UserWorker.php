<?php

/**
 * Worker gérant les requêtes PDO liées aux utilisateurs.
 * Fournit les méthodes de lecture de la table t_user.
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/User.php';

class UserWorker {

    /** @var PDO Instance de connexion à la base de données */
    private PDO $pdo;

    /**
     * Initialise le worker avec la connexion PDO partagée.
     */
    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    // -------------------------------------------------------------------------
    // Hydratation
    // -------------------------------------------------------------------------

    /**
     * Transforme une ligne de résultat PDO en objet User.
     *
     * @param  array $row Ligne associative retournée par PDO
     * @return User
     */
    private function hydrate(array $row): User {
        return new User(
            $row['username'],
            $row['password'],
            (int) $row['pk_user']
        );
    }

    // -------------------------------------------------------------------------
    // Requêtes
    // -------------------------------------------------------------------------

    /**
     * Recherche un utilisateur par son nom d'utilisateur.
     * Retourne null si aucun utilisateur ne correspond.
     *
     * @param  string $username Nom d'utilisateur à rechercher
     * @return User|null
     * @throws Exception En cas d'erreur PDO
     */
    public function findByUsername(string $username): ?User {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM t_user WHERE username = :username');
            $stmt->execute([':username' => $username]);

            $row = $stmt->fetch();
            return $row ? $this->hydrate($row) : null;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération de l'utilisateur '$username' : " . $e->getMessage());
        }
    }
}
