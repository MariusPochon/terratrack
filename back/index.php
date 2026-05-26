<?php

session_start();
header('Content-Type: application/json');
//header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Origin: http://localhost:8080');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

//preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config/Database.php';

$action = $_GET['action'] ?? null;

switch ($action) {

    case 'getObservations':
        require_once __DIR__ . '/ctrl/ObservationController.php';
        (new ObservationController())->index();
        break;

    case 'createObservation':
        require_once __DIR__ . '/ctrl/ObservationController.php';
        (new ObservationController())->create();
        break;

    case 'login':
        require_once __DIR__ . '/ctrl/AuthController.php';
        (new AuthController())->login();
        break;

    case 'logout':
        require_once __DIR__ . '/ctrl/AuthController.php';
        (new AuthController())->logout();
        break;

    case 'checkAuth':
        require_once __DIR__ . '/ctrl/AuthController.php';
        (new AuthController())->check();
        break;

    case 'searchObservations':
        require_once __DIR__ . '/ctrl/ObservationController.php';
        (new ObservationController())->search();
        break;

    case 'deleteObservation':
        require_once __DIR__ . '/ctrl/ObservationController.php';
        (new ObservationController())->delete();
        break;

    case 'updateObservation':
        require_once __DIR__ . '/ctrl/ObservationController.php';
        (new ObservationController())->update();
        break;

    case 'getCategories':
        require_once __DIR__ . '/ctrl/CategoryController.php';
        (new CategoryController())->index();
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
        break;
}