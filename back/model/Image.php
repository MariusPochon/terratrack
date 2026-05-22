<?php

class Image {

    private ?int $pkImage;
    private int $fkObservation;
    private string $filePath;
    private ?string $uploadedAt;

    public function __construct(
        int $fkObservation,
        string $filePath,
        ?string $uploadedAt = null,
        ?int $pkImage = null
    ) {
        $this->fkObservation = $fkObservation;
        $this->filePath      = $filePath;
        $this->uploadedAt    = $uploadedAt;
        $this->pkImage       = $pkImage;
    }

    public function getPkImage(): ?int { return $this->pkImage; }
    public function setPkImage(?int $pkImage): void { $this->pkImage = $pkImage; }

    public function getFkObservation(): int { return $this->fkObservation; }
    public function setFkObservation(int $fkObservation): void { $this->fkObservation = $fkObservation; }

    public function getFilePath(): string { return $this->filePath; }
    public function setFilePath(string $filePath): void { $this->filePath = $filePath; }

    public function getUploadedAt(): ?string { return $this->uploadedAt; }
    public function setUploadedAt(?string $uploadedAt): void { $this->uploadedAt = $uploadedAt; }

    public function toArray(): array {
        return [
            'pk_image'       => $this->pkImage,
            'fk_observation' => $this->fkObservation,
            'file_path'      => $this->filePath,
            'uploaded_at'    => $this->uploadedAt,
        ];
    }
}
