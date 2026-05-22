# TerraTrack – Contexte projet

## Stack
- Frontend : HTML, CSS, JavaScript (vanilla)
- Backend : PHP (pas de framework)
- DB : MySQL
- Pattern : View > Controller > Worker > Model (beans)

## Conventions
- Code : anglais
- Données utilisateur : français
- Nommage DB : t_ (tables), pk_ (clé primaire), fk_ (clé étrangère)
- Nommage fichiers : PascalCase pour les classes PHP, camelCase pour JS
- Pas de xxId pour les paremètres d'ID toujours fkXx ou pkXx (pour les paremetres et les query par exemple)

## Structure
/front
    /view      → fichiers HTML
    /ctrl      → logique JS par page (HomeController.js, etc.)
    /wrk       → workers JS (ObservationWorker.js, etc.)
    /css

/back
    index.php          → point d'entrée + routing
    /config
        config.php       → constantes
        Database.php     → connexion PDO singleton
    /ctrl              → controllers PHP
    /wrk               → workers PHP (accès DB)
    /model             → beans PHP

## DB – tables
t_user (pk_user, username, password)
t_category (pk_category, name, color, description)
t_observation (pk_observation, title, description, type ENUM('point','zone'), fk_category, created_at, updated_at)
t_coordinate (pk_coordinate, fk_observation, latitude DECIMAL(10,7), longitude DECIMAL(10,7), order_index)
t_image (pk_image, fk_observation, file_path, uploaded_at)

## Règles importantes
- Pas de framework PHP (pas de Laravel, pas de Symfony)
- Pas de framework JS (pas de React, pas de Vue)
- PDO avec requêtes préparées OBLIGATOIRE (sécurité)
- Validation côté serveur dans les controllers
- Auth vérifiée dans chaque controller qui le nécessite (pas de middleware)
- Backend retourne uniquement du JSON
- Frontend fait des fetch() vers /back/index.php?action=xxx