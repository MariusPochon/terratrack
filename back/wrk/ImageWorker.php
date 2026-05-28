<?php

/**
 * Worker gérant les requêtes PDO liées aux images.
 * Fournit les méthodes de lecture, d'insertion et de suppression de la table t_image.
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/Image.php';

class ImageWorker {

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
     * Transforme une ligne de résultat PDO en objet Image.
     *
     * @param  array $row Ligne associative retournée par PDO
     * @return Image
     */
    private function hydrate(array $row): Image {
        return new Image(
            (int) $row['fk_observation'],
            $row['file_path'],
            (int) $row['pk_image']
        );
    }

    // -------------------------------------------------------------------------
    // Requêtes
    // -------------------------------------------------------------------------

    /**
     * Retourne une image par son identifiant, ou null si elle n'existe pas.
     * Utilisée avant suppression pour récupérer le chemin du fichier physique.
     *
     * @param  int $pkImage Clé primaire de l'image
     * @return Image|null
     * @throws Exception En cas d'erreur PDO
     */
    public function findById(int $pkImage): ?Image {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM t_image WHERE pk_image = :pk_image'
            );
            $stmt->execute([':pk_image' => $pkImage]);

            $row = $stmt->fetch();
            return $row ? $this->hydrate($row) : null;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération de l'image (id=$pkImage) : " . $e->getMessage());
        }
    }

    /**
     * Retourne toutes les images associées à une observation.
     *
     * @param  int $fkObservation Identifiant de l'observation parente
     * @return Image[]
     * @throws Exception En cas d'erreur PDO
     */
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

    /**
     * Insère une image en base et met à jour sa clé primaire.
     *
     * @param  Image $image Objet Image à insérer
     * @return Image        L'objet Image avec sa clé primaire renseignée
     * @throws Exception En cas d'erreur PDO
     */
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

    /**
     * Supprime une image par son identifiant.
     *
     * @param  int $pkImage Clé primaire de l'image
     * @return void
     * @throws Exception En cas d'erreur PDO
     */
    public function delete(int $pkImage): void {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM t_image WHERE pk_image = :pk_image');
            $stmt->execute([':pk_image' => $pkImage]);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la suppression de l'image (id=$pkImage) : " . $e->getMessage());
        }
    }

    /**
     * Supprime toutes les images associées à une observation.
     *
     * @param  int $fkObservation Identifiant de l'observation parente
     * @return void
     * @throws Exception En cas d'erreur PDO
     */
    public function deleteByObservationId(int $fkObservation): void {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM t_image WHERE fk_observation = :fk_observation');
            $stmt->execute([':fk_observation' => $fkObservation]);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la suppression des images (observation=$fkObservation) : " . $e->getMessage());
        }
    }
}
