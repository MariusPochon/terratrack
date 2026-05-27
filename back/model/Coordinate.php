<?php

/**
 * Modèle représentant une coordonnée géographique liée à une observation.
 * Plusieurs coordonnées ordonnées forment un point (1 coordonnée) ou une zone (polygone).
 */
class Coordinate {

    /** @var int|null Clé primaire de la coordonnée en base de données */
    private ?int $pkCoordinate;

    /** @var int Clé étrangère vers l'observation parente */
    private int $fkObservation;

    /** @var float Latitude en degrés décimaux */
    private float $latitude;

    /** @var float Longitude en degrés décimaux */
    private float $longitude;

    /** @var int Position de la coordonnée dans la séquence (0-indexé) */
    private int $orderIndex;

    /**
     * Crée une nouvelle coordonnée.
     *
     * @param int      $fkObservation Identifiant de l'observation parente
     * @param float    $latitude      Latitude
     * @param float    $longitude     Longitude
     * @param int      $orderIndex    Ordre dans la séquence de coordonnées
     * @param int|null $pkCoordinate  Clé primaire (null si non encore persistée)
     */
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

    /** @return int|null */
    public function getPkCoordinate(): ?int { return $this->pkCoordinate; }
    /** @param int|null $pkCoordinate */
    public function setPkCoordinate(?int $pkCoordinate): void { $this->pkCoordinate = $pkCoordinate; }

    /** @return int */
    public function getFkObservation(): int { return $this->fkObservation; }
    /** @param int $fkObservation */
    public function setFkObservation(int $fkObservation): void { $this->fkObservation = $fkObservation; }

    /** @return float */
    public function getLatitude(): float { return $this->latitude; }
    /** @param float $latitude */
    public function setLatitude(float $latitude): void { $this->latitude = $latitude; }

    /** @return float */
    public function getLongitude(): float { return $this->longitude; }
    /** @param float $longitude */
    public function setLongitude(float $longitude): void { $this->longitude = $longitude; }

    /** @return int */
    public function getOrderIndex(): int { return $this->orderIndex; }
    /** @param int $orderIndex */
    public function setOrderIndex(int $orderIndex): void { $this->orderIndex = $orderIndex; }

    /**
     * Retourne les données de la coordonnée sous forme de tableau associatif.
     *
     * @return array
     */
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
