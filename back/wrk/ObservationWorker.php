<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/Observation.php';

class ObservationWorker {

    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    // -------------------------------------------------------------------------
    // Hydration
    // -------------------------------------------------------------------------

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
    // Queries
    // -------------------------------------------------------------------------

    /** @return Observation[] */
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

    /** Recherche sur title et description (insensible à la casse). */
    /** @return Observation[] */
    public function findByKeyword(string $keyword): array {
        try {
            $search = '%' . $keyword . '%';
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
