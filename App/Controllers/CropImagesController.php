<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\TranslationModel;
use App\Models\ImagesModel;

/**
 * Class CropImagesController
 * 
 ** Handles the image resizing and cropping process
 ** Uses an external java jar (legotools) to process the image according to the board size
 * 
 * @package App\Controllers
 */
class CropImagesController extends Controller {

    /** @var array Key/Value pair of translations. */
    private $translations;

    /**
     * Initializes the controller and loads translation strings
     */
    public function __construct() {
        $lang = $_SESSION['lang'] ?? 'fr';
        $translation_model = new TranslationModel();
        $this->translations = $translation_model->getTranslations($lang);
    }

    /**
     * Displays the cropping interface for the most recently uploaded image
     *
     * @return void
     */
    public function index() {
        if (!isset($_SESSION['can_crop']) || $_SESSION['can_crop'] !== true) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/images");
            exit;
        }

        $imagesModel = new ImagesModel();
        $lastImage = null;

        if (isset($_SESSION['current_image_id'])) {
            $imgId = $_SESSION['current_image_id'];
            $result = $imagesModel->getImageById($imgId, null); 
            if ($result) {
                $lastImage = (array) $result;
            }
        }
        elseif (isset($_SESSION['user_id']) && method_exists($imagesModel, 'getLastImageByUserId')) {
            $result = $imagesModel->getLastImageByUserId($_SESSION['user_id']);
            if ($result) {
                $lastImage = (array) $result;
            }
        }

        $this->render('crop_images_views', [
            't' => $this->translations,
            'image' => $lastImage,
            'css' => 'crop_images_views.css'
        ]);
    }

    /**
     * Handles the cropping logic by invoking an external java tool
     *
     * @return void
     */
    public function process() {
        $user_id = $_SESSION['user_id'] ?? null;

        if (!isset($_FILES['cropped_image']) || !isset($_POST['size'])) {
            echo json_encode(["status" => "error", "message" => "données manquantes"]);
            exit;
        }

        $uploadedFile = $_FILES['cropped_image'];

        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            $msg = "erreur inconnue";
            switch ($uploadedFile['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $msg = "l'image est trop lourde (limite serveur)";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $msg = "l'image est trop lourde (limite formulaire)";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $msg = "téléchargement partiel uniquement";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $msg = "aucun fichier reçu";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $msg = "dossier temporaire manquant sur le serveur";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $msg = "échec de l'écriture sur le disque";
                    break;
            }
            echo json_encode(["status" => "error", "message" => $msg]);
            exit;
        }

        $boardSize = intval($_POST['size']); 
        $_SESSION['boardSize'] = $boardSize;

        $projectRoot = dirname(__DIR__, 2);
        $tempDir = $projectRoot . '/JAVA/temp';
        
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }

        if (!is_writable($tempDir)) {
            $tempDir = sys_get_temp_dir();
        }

        $inputPath = $tempDir . '/lego_in_' . uniqid() . '.png';
        $outputPath = $tempDir . '/lego_out_' . uniqid() . '.png';

        $jarPath = realpath(__DIR__ . '/../../bin/legotools-1.0-SNAPSHOT.jar');

        try {
            if (!move_uploaded_file($uploadedFile['tmp_name'], $inputPath)) {
                throw new \Exception("impossible de sauvegarder l'image temporaire (vérifiez les droits ou la taille).");
            }

            $dimension = $boardSize . "x" . $boardSize;
            $strategy = "stepwise"; 
            
            if (!$jarPath || !file_exists($jarPath)) {
                throw new \Exception("fichier jar introuvable : " . $jarPath);
            }

            $command = "java -jar " . escapeshellarg($jarPath) . " resize " . escapeshellarg($inputPath) . " " . escapeshellarg($outputPath) . " " . escapeshellarg($dimension) . " " . escapeshellarg($strategy) . " 2>&1";

            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($outputPath)) {
                error_log("Erreur Java: " . implode("\n", $output));
                throw new \Exception("echec du traitement java.");
            }

            $processedData = file_get_contents($outputPath);
            if ($processedData === false) {
                throw new \Exception("impossible de lire l'image traitée.");
            }

            $model = new ImagesModel();
            
            $idToUpdate = $_POST['image_id'] ?? $_SESSION['current_image_id'] ?? null;

            if (isset($_POST['image_id'])) {
                $idToUpdate = $_POST['image_id'];
            } else {
                $lastResult = $model->getLastImageByUserId($user_id);
                if ($lastResult) {
                    $lastResult = (array)$lastResult; 
                    $idToUpdate = $lastResult['id_Image'];
                }
            }

            if (!$idToUpdate) {
                throw new \Exception("aucune image trouvée à modifier.");
            }

            $success = $model->updateCustomerImageBlob($idToUpdate, $user_id, $processedData);

            @unlink($inputPath);
            @unlink($outputPath);

            if ($success) {
                echo json_encode(["status" => "success", "file" => $idToUpdate]);
            } else {
                echo json_encode(["status" => "error", "message" => "erreur lors de la mise à jour en bdd."]);
            }

            $_SESSION['can_crop'] = false;

        } catch (\Exception $e) {
            if (file_exists($inputPath)) @unlink($inputPath);
            if (file_exists($outputPath)) @unlink($outputPath);
            
            echo json_encode(["status" => "error", "message" => "exception: " . $e->getMessage()]);
        }
        exit;
    }
}