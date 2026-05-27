<?php

/**
 * Modèle représentant un utilisateur de l'application.
 * Contient les informations d'identification (nom d'utilisateur et mot de passe hashé).
 */
class User {

    /** @var int|null Clé primaire de l'utilisateur en base de données */
    private ?int $pkUser;

    /** @var string Nom d'utilisateur unique */
    private string $username;

    /** @var string Mot de passe hashé (bcrypt) */
    private string $password;

    /**
     * Crée un nouvel utilisateur.
     *
     * @param string   $username Nom d'utilisateur
     * @param string   $password Mot de passe hashé
     * @param int|null $pkUser   Clé primaire (null si non encore persisté)
     */
    public function __construct(string $username, string $password, ?int $pkUser = null) {
        $this->username = $username;
        $this->password = $password;
        $this->pkUser   = $pkUser;
    }

    /** @return int|null */
    public function getPkUser(): ?int { return $this->pkUser; }
    /** @param int|null $pkUser */
    public function setPkUser(?int $pkUser): void { $this->pkUser = $pkUser; }

    /** @return string */
    public function getUsername(): string { return $this->username; }
    /** @param string $username */
    public function setUsername(string $username): void { $this->username = $username; }

    /** @return string */
    public function getPassword(): string { return $this->password; }
    /** @param string $password */
    public function setPassword(string $password): void { $this->password = $password; }

    /**
     * Retourne les données publiques de l'utilisateur sous forme de tableau associatif.
     * Le mot de passe est volontairement exclu.
     *
     * @return array
     */
    public function toArray(): array {
        return [
            'pk_user'  => $this->pkUser,
            'username' => $this->username,
        ];
    }
}
