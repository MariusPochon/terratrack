<?php

/**
 * Modèle représentant une image associée à une observation.
 * Stocke le chemin relatif du fichier image sur le serveur.
 */
class Image {

    /** @var int|null Clé primaire de l'image en base de données */
    private ?int $pkImage;

    /** @var int Clé étrangère vers l'observation parente */
    private int $fkObservation;

    /** @var string Chemin relatif vers le fichier image (ex. "../uploads/1_0.jpg") */
    private string $filePath;

    /**
     * Crée une nouvelle image.
     *
     * @param int      $fkObservation Identifiant de l'observation parente
     * @param string   $filePath      Chemin relatif vers le fichier
     * @param int|null $pkImage       Clé primaire (null si non encore persistée)
     */
    public function __construct(
        int $fkObservation,
        string $filePath,
        ?int $pkImage = null
    ) {
        $this->fkObservation = $fkObservation;
        $this->filePath      = $filePath;
        $this->pkImage       = $pkImage;
    }

    /** @return int|null */
    public function getPkImage(): ?int { return $this->pkImage; }
    /** @param int|null $pkImage */
    public function setPkImage(?int $pkImage): void { $this->pkImage = $pkImage; }

    /** @return int */
    public function getFkObservation(): int { return $this->fkObservation; }
    /** @param int $fkObservation */
    public function setFkObservation(int $fkObservation): void { $this->fkObservation = $fkObservation; }

    /** @return string */
    public function getFilePath(): string { return $this->filePath; }
    /** @param string $filePath */
    public function setFilePath(string $filePath): void { $this->filePath = $filePath; }

    /**
     * Retourne les données de l'image sous forme de tableau associatif.
     *
     * @return array
     */
    public function toArray(): array {
        return [
            'pk_image'       => $this->pkImage,
            'fk_observation' => $this->fkObservation,
            'file_path'      => $this->filePath,
        ];
    }
}
