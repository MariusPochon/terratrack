<?php

class User {

    private ?int $pkUser;
    private string $username;
    private string $password;

    public function __construct(string $username, string $password, ?int $pkUser = null) {
        $this->username = $username;
        $this->password = $password;
        $this->pkUser   = $pkUser;
    }

    public function getPkUser(): ?int { return $this->pkUser; }
    public function setPkUser(?int $pkUser): void { $this->pkUser = $pkUser; }

    public function getUsername(): string { return $this->username; }
    public function setUsername(string $username): void { $this->username = $username; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): void { $this->password = $password; }

    public function toArray(): array {
        return [
            'pk_user'  => $this->pkUser,
            'username' => $this->username,
        ];
    }
}
