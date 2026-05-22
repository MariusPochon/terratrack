<?php

class Category {

    private ?int $pkCategory;
    private string $name;
    private string $color;
    private ?string $description;

    public function __construct(string $name, string $color, ?string $description = null, ?int $pkCategory = null) {
        $this->name        = $name;
        $this->color       = $color;
        $this->description = $description;
        $this->pkCategory = $pkCategory;
    }

    public function getPkCategory(): ?int { return $this->pkCategory; }
    public function setPkCategory(?int $pkCategory): void { $this->pkCategory = $pkCategory; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }

    public function getColor(): string { return $this->color; }
    public function setColor(string $color): void { $this->color = $color; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): void { $this->description = $description; }

    public function toArray(): array {
        return [
            'pk_category' => $this->pkCategory,
            'name'         => $this->name,
            'color'        => $this->color,
            'description'  => $this->description,
        ];
    }
}
