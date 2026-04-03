<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ImagesModel;
use App\Models\TranslationModel;

/**
 * class imagescontroller
 * * handles the landing page, image upload process, and daily image feature.
 * accessible to visitors, but upload features are restricted to active members.
 * * @package App\Controllers
 */
class ImagesController extends Controller {

    /** * @var array key/value pair of translations. 
     */
    private $translations;

    /**
     * constructor.
     * initializes translation services.
     */
    public function __construct() {
        $lang = $_SESSION['lang'] ?? 'fr';
        $translation_model = new TranslationModel();
        $this->translations = $translation_model->getTranslations($lang);
    }

    /**
     * retrieves the physical path of the daily image based on the current day of the month.
     *
     * @return string|null the absolute path to the image or null if not found
     */
    private function getDailyImagePath() {
        // get the current day of the month without leading zeros (1 to 31)
        $day = (int)date('j');
        // define the path to the daily images folder at the root of the project
        $dir = ROOT . '/day_image';
        
        // check if the directory exists
        if (is_dir($dir)) {
            // scan the directory and remove . and .. entries
            $files = array_diff(scandir($dir), ['.', '..']);
            // reindex the array
            $files = array_values($files);
            // sort the files naturally so 10 comes after 2
            natsort($files);
            // reindex again after sorting
            $files = array_values($files);
            
            // if the image for the current day exists (index is day - 1)
            if (isset($files[$day - 1])) {
                return $dir . '/' . $files[$day - 1];
            }
        }
        return null;
    }

    /**
     * serves the daily image directly to the browser.
     * * @return void
     */
    public function getDailyImage() {
        // get the path of today's image
        $path = $this->getDailyImagePath();
        
        if ($path && file_exists($path)) {
            // determine the mime type of the image
            $mimeType = mime_content_type($path);
            
            // clean the output buffer
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // set the appropriate content-type header
            header("Content-Type: " . $mimeType);
            // output the file content
            readfile($path);
            exit;
        }
        
        // return a 404 error if the image is not found
        http_response_code(404);
        exit;
    }

    /**
     * displays the landing page (images view).
     * accessible to everyone (public page).
     * view logic will determine if upload form is shown.
     *
     * @return void
     */
    public function index() {
        // check if there is a daily image available
        $hasDailyImage = ($this->getDailyImagePath() !== null);

        $this->render('images_views', [
            't' => $this->translations,
            'css' => 'images_views.css',
            'is_logged' => isset($_SESSION['user_id']),
            'is_active' => ($_SESSION['status'] ?? '') === 'valide',
            'hasDailyImage' => $hasDailyImage
        ]);
    }

    /**
     * handles file uploads via ajax/post.
     * strictly restricted to logged-in users with active accounts.
     *
     * @return void
     */
    public function upload() {
        // set response content type to json
        header('Content-Type: application/json');

        // check if the request is post and contains the file
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image_input'])) {
            $file = $_FILES['image_input'];

            // check for upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['status' => 'error', 'message' => 'Erreur upload: ' . $file['error']]);
                exit;
            }

            // define allowed mime types
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            $fileType = mime_content_type($file['tmp_name']);
            
            // validate the mime type
            if (!in_array($fileType, $allowed)) {
                echo json_encode(['status' => 'error', 'message' => 'Format invalide (JPG, PNG, WEBP uniquement)']);
                exit;
            }

            // read the binary data of the uploaded image
            $imgData = file_get_contents($file['tmp_name']);
            $fileName = $file['name'];

            try {
                $model = new ImagesModel();
                $userId = $_SESSION['user_id'] ?? null;
                // save the image in the database
                $imageId = $model->saveCustomerImage($userId, $imgData, $fileName, $fileType);

                // set session variables for cropping
                $_SESSION['can_crop'] = true;
                $_SESSION['current_image_id'] = $imageId;

                // check if the image is marked as a daily image for the discount
                if (isset($_POST['is_daily']) && $_POST['is_daily'] === '1') {
                    // store the discount flag in the session
                    $_SESSION['daily_discount_' . $imageId] = true;
                }

                // return success response with redirection url
                echo json_encode([
                    'status' => 'success', 
                    'id_image' => $imageId,
                    'redirect' => ($_ENV['BASE_URL'] ?? '') . '/cropImages' 
                ]);
            } catch (\Exception $e) {
                // return error response if database insertion fails
                echo json_encode(['status' => 'error', 'message' => 'Erreur BDD : ' . $e->getMessage()]);
            }

        } else {
            // return error response if no file was received
            echo json_encode(['status' => 'error', 'message' => 'Aucun fichier reçu']);
        }
        exit;
    }
    
    /**
     * retrieves and displays raw image data from the database.
     *
     * @param int $id the image identifier
     * @return void
     */
    public function view($id) {
        $id = (int)$id;

        // return 404 if the id is invalid
        if ($id <= 0) {
            http_response_code(404);
            exit;
        }

        $model = new ImagesModel();
        $image = $model->getImageById($id);

        // return 404 if the image is not found or empty
        if (!$image || empty($image->file)) {
            http_response_code(404);
            exit;
        }
        
        // clean the output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header("Content-Type: " . $image->file_type);
        echo $image->file;
        exit;
    }
}