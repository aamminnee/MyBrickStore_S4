<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\MosaicModel;

class ApiController extends Controller {

    public function getRandomMosaic() {
        $headers = getallheaders();
        $apiKey = $headers['X-API-KEY'] ?? '';
        
        if ($apiKey !== 'SUPER_CLE_SECRETE_123') {
            http_response_code(403);
            echo json_encode(["error" => "Accès refusé"]);
            return;
        }

        $model = new MosaicModel();
        $data = $model->getRandomGameMosaic();

        if (!$data) {
            http_response_code(404);
            echo json_encode(["error" => "Aucun pavage trouvé"]);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode($data);
    }
}