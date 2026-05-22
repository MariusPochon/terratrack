<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/Image.php';

class ImageWorker {

    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    // -------------------------------------------------------------------------
    // Hydration
    // -------------------------------------------------------------------------

    private function hydrate(array $row): Image {
        return new Image(
            (int) $row['fk_observation'],
            $row['file_path'],
            $row['uploaded_at'],
            (int) $row['pk_image']
        );
    }

    // -------------------------------------------------------------------------
    // Queries
    // -------------------------------------------------------------------------

    /** @return Image[] */
    public function findByObservationId(int $fkObservation): array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM t_image WHERE fk_observation = :fk_observation ORDER BY uploaded_at ASC'
        );
        $stmt->execute([':fk_observation' => $fkObservation]);

        $images = [];
        foreach ($stmt->fetchAll() as $row) {
            $images[] = $this->hydrate($row);
        }
        return $images;
    }

    public function create(Image $image): Image {
        $stmt = $this->pdo->prepare(
            'INSERT INTO t_image (fk_observation, file_path)
             VALUES (:fk_observation, :file_path)'
        );
        $stmt->execute([
            ':fk_observation' => $image->getFkObservation(),
            ':file_path'      => $image->getFilePath(),
        ]);

        $image->setPkImage((int) $this->pdo->lastInsertId());
        return $image;
    }

    public function delete(int $pkImage): void {
        $stmt = $this->pdo->prepare('DELETE FROM t_image WHERE pk_image = :pk_image');
        $stmt->execute([':pk_image' => $pkImage]);
    }

    public function deleteByObservationId(int $fkObservation): void {
        $stmt = $this->pdo->prepare('DELETE FROM t_image WHERE fk_observation = :fk_observation');
        $stmt->execute([':fk_observation' => $fkObservation]);
    }
}
