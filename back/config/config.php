<?php

/**
 * Configuration globale de l'application.
 * Définit les constantes de connexion à la base de données et les chemins importants.
 */

// Paramètres de connexion à la base de données MySQL
define('DB_HOST', 'db');
define('DB_NAME', 'testdb');
define('DB_USER', 'user');
define('DB_PASS', 'user');

// Chemin absolu vers le dossier de stockage des images uploadées
define('UPLOAD_PATH', __DIR__ . '/../../uploads/');

// URL de base de l'application
define('BASE_URL', 'http://localhost:8080/');
