<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/Coordinate.php';

class CoordinateWorker {

    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    // -------------------------------------------------------------------------
    // Hydration
    // -------------------------------------------------------------------------

    private function hydrate(array $row): Coordinate {
        return new Coordinate(
            (int)   $row['fk_observation'],
            (float) $row['latitude'],
            (float) $row['longitude'],
            (int)   $row['order_index'],
            (int)   $row['pk_coordinate']
        );
    }

    // -------------------------------------------------------------------------
    // Queries
    // -------------------------------------------------------------------------

    /** @return Coordinate[] */
    public function findByObservationId(int $fkObservation): array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM t_coordinate
             WHERE fk_observation = :fk_observation
             ORDER BY order_index ASC'
        );
        $stmt->execute([':fk_observation' => $fkObservation]);

        $coordinates = [];
        foreach ($stmt->fetchAll() as $row) {
            $coordinates[] = $this->hydrate($row);
        }
        return $coordinates;
    }

    /**
     * Insère un tableau de Coordinate en une passe.
     * @param Coordinate[] $coordinates
     */
    public function createMany(array $coordinates): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO t_coordinate (fk_observation, latitude, longitude, order_index)
             VALUES (:fk_observation, :latitude, :longitude, :order_index)'
        );

        foreach ($coordinates as $coordinate) {
            $stmt->execute([
                ':fk_observation' => $coordinate->getFkObservation(),
                ':latitude'       => $coordinate->getLatitude(),
                ':longitude'      => $coordinate->getLongitude(),
                ':order_index'    => $coordinate->getOrderIndex(),
            ]);
            $coordinate->setPkCoordinate((int) $this->pdo->lastInsertId());
        }
    }

    public function deleteByObservationId(int $fkObservation): void {
        $stmt = $this->pdo->prepare(
            'DELETE FROM t_coordinate WHERE fk_observation = :fk_observation'
        );
        $stmt->execute([':fk_observation' => $fkObservation]);
    }
}
