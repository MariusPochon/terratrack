<?php

/**
 * Contrôleur gérant l'authentification des utilisateurs.
 * Prend en charge la connexion, la déconnexion et la vérification de session.
 */

require_once __DIR__ . '/../wrk/UserWorker.php';

class AuthController {

    /** @var UserWorker Worker utilisé pour les requêtes liées aux utilisateurs */
    private UserWorker $userWorker;

    /**
     * Initialise le contrôleur avec son worker.
     */
    public function __construct()
    {
        $this->userWorker = new UserWorker();
    }

    /**
     * Connecte un utilisateur à partir des données POST (username, password).
     * Vérifie les identifiants et ouvre une session en cas de succès.
     * Répond avec 401 si les identifiants sont invalides.
     *
     * @return void
     */
    public function login(): void {
        $username = $_POST['username'] ?? null;
        $password = $_POST['password'] ?? null;

        $user = $this->userWorker->findByUsername($username);

        // Vérifie que l'utilisateur existe et que le mot de passe correspond au hash stocké
        if (!$user || !password_verify($password, $user->getPassword())) {
            http_response_code(401);
            echo json_encode(['error' => 'Identifiants invalides !']);
            return;
        }

        $_SESSION['user'] = $user->getPkUser();
        http_response_code(200);
        echo json_encode(['success' => true]);
    }

    /**
     * Déconnecte l'utilisateur en supprimant sa session.
     *
     * @return void
     */
    public function logout(): void {
        unset($_SESSION['user']);
        http_response_code(200);
        echo json_encode(['success' => true]);
    }

    /**
     * Vérifie si un utilisateur est actuellement connecté.
     * Retourne toujours 200 avec un booléen "authenticated".
     *
     * @return void
     */
    public function check(): void {
        if (isset($_SESSION['user'])) {
            http_response_code(200);
            echo json_encode(['authenticated' => true]);
        } else {
            http_response_code(200);
            echo json_encode(['authenticated' => false]);
        }
    }
}