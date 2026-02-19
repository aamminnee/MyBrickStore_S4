<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\TranslationModel;
use App\Models\ImagesModel;
use App\Models\MosaicModel;

/**
 * Class ReviewImagesController
 * 
 ** Manages the preview generation of lego mosaics
 ** Acts as the bridge between raw image uploads and the java processing engine
 * 
 * @package App\Controllers
 */
class ReviewImagesController extends Controller {

    /** @var array Key/Value pair of translations. */
    private $translations;

    /**
     * Constructor.
     * Initializes translation services based on user preference
     */
    public function __construct() {
        $lang = $_SESSION['lang'] ?? 'fr';
        $translation_model = new TranslationModel();
        $this->translations = $translation_model->getTranslations($lang);
    }

    /**
     * Generates and displays mosaic previews with price calculations
     *
     * @return void
     */
    public function index() {
        if (!isset($_GET['img'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/images");
            exit;
        }

        $imageId = $_GET['img'];
        $userId = $_SESSION['user_id'] ?? null;

        if ($userId === null && isset($_SESSION['current_image_id']) && $_SESSION['current_image_id'] != $imageId) {
             header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/images");
             exit;
        }

        $imagesModel = new ImagesModel();
        $image = $imagesModel->getImageById($imageId, $userId);
        
        if (!$image) {
            die("Image introuvable.");
        }
        $image = (array) $image;

        $previews = [];
        $counts = [];
        $prices = [];
        $error = null;
        
        $sessionKey = 'mosaics_' . $imageId;
        $mosaicModel = new MosaicModel();

        if (!isset($_SESSION[$sessionKey]) || empty($_SESSION[$sessionKey])) {
            try {
                $extension = ($image['file_type'] === 'image/png') ? 'png' : 'jpg';
                $results = $mosaicModel->generateTemporaryMosaics($image['id_Image'], $image['file'], $extension);
                
                if (empty($results)) {
                    $error = "La génération a échoué. Vérifiez les logs serveur et les permissions.";
                } else {
                    $_SESSION[$sessionKey] = $results;
                }
            } catch (\Exception $e) {
                $error = "Erreur : " . $e->getMessage();
                error_log($e->getMessage());
            }
        }

        if (isset($_SESSION[$sessionKey])) {
            foreach ($_SESSION[$sessionKey] as $type => $data) {
                if (isset($data['img'])) {
                    $previews[$type] = $data['img'];
                }
                
                if (isset($data['txt'])) {
                    $prices[$type] = $mosaicModel->calculatePriceFromContent($data['txt']);
                    $counts[$type] = $mosaicModel->countPiecesFromContent($data['txt']);
                } else {
                    $prices[$type] = 0;
                    $counts[$type] = isset($data['count']) ? $data['count'] : 0;
                }
            }
        }

        $_SESSION['mosaic_prices_' . $imageId] = $prices;
        $_SESSION['mosaic_counts_' . $imageId] = $counts;

        $this->render('review_images_views', [
            't' => $this->translations,
            'image' => $image,
            'previews' => $previews,
            'counts' => $counts,
            'prices' => $prices,
            'css' => 'review_images_views.css',
            'error_msg' => $error
        ]);
    }

    /**
     * Saves the selected mosaic configuration and redirects to payment
     *
     * @return void
     */
    public function save() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['choice'], $_POST['image_id'])) {
            $choice = $_POST['choice'];
            $imageId = $_POST['image_id'];
            $sessionKey = 'mosaics_' . $imageId;

            if (isset($_SESSION[$sessionKey][$choice]['txt'])) {
                $contentToSave = $_SESSION[$sessionKey][$choice]['txt'];
                
                $mosaicModel = new MosaicModel();
                $mosaicId = $mosaicModel->saveSelectedMosaic($imageId, $contentToSave, $choice);

                if ($mosaicId) {
                    $_SESSION['pending_payment_mosaic_id'] = $mosaicId;
                    
                    unset($_SESSION[$sessionKey]);

                    header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/payment");
                    exit;
                }
            }
        }
        header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/images");
    }

    /**
     * Handles the user choice (Add to Cart OR Buy Now).
     * Replaces the old save/add logic to handle both flows.
     *
     * @return void
     */
    public function handleChoice() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/images");
            exit;
        }

        $action = $_POST['action'] ?? 'cart';
        $imageId = $_POST['image_id'];
        $style = $_POST['choice']; 
        $size = $_SESSION['boardSize'] ?? 64; 

        $sessionKeyPrice = 'mosaic_prices_' . $imageId;
        $sessionKeyCount = 'mosaic_counts_' . $imageId;
        
        $price = $_SESSION[$sessionKeyPrice][$style] ?? 0;
        $pieces = $_SESSION[$sessionKeyCount][$style] ?? 0;

        $imagesModel = new ImagesModel();
        $userId = $_SESSION['user_id'] ?? null;
        $image = $imagesModel->getImageById($imageId, $userId);
        
        $newItem = [
            'id_unique' => uniqid(),
            'image_id' => $imageId,
            'style' => $style,
            'size' => $size,
            'price' => $price,
            'pieces_count' => $pieces,
            'image_data' => $image ? base64_encode($image->file) : '',
            'image_type' => $image ? $image->file_type : 'image/png'
        ];

        if ($action === 'buy_now') {
            
            $_SESSION['purchase_context'] = [
                'source' => 'direct',
                'items' => [$newItem]
            ];
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/payment");
            exit;

        } else {
           
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            $_SESSION['cart'][] = $newItem;
            
            // On enregistre le message de succès en session
            $_SESSION['success_message'] = "La mosaïque a été ajoutée au panier !";
            
            // REDIRECTION VERS LA PAGE REVIEW AU LIEU DU PANIER
            // On récupère l'ID de l'image pour revenir sur la bonne prévisualisation
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/reviewImages?img=" . $imageId);
            exit;
        }
    }
}