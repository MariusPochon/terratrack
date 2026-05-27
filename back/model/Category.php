<?php

/**
 * Modèle représentant une catégorie d'observation.
 * Une catégorie regroupe des observations de même nature et leur associe une couleur d'affichage.
 */
class Category {

    /** @var int|null Clé primaire de la catégorie en base de données */
    private ?int $pkCategory;

    /** @var string Nom de la catégorie */
    private string $name;

    /** @var string Couleur hexadécimale associée à la catégorie (ex. "#3388ff") */
    private string $color;

    /** @var string|null Description optionnelle de la catégorie */
    private ?string $description;

    /**
     * Crée une nouvelle catégorie.
     *
     * @param string      $name        Nom de la catégorie
     * @param string      $color       Couleur hexadécimale
     * @param string|null $description Description optionnelle
     * @param int|null    $pkCategory  Clé primaire (null si non encore persistée)
     */
    public function __construct(string $name, string $color, ?string $description = null, ?int $pkCategory = null) {
        $this->name        = $name;
        $this->color       = $color;
        $this->description = $description;
        $this->pkCategory = $pkCategory;
    }

    /** @return int|null */
    public function getPkCategory(): ?int { return $this->pkCategory; }
    /** @param int|null $pkCategory */
    public function setPkCategory(?int $pkCategory): void { $this->pkCategory = $pkCategory; }

    /** @return string */
    public function getName(): string { return $this->name; }
    /** @param string $name */
    public function setName(string $name): void { $this->name = $name; }

    /** @return string */
    public function getColor(): string { return $this->color; }
    /** @param string $color */
    public function setColor(string $color): void { $this->color = $color; }

    /** @return string|null */
    public function getDescription(): ?string { return $this->description; }
    /** @param string|null $description */
    public function setDescription(?string $description): void { $this->description = $description; }

    /**
     * Retourne les données de la catégorie sous forme de tableau associatif.
     *
     * @return array
     */
    public function toArray(): array {
        return [
            'pk_category' => $this->pkCategory,
            'name'         => $this->name,
            'color'        => $this->color,
            'description'  => $this->description,
        ];
    }
}
