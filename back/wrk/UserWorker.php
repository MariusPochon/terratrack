<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/User.php';

class UserWorker {

    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    // -------------------------------------------------------------------------
    // Hydration
    // -------------------------------------------------------------------------

    private function hydrate(array $row): User {
        return new User(
            $row['username'],
            $row['password'],
            (int) $row['pk_user']
        );
    }

    // -------------------------------------------------------------------------
    // Queries
    // -------------------------------------------------------------------------

    public function findByUsername(string $username): ?User {
        $stmt = $this->pdo->prepare('SELECT * FROM t_user WHERE username = :username');
        $stmt->execute([':username' => $username]);

        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }
}
