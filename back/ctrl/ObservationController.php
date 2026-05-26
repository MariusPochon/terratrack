<?php
require_once __DIR__ . '/../wrk/ObservationWorker.php';
require_once __DIR__ . '/../wrk/CoordinateWorker.php';
require_once __DIR__ . '/../wrk/ImageWorker.php';
require_once __DIR__ . '/../wrk/CategoryWorker.php';
class ObservationController {
private CoordinateWorker $coordinatesWorker;
private ImageWorker $imageWorker;
private ObservationWorker $observationWorker;
private CategoryWorker $categoryWorker;

public function __construct()
{
    $this->coordinatesWorker = new CoordinateWorker();
    $this->imageWorker = new ImageWorker();
    $this->observationWorker = new ObservationWorker();
    $this->categoryWorker = new CategoryWorker();
}
    public function create(): void {

        // 1. Auth
        //$this->requireAuth();

        // 2. Récupérer les données brutes
        $title       = $_POST['title']       ?? null;
        $description = $_POST['description'] ?? null;
        $type        = $_POST['type']        ?? null;
        $fkCategory  = $_POST['fk_category'] ?? null;
        $coordsJson  = $_POST['coordinates'] ?? '[]';

        // 3. Validation
        if (!$title || !$type || !$fkCategory) {
            http_response_code(400);
            echo json_encode(['error' => 'Il manque des champs obligatoires !']);
            return;
        }

        $pdo = Database::getInstance()->getConnection();

        try {
            $pdo->beginTransaction();

            // 4. Observation
            $observation = new Observation($title, $type, (int)$fkCategory, $description);
            $observation = $this->observationWorker->create($observation);

            // 5. Coordonnées
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

            // 6. Images
            if (!empty($_FILES['images']['name'][0])) {
                $uploadDir = __DIR__ . '/../../uploads/observations/';
                for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                    if ($_FILES['images']['error'][$i] !== 0) continue;
                    $extension = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                    $fileName  = $observation->getPkObservation() . '_' . $i . '.' . $extension;
                    $filePath  = 'uploads/observations/' . $fileName;
                    move_uploaded_file($_FILES['images']['tmp_name'][$i], $uploadDir . $fileName);
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

    private function requireAuth(): void {
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }

    public function index(): void {

            $observations = $this->observationWorker->findAll();

            $result = [];
            foreach ($observations as $observation) {
                $coords = $this->coordinatesWorker->findByObservationId($observation->getPkObservation());
                $images = $this->imageWorker->findByObservationId($observation->getPkObservation());

                $data = $observation->toArray();
                $category = $this->categoryWorker->findById($observation->getFkCategory()); //récupèrer la catégorie de l'observation
                $data['category'] = $category ? $category->toArray() : null;
                $data['coordinates'] = array_map(fn($c) => $c->toArray(), $coords);
                $data['images']      = array_map(fn($i) => $i->toArray(), $images);

                $result[] = $data;
            }

            http_response_code(200);
            echo json_encode($result);
        }

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

        public function delete(): void {

            //$this->requireAuth();

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

        public function update(): void {
            //$this->requireAuth();
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