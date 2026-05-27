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
            (int) $row['pk_image']
        );
    }

    // -------------------------------------------------------------------------
    // Queries
    // -------------------------------------------------------------------------

    /** @return Image[] */
    public function findByObservationId(int $fkObservation): array {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM t_image WHERE fk_observation = :fk_observation'
            );
            $stmt->execute([':fk_observation' => $fkObservation]);

            $images = [];
            foreach ($stmt->fetchAll() as $row) {
                $images[] = $this->hydrate($row);
            }
            return $images;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des images (observation=$fkObservation) : " . $e->getMessage());
        }
    }

    public function create(Image $image): Image {
        try {
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
        } catch (PDOException $e) {
            throw new Exception('Erreur lors de la création de l\'image : ' . $e->getMessage());
        }
    }

    public function delete(int $pkImage): void {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM t_image WHERE pk_image = :pk_image');
            $stmt->execute([':pk_image' => $pkImage]);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la suppression de l'image (id=$pkImage) : " . $e->getMessage());
        }
    }

    public function deleteByObservationId(int $fkObservation): void {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM t_image WHERE fk_observation = :fk_observation');
            $stmt->execute([':fk_observation' => $fkObservation]);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la suppression des images (observation=$fkObservation) : " . $e->getMessage());
        }
    }
}
