<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ImagesModel;
use App\Models\TranslationModel;

/**
 * Class ImagesController
 * 
 ** Handles the landing page and image upload process
 ** Accessible to visitors, but upload features are restricted to active members
 * 
 * @package App\Controllers
 */
class ImagesController extends Controller {

    /** @var array Key/Value pair of translations. */
    private $translations;

    /**
     * Constructor.
     * Initializes translation services
     */
    public function __construct() {
        $lang = $_SESSION['lang'] ?? 'fr';
        $translation_model = new TranslationModel();
        $this->translations = $translation_model->getTranslations($lang);
    }

    /**
     * displays the landing page (images view)
     * * accessible to everyone (public page)
     * * view logic will determine if upload form is shown
     *
     * @return void
     */
    public function index() {
        $this->render('images_views', [
            't' => $this->translations,
            'css' => 'images_views.css',
            'is_logged' => isset($_SESSION['user_id']),
            'is_active' => ($_SESSION['status'] ?? '') === 'valide'
        ]);
    }

    /**
     * handles file uploads via ajax/post
     * * strictly restricted to logged-in users with active accounts
     *
     * @return void
     */
    public function upload() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image_input'])) {
            $file = $_FILES['image_input'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['status' => 'error', 'message' => 'Erreur upload: ' . $file['error']]);
                exit;
            }

            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            $fileType = mime_content_type($file['tmp_name']);
            
            if (!in_array($fileType, $allowed)) {
                echo json_encode(['status' => 'error', 'message' => 'Format invalide (JPG, PNG, WEBP uniquement)']);
                exit;
            }

            $imgData = file_get_contents($file['tmp_name']);
            $fileName = $file['name'];

            try {
                $model = new ImagesModel();
                $userId = $_SESSION['user_id'] ?? null;
                $imageId = $model->saveCustomerImage($userId, $imgData, $fileName, $fileType);

                $_SESSION['can_crop'] = true;
                $_SESSION['current_image_id'] = $imageId;

                echo json_encode([
                    'status' => 'success', 
                    'id_image' => $imageId,
                    'redirect' => ($_ENV['BASE_URL'] ?? '') . '/cropImages' 
                ]);
            } catch (\Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Erreur BDD : ' . $e->getMessage()]);
            }

        } else {
            echo json_encode(['status' => 'error', 'message' => 'Aucun fichier reçu']);
        }
        exit;
    }
    
    /**
     * Retrieves and displays raw image data from the database.
     *
     * @param int $id
     * @return void
     */
    public function view($id) {
        $id = (int)$id;

        if ($id <= 0) {
            http_response_code(404);
            exit;
        }

        $model = new ImagesModel();
        $image = $model->getImageById($id);

        if (!$image || empty($image->file)) {
            http_response_code(404);
            exit;
        }
        
        if (ob_get_level()) {
            ob_end_clean();
        }

        header("Content-Type: " . $image->file_type);
        echo $image->file;
        exit;
    }
}