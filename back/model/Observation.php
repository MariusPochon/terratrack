<?php

/**
 * Modèle représentant une observation terrain.
 * Une observation est de type "point" ou "zone", appartient à une catégorie
 * et peut être accompagnée de coordonnées géographiques et d'images.
 */
class Observation {

    /** @var int|null Clé primaire de l'observation en base de données */
    private ?int $pkObservation;

    /** @var string Titre de l'observation */
    private string $title;

    /** @var string|null Description textuelle optionnelle */
    private ?string $description;

    /** @var string Type géographique : "point" ou "zone" */
    private string $type;

    /** @var int Clé étrangère vers la catégorie associée */
    private int $fkCategory;

    /** @var string|null Date de création (format datetime MySQL) */
    private ?string $createdAt;

    /**
     * Crée une nouvelle observation.
     *
     * @param string      $title         Titre de l'observation
     * @param string      $type          Type : "point" ou "zone"
     * @param int         $fkCategory    Identifiant de la catégorie
     * @param string|null $description   Description optionnelle
     * @param string|null $createdAt     Date de création (null si non encore persistée)
     * @param int|null    $pkObservation Clé primaire (null si non encore persistée)
     */
    public function __construct(
        string $title,
        string $type,
        int $fkCategory,
        ?string $description = null,
        ?string $createdAt = null,
        ?int $pkObservation = null
    ) {
        $this->title         = $title;
        $this->type          = $type;
        $this->fkCategory   = $fkCategory;
        $this->description   = $description;
        $this->createdAt     = $createdAt;
        $this->pkObservation = $pkObservation;
    }

    /** @return int|null */
    public function getPkObservation(): ?int { return $this->pkObservation; }
    /** @param int|null $pkObservation */
    public function setPkObservation(?int $pkObservation): void { $this->pkObservation = $pkObservation; }

    /** @return string */
    public function getTitle(): string { return $this->title; }
    /** @param string $title */
    public function setTitle(string $title): void { $this->title = $title; }

    /** @return string|null */
    public function getDescription(): ?string { return $this->description; }
    /** @param string|null $description */
    public function setDescription(?string $description): void { $this->description = $description; }

    /** @return string */
    public function getType(): string { return $this->type; }
    /** @param string $type */
    public function setType(string $type): void { $this->type = $type; }

    /** @return int */
    public function getFkCategory(): int { return $this->fkCategory; }
    /** @param int $fkCategory */
    public function setFkCategory(int $fkCategory): void { $this->fkCategory = $fkCategory; }

    /** @return string|null */
    public function getCreatedAt(): ?string { return $this->createdAt; }
    /** @param string|null $createdAt */
    public function setCreatedAt(?string $createdAt): void { $this->createdAt = $createdAt; }

    /**
     * Retourne les données de l'observation sous forme de tableau associatif.
     *
     * @return array
     */
    public function toArray(): array {
        return [
            'pk_observation' => $this->pkObservation,
            'title'          => $this->title,
            'description'    => $this->description,
            'type'           => $this->type,
            'fk_category'   => $this->fkCategory,
            'created_at'     => $this->createdAt,
        ];
    }
}
