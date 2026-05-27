<?php

/**
 * Worker gérant les requêtes PDO liées aux observations.
 * Fournit les méthodes de lecture, d'insertion, de mise à jour et de suppression
 * de la table t_observation.
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/Observation.php';

class ObservationWorker {

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
     * Transforme une ligne de résultat PDO en objet Observation.
     *
     * @param  array $row Ligne associative retournée par PDO
     * @return Observation
     */
    private function hydrate(array $row): Observation {
        return new Observation(
            $row['title'],
            $row['type'],
            (int) $row['fk_category'],
            $row['description'],
            $row['created_at'],
            (int) $row['pk_observation']
        );
    }

    // -------------------------------------------------------------------------
    // Requêtes
    // -------------------------------------------------------------------------

    /**
     * Retourne toutes les observations triées par date de création décroissante.
     *
     * @return Observation[]
     * @throws Exception En cas d'erreur PDO
     */
    public function findAll(): array {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM t_observation ORDER BY created_at DESC'
            );
            $stmt->execute();

            $observations = [];
            foreach ($stmt->fetchAll() as $row) {
                $observations[] = $this->hydrate($row);
            }
            return $observations;
        } catch (PDOException $e) {
            throw new Exception('Erreur lors de la récupération des observations : ' . $e->getMessage());
        }
    }

    /**
     * Retourne une observation par son identifiant, ou null si elle n'existe pas.
     *
     * @param  int $pkObservation Clé primaire de l'observation
     * @return Observation|null
     * @throws Exception En cas d'erreur PDO
     */
    public function findById(int $pkObservation): ?Observation {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM t_observation WHERE pk_observation = :pk_observation'
            );
            $stmt->execute([':pk_observation' => $pkObservation]);

            $row = $stmt->fetch();
            return $row ? $this->hydrate($row) : null;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération de l'observation (id=$pkObservation) : " . $e->getMessage());
        }
    }

    /**
     * Recherche des observations dont le titre ou la description contient le mot-clé.
     * La recherche est insensible à la casse (opérateur LIKE SQL).
     *
     * @param  string $keyword Mot-clé à rechercher
     * @return Observation[]
     * @throws Exception En cas d'erreur PDO
     */
    public function findByKeyword(string $keyword): array {
        try {
            $search = '%' . $keyword . '%'; // entoure le mot-clé de jokers pour une recherche partielle
            $stmt   = $this->pdo->prepare(
                'SELECT * FROM t_observation
                 WHERE title LIKE :kw OR description LIKE :kw
                 ORDER BY created_at DESC'
            );
            $stmt->execute([':kw' => $search]);

            $observations = [];
            foreach ($stmt->fetchAll() as $row) {
                $observations[] = $this->hydrate($row);
            }
            return $observations;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la recherche d'observations (mot-clé='$keyword') : " . $e->getMessage());
        }
    }

    /**
     * Insère une nouvelle observation en base et met à jour sa clé primaire.
     *
     * @param  Observation $observation Objet Observation à insérer
     * @return Observation              L'objet Observation avec sa clé primaire renseignée
     * @throws Exception En cas d'erreur PDO
     */
    public function create(Observation $observation): Observation {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO t_observation (title, description, type, fk_category)
                 VALUES (:title, :description, :type, :fk_category)'
            );
            $stmt->execute([
                ':title'       => $observation->getTitle(),
                ':description' => $observation->getDescription(),
                ':type'        => $observation->getType(),
                ':fk_category' => $observation->getFkCategory(),
            ]);

            $observation->setPkObservation((int) $this->pdo->lastInsertId());
            return $observation;
        } catch (PDOException $e) {
            throw new Exception('Erreur lors de la création de l\'observation : ' . $e->getMessage());
        }
    }

    /**
     * Met à jour les champs d'une observation existante.
     * Retourne true si une ligne a bien été modifiée, false sinon.
     *
     * @param  Observation $observation Objet Observation avec les nouvelles valeurs
     * @return bool
     * @throws Exception En cas d'erreur PDO
     */
    public function update(Observation $observation): bool {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE t_observation
                 SET title        = :title,
                     description  = :description,
                     type         = :type,
                     fk_category  = :fk_category
                 WHERE pk_observation = :pk_observation'
            );
            $stmt->execute([
                ':title'        => $observation->getTitle(),
                ':description'  => $observation->getDescription(),
                ':type'         => $observation->getType(),
                ':fk_category'  => $observation->getFkCategory(),
                ':pk_observation' => $observation->getPkObservation(),
            ]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la mise à jour de l'observation (id={$observation->getPkObservation()}) : " . $e->getMessage());
        }
    }

    /**
     * Supprime une observation par son identifiant.
     * Retourne true si une ligne a bien été supprimée, false sinon.
     *
     * @param  int $pkObservation Clé primaire de l'observation à supprimer
     * @return bool
     * @throws Exception En cas d'erreur PDO
     */
    public function delete(int $pkObservation): bool {
        try {
            $stmt = $this->pdo->prepare(
                'DELETE FROM t_observation WHERE pk_observation = :pk_observation'
            );
            $stmt->execute([':pk_observation' => $pkObservation]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la suppression de l'observation (id=$pkObservation) : " . $e->getMessage());
        }
    }
}
