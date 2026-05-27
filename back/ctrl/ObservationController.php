<?php

/**
 * Contrôleur gérant toutes les opérations CRUD sur les observations.
 * Coordonne la création, la lecture, la mise à jour et la suppression
 * des observations ainsi que leurs coordonnées et images associées.
 */

require_once __DIR__ . '/../wrk/ObservationWorker.php';
require_once __DIR__ . '/../wrk/CoordinateWorker.php';
require_once __DIR__ . '/../wrk/ImageWorker.php';
require_once __DIR__ . '/../wrk/CategoryWorker.php';

class ObservationController {

    /** @var CoordinateWorker Worker pour les coordonnées géographiques */
    private CoordinateWorker $coordinatesWorker;

    /** @var ImageWorker Worker pour les images */
    private ImageWorker $imageWorker;

    /** @var ObservationWorker Worker pour les observations */
    private ObservationWorker $observationWorker;

    /** @var CategoryWorker Worker pour les catégories */
    private CategoryWorker $categoryWorker;

    /**
     * Initialise le contrôleur avec tous les workers nécessaires.
     */
    public function __construct()
    {
        $this->coordinatesWorker = new CoordinateWorker();
        $this->imageWorker = new ImageWorker();
        $this->observationWorker = new ObservationWorker();
        $this->categoryWorker = new CategoryWorker();
    }

    /**
     * Crée une nouvelle observation avec ses coordonnées et ses images.
     * Utilise une transaction pour garantir l'intégrité des données.
     * Lit les données depuis $_POST et les fichiers depuis $_FILES.
     * Répond avec 201 et l'identifiant créé en cas de succès, 400 si des champs sont manquants.
     *
     * @return void
     */
    public function create(): void {

        // 1. Check l'authentification
        $this->requireAuth();

        // 2. Récupérer les données brutes
        $title       = $_POST['title']       ?? null;
        $description = $_POST['description'] ?? null;
        $type        = $_POST['type']        ?? null;
        $fkCategory  = $_POST['fk_category'] ?? null;
        $coordsJson  = $_POST['coordinates'] ?? '[]';

        // 3. Validation des champs obligatoires
        if (!$title || !$type || !$fkCategory) {
            http_response_code(400);
            echo json_encode(['error' => 'Il manque des champs obligatoires !']);
            return;
        }

        $pdo = Database::getInstance()->getConnection();

        try {
            $pdo->beginTransaction();

            // 4. Insertion de l'observation principale
            $observation = new Observation($title, $type, (int)$fkCategory, $description);
            $observation = $this->observationWorker->create($observation);

            // 5. Décodage et insertion des coordonnées géographiques
            $coords = json_decode($coordsJson, true);
            $coordinates = [];
            foreach ($coords as $index => $point) {
                $coordinates[] = new Coordinate(
                    $observation->getPkObservation(),
                    (float) $point['latitude'],
                    (float) $point['longitude'],
                    (int)   $index
                );
            }
            $this->coordinatesWorker->createMany($coordinates);

            // 6. Upload et enregistrement des images si présentes
            if (!empty($_FILES['images']['name'][0])) {
                for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                    if ($_FILES['images']['error'][$i] !== 0) continue; // ignore les fichiers en erreur
                    $extension = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                    $fileName  = $observation->getPkObservation() . '_' . $i . '.' . $extension;
                    $filePath  = '../uploads/' . $fileName;
                    move_uploaded_file($_FILES['images']['tmp_name'][$i], UPLOAD_PATH . $fileName);
                    $this->imageWorker->create(new Image($observation->getPkObservation(), $filePath));
                }
            }

            $pdo->commit();
            http_response_code(201);
            echo json_encode(['success' => true, 'id' => $observation->getPkObservation()]);

        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Transaction failed']);
        }

    }

    /**
     * Vérifie que l'utilisateur est connecté, interrompt la requête avec 401 sinon.
     *
     * @return void
     */
    private function requireAuth(): void {
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }

    /**
     * Retourne la liste complète des observations avec leurs coordonnées, images et catégorie.
     * Répond avec 200 et un tableau JSON.
     *
     * @return void
     */
    public function index(): void {

            $observations = $this->observationWorker->findAll();

            $result = [];
            foreach ($observations as $observation) {
                $coords = $this->coordinatesWorker->findByObservationId($observation->getPkObservation());
                $images = $this->imageWorker->findByObservationId($observation->getPkObservation());

                $data = $observation->toArray();
                $category = $this->categoryWorker->findById($observation->getFkCategory()); // récupère la catégorie de l'observation
                $data['category'] = $category ? $category->toArray() : null;
                $data['coordinates'] = array_map(fn($c) => $c->toArray(), $coords);
                $data['images']      = array_map(fn($i) => $i->toArray(), $images);

                $result[] = $data;
            }

            http_response_code(200);
            echo json_encode($result);
        }

        /**
         * Recherche des observations par mot-clé dans le titre et la description.
         * Lit le paramètre "q" depuis $_GET. Requiert au minimum 3 caractères.
         * Répond avec 400 si la requête est trop courte, 200 avec les résultats sinon.
         *
         * @return void
         */
        public function search(): void
        {
            $query = $_GET['q'] ?? '';
            if (strlen($query) < 3) {
                http_response_code(400);
                echo json_encode(['error' => 'La requête doit contenir au moins 3 caractères !']);
                return;
            }

            $observations = $this->observationWorker->findByKeyword($query);

            $result = [];
            foreach ($observations as $obs) {
                $coords = $this->coordinatesWorker->findByObservationId($obs->getPkObservation());
                $images = $this->imageWorker->findByObservationId($obs->getPkObservation());

                $data = $obs->toArray();
                $data['coordinates'] = array_map(fn($c) => $c->toArray(), $coords);
                $data['images']      = array_map(fn($i) => $i->toArray(), $images);

                $result[] = $data;
            }

            http_response_code(200);
            echo json_encode($result);
        }

        /**
         * Supprime une observation identifiée par "id" dans $_POST.
         * Requiert une session active. Répond avec 400 si l'id est absent, 200 en cas de succès.
         *
         * @return void
         */
        public function delete(): void {

            $this->requireAuth();

            $id = $_POST['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Il manque des champs obligatoires !']);
                return;
            }

            $this->observationWorker->delete((int)$id);
            http_response_code(200);
            echo json_encode(['success' => true]);
        }

        /**
         * Met à jour les champs textuels d'une observation existante.
         * Requiert une session active. Lit les données depuis $_POST.
         * Répond avec 400 si des champs sont manquants, 404 si l'observation est introuvable.
         *
         * @return void
         */
        public function update(): void {
            $this->requireAuth();
            $id = $_POST['id'] ?? null;
            $title = $_POST['title'] ?? null;
            $description = $_POST['description'] ?? null;
            $type = $_POST['type'] ?? null;
            $fkCategory = $_POST['fk_category'] ?? null;

            if (!$id || !$title || !$type || !$fkCategory) {
                http_response_code(400);
                echo json_encode(['error' => 'Il manque des champs obligatoires !']);
                return;
            }

            $observation = $this->observationWorker->findById((int)$id);
            if (!$observation) {
                http_response_code(404);
                echo json_encode(['error' => 'Observation introuvable']);
                return;
            }

            $observation->setTitle($title);
            $observation->setDescription($description);
            $observation->setType($type);
            $observation->setFkCategory((int)$fkCategory);
            $this->observationWorker->update($observation);
            http_response_code(200);
            echo json_encode(['success' => true]);

        }

}
