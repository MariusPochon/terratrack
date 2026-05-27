<?php

/**
 * Worker gérant les requêtes PDO liées aux catégories.
 * Fournit les méthodes de lecture de la table t_category.
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/Category.php';

class CategoryWorker {

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
     * Transforme une ligne de résultat PDO en objet Category.
     *
     * @param  array $row Ligne associative retournée par PDO
     * @return Category
     */
    private function hydrate(array $row): Category {
        return new Category(
            $row['name'],
            $row['color'],
            $row['description'],
            (int) $row['pk_category']
        );
    }

    // -------------------------------------------------------------------------
    // Requêtes
    // -------------------------------------------------------------------------

    /**
     * Retourne toutes les catégories triées par nom alphabétique.
     *
     * @return Category[]
     * @throws Exception En cas d'erreur PDO
     */
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

    /**
     * Retourne une catégorie par son identifiant, ou null si elle n'existe pas.
     *
     * @param  int $pkCategory Clé primaire de la catégorie
     * @return Category|null
     * @throws Exception En cas d'erreur PDO
     */
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
