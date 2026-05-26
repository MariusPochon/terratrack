<?php

class Image {

    private ?int $pkImage;
    private int $fkObservation;
    private string $filePath;

    public function __construct(
        int $fkObservation,
        string $filePath,
        ?int $pkImage = null
    ) {
        $this->fkObservation = $fkObservation;
        $this->filePath      = $filePath;
        $this->pkImage       = $pkImage;
    }

    public function getPkImage(): ?int { return $this->pkImage; }
    public function setPkImage(?int $pkImage): void { $this->pkImage = $pkImage; }

    public function getFkObservation(): int { return $this->fkObservation; }
    public function setFkObservation(int $fkObservation): void { $this->fkObservation = $fkObservation; }

    public function getFilePath(): string { return $this->filePath; }
    public function setFilePath(string $filePath): void { $this->filePath = $filePath; }

    public function toArray(): array {
        return [
            'pk_image'       => $this->pkImage,
            'fk_observation' => $this->fkObservation,
            'file_path'      => $this->filePath,
        ];
    }
}
