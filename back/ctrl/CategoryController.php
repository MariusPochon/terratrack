<?php
require_once __DIR__ . '/../wrk/CategoryWorker.php';

class CategoryController
{
    private CategoryWorker $categoryWorker;
    public function __construct()
    {
        $this->categoryWorker = new CategoryWorker();
    }
    public function index(){
        $categories = $this->categoryWorker->findAll();

        $result = [];
        foreach ($categories as $category) {
            $data = $category->toArray();
            $result[] = $data;
        }
        http_response_code(200);
        echo json_encode($result);
    }
}