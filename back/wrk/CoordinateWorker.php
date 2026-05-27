<?php

/**
 * Worker gérant les requêtes PDO liées aux coordonnées géographiques.
 * Fournit les méthodes de lecture, d'insertion et de suppression de la table t_coordinate.
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/Coordinate.php';

class CoordinateWorker {

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
     * Transforme une ligne de résultat PDO en objet Coordinate.
     *
     * @param  array $row Ligne associative retournée par PDO
     * @return Coordinate
     */
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
    // Requêtes
    // -------------------------------------------------------------------------

    /**
     * Retourne toutes les coordonnées d'une observation, triées par order_index croissant.
     *
     * @param  int $fkObservation Identifiant de l'observation parente
     * @return Coordinate[]
     * @throws Exception En cas d'erreur PDO
     */
    public function findByObservationId(int $fkObservation): array {
        try {
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
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des coordonnées (observation=$fkObservation) : " . $e->getMessage());
        }
    }

    /**
     * Insère un tableau de coordonnées en base en une seule passe.
     * Met à jour la clé primaire de chaque objet après insertion.
     *
     * @param  Coordinate[] $coordinates Tableau d'objets Coordinate à insérer
     * @return void
     * @throws Exception En cas d'erreur PDO
     */
    public function createMany(array $coordinates): void {
        try {
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
        } catch (PDOException $e) {
            throw new Exception('Erreur lors de l\'insertion des coordonnées : ' . $e->getMessage());
        }
    }

    /**
     * Supprime toutes les coordonnées associées à une observation.
     *
     * @param  int $fkObservation Identifiant de l'observation parente
     * @return void
     * @throws Exception En cas d'erreur PDO
     */
    public function deleteByObservationId(int $fkObservation): void {
        try {
            $stmt = $this->pdo->prepare(
                'DELETE FROM t_coordinate WHERE fk_observation = :fk_observation'
            );
            $stmt->execute([':fk_observation' => $fkObservation]);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la suppression des coordonnées (observation=$fkObservation) : " . $e->getMessage());
        }
    }
}
