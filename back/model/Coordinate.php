<?php

class Coordinate {

    private ?int $pkCoordinate;
    private int $fkObservation;
    private float $latitude;
    private float $longitude;
    private int $orderIndex;

    public function __construct(
        int $fkObservation,
        float $latitude,
        float $longitude,
        int $orderIndex,
        ?int $pkCoordinate = null
    ) {
        $this->fkObservation = $fkObservation;
        $this->latitude      = $latitude;
        $this->longitude     = $longitude;
        $this->orderIndex    = $orderIndex;
        $this->pkCoordinate  = $pkCoordinate;
    }

    public function getPkCoordinate(): ?int { return $this->pkCoordinate; }
    public function setPkCoordinate(?int $pkCoordinate): void { $this->pkCoordinate = $pkCoordinate; }

    public function getFkObservation(): int { return $this->fkObservation; }
    public function setFkObservation(int $fkObservation): void { $this->fkObservation = $fkObservation; }

    public function getLatitude(): float { return $this->latitude; }
    public function setLatitude(float $latitude): void { $this->latitude = $latitude; }

    public function getLongitude(): float { return $this->longitude; }
    public function setLongitude(float $longitude): void { $this->longitude = $longitude; }

    public function getOrderIndex(): int { return $this->orderIndex; }
    public function setOrderIndex(int $orderIndex): void { $this->orderIndex = $orderIndex; }

    public function toArray(): array {
        return [
            'pk_coordinate'  => $this->pkCoordinate,
            'fk_observation' => $this->fkObservation,
            'latitude'       => $this->latitude,
            'longitude'      => $this->longitude,
            'order_index'    => $this->orderIndex,
        ];
    }
}
