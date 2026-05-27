<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/Category.php';

class CategoryWorker {

    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    // -------------------------------------------------------------------------
    // Hydration
    // -------------------------------------------------------------------------

    private function hydrate(array $row): Category {
        return new Category(
            $row['name'],
            $row['color'],
            $row['description'],
            (int) $row['pk_category']
        );
    }

    // -------------------------------------------------------------------------
    // Queries
    // -------------------------------------------------------------------------

    public function findAll(): array {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM t_category ORDER BY name ASC');
            $stmt->execute();

            $categories = [];
            foreach ($stmt->fetchAll() as $row) {
                $categories[] = $this->hydrate($row);
            }
            return $categories;
        } catch (PDOException $e) {
            throw new Exception('Erreur lors de la récupération des catégories : ' . $e->getMessage());
        }
    }

    public function findById(int $pkCategory): ?Category {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM t_category WHERE pk_category = :pk_category');
            $stmt->execute([':pk_category' => $pkCategory]);

            $row = $stmt->fetch();
            return $row ? $this->hydrate($row) : null;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération de la catégorie (id=$pkCategory) : " . $e->getMessage());
        }
    }
}
