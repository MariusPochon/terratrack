<?php

/**
 * Contrôleur gérant les opérations sur les catégories.
 * Expose la liste complète des catégories disponibles.
 */

require_once __DIR__ . '/../wrk/CategoryWorker.php';

class CategoryController
{
    /** @var CategoryWorker Worker utilisé pour les requêtes liées aux catégories */
    private CategoryWorker $categoryWorker;

    /**
     * Initialise le contrôleur avec son worker.
     */
    public function __construct()
    {
        $this->categoryWorker = new CategoryWorker();
    }

    /**
     * Retourne la liste de toutes les catégories au format JSON.
     * Répond avec 200 et un tableau d'objets catégorie.
     *
     * @return void
     */
    public function index(){
        $categories = $this->categoryWorker->findAll();

        // Convertit chaque objet Category en tableau associatif pour la sérialisation JSON
        $result = [];
        foreach ($categories as $category) {
            $data = $category->toArray();
            $result[] = $data;
        }
        http_response_code(200);
        echo json_encode($result);
    }
}
