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
            $row['updated_at'],
            (int) $row['pk_observation']
        );
    }

    // -------------------------------------------------------------------------
    // Queries
    // -------------------------------------------------------------------------

    /** @return Observation[] */
    public function findAll(): array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM t_observation ORDER BY created_at DESC'
        );
        $stmt->execute();

        $observations = [];
        foreach ($stmt->fetchAll() as $row) {
            $observations[] = $this->hydrate($row);
        }
        return $observations;
    }

    public function findById(int $pkObservation): ?Observation {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM t_observation WHERE pk_observation = :pk_observation'
        );
        $stmt->execute([':pk_observation' => $pkObservation]);

        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    /** Recherche sur title et description (insensible à la casse). */
    /** @return Observation[] */
    public function findByKeyword(string $keyword): array {
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
    }

    public function create(Observation $observation): Observation {
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
    }

    public function update(Observation $observation): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE t_observation
             SET title        = :title,
                 description  = :description,
                 type         = :type,
                 fk_category  = :fk_category,
                 updated_at   = NOW()
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
    }

    public function delete(int $pkObservation): bool {
        $stmt = $this->pdo->prepare(
            'DELETE FROM t_observation WHERE pk_observation = :pk_observation'
        );
        $stmt->execute([':pk_observation' => $pkObservation]);

        return $stmt->rowCount() > 0;
    }
}
