<?php
require_once __DIR__ . '/../wrk/UserWorker.php';
class AuthController {
    private UserWorker $userWorker;
    public function __construct()
    {
        $this->userWorker = new UserWorker();
    }
    public function login(): void {
        $username = $_POST['username'] ?? null;
        $password = $_POST['password'] ?? null;

        $user = $this->userWorker->findByUsername($username);

        if (!$user || !password_verify($password, $user->getPassword())) {
            http_response_code(401);
            echo json_encode(['error' => 'Identifiants invalides !']);
            return;
        }

        $_SESSION['user'] = $user->getPkUser();
        http_response_code(200);
        echo json_encode(['success' => true]);
    }

    public function logout(): void {
        unset($_SESSION['user']);
        http_response_code(200);
        echo json_encode(['success' => true]);
    }

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