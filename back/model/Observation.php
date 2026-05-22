<?php

class Observation {

    private ?int $pkObservation;
    private string $title;
    private ?string $description;
    private string $type;
    private int $fkCategory;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        string $title,
        string $type,
        int $fkCategory,
        ?string $description = null,
        ?string $createdAt = null,
        ?string $updatedAt = null,
        ?int $pkObservation = null
    ) {
        $this->title         = $title;
        $this->type          = $type;
        $this->fkCategory   = $fkCategory;
        $this->description   = $description;
        $this->createdAt     = $createdAt;
        $this->updatedAt     = $updatedAt;
        $this->pkObservation = $pkObservation;
    }

    public function getPkObservation(): ?int { return $this->pkObservation; }
    public function setPkObservation(?int $pkObservation): void { $this->pkObservation = $pkObservation; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): void { $this->title = $title; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): void { $this->description = $description; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): void { $this->type = $type; }

    public function getFkCategory(): int { return $this->fkCategory; }
    public function setFkCategory(int $fkCategory): void { $this->fkCategory = $fkCategory; }

    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): void { $this->createdAt = $createdAt; }

    public function getUpdatedAt(): ?string { return $this->updatedAt; }
    public function setUpdatedAt(?string $updatedAt): void { $this->updatedAt = $updatedAt; }

    public function toArray(): array {
        return [
            'pk_observation' => $this->pkObservation,
            'title'          => $this->title,
            'description'    => $this->description,
            'type'           => $this->type,
            'fk_category'   => $this->fkCategory,
            'created_at'     => $this->createdAt,
            'updated_at'     => $this->updatedAt,
        ];
    }
}
