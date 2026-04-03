<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\TranslationModel;
use App\Models\ImagesModel;
use App\Models\MosaicModel;

/**
 * class reviewimagescontroller
 * * manages the preview generation of lego mosaics.
 * acts as the bridge between raw image uploads and the java processing engine.
 * applies daily image promotional discounts if eligible.
 * * @package App\Controllers
 */
class ReviewImagesController extends Controller {

    /** * @var array key/value pair of translations. 
     */
    private $translations;

    /**
     * constructor.
     * initializes translation services based on user preference.
     */
    public function __construct() {
        $lang = $_SESSION['lang'] ?? 'fr';
        $translation_model = new TranslationModel();
        $this->translations = $translation_model->getTranslations($lang);
    }

    /**
     * generates and displays mosaic previews with price calculations.
     *
     * @return void
     */
    public function index() {
        // redirect if no image is specified
        if (!isset($_GET['img'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/images");
            exit;
        }

        $imageId = $_GET['img'];
        $userId = $_SESSION['user_id'] ?? null;

        // security check for guests
        if ($userId === null && isset($_SESSION['current_image_id']) && $_SESSION['current_image_id'] != $imageId) {
             header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/images");
             exit;
        }

        $imagesModel = new ImagesModel();
        $image = $imagesModel->getImageById($imageId, $userId);
        
        // stop if image does not exist
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

        // generate mosaics if not already cached in session
        if (!isset($_SESSION[$sessionKey]) || empty($_SESSION[$sessionKey])) {
            try {
                // determine image extension
                $extension = ($image['file_type'] === 'image/png') ? 'png' : 'jpg';
                // call java engine to generate previews
                $results = $mosaicModel->generateTemporaryMosaics($image['id_Image'], $image['file'], $extension);
                
                if (empty($results)) {
                    $error = "La génération a échoué. Vérifiez les logs serveur et les permissions.";
                } else {
                    // cache results in session
                    $_SESSION[$sessionKey] = $results;
                }
            } catch (\Exception $e) {
                // catch generation errors
                $error = "Erreur : " . $e->getMessage();
                error_log($e->getMessage());
            }
        }

        // extract prices and piece counts from session
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

        // check if this image was uploaded as the daily image
        $isDailyDiscount = isset($_SESSION['daily_discount_' . $imageId]);
        
        if ($isDailyDiscount && !empty($prices)) {
            // apply a 15% discount for the image of the day
            foreach ($prices as $type => $price) {
                if ($price > 0) {
                    $prices[$type] = $price * 0.85;
                }
            }
        }

        // save the final prices and counts in session for the cart
        $_SESSION['mosaic_prices_' . $imageId] = $prices;
        $_SESSION['mosaic_counts_' . $imageId] = $counts;

        // render the view
        $this->render('review_images_views', [
            't' => $this->translations,
            'image' => $image,
            'previews' => $previews,
            'counts' => $counts,
            'prices' => $prices,
            'css' => 'review_images_views.css',
            'error_msg' => $error,
            'isDailyDiscount' => $isDailyDiscount
        ]);
    }

    /**
     * saves the selected mosaic configuration and redirects to payment.
     *
     * @return void
     */
    public function save() {
        // process only post requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['choice'], $_POST['image_id'])) {
            $choice = $_POST['choice'];
            $imageId = $_POST['image_id'];
            $sessionKey = 'mosaics_' . $imageId;

            // check if content to save exists
            if (isset($_SESSION[$sessionKey][$choice]['txt'])) {
                $contentToSave = $_SESSION[$sessionKey][$choice]['txt'];
                
                $mosaicModel = new MosaicModel();
                // save the layout instructions
                $mosaicId = $mosaicModel->saveSelectedMosaic($imageId, $contentToSave, $choice);

                // redirect to payment if successful
                if ($mosaicId) {
                    $_SESSION['pending_payment_mosaic_id'] = $mosaicId;
                    
                    unset($_SESSION[$sessionKey]);

                    header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/payment");
                    exit;
                }
            }
        }
        // fallback redirect
        header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/images");
    }

    /**
     * handles the user choice (add to cart or buy now).
     * replaces the old save/add logic to handle both flows.
     *
     * @return void
     */
    public function handleChoice() {
        // redirect if not a post request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/images");
            exit;
        }

        // get basic inputs
        $action = $_POST['action'] ?? 'cart';
        $imageId = $_POST['image_id'];
        $style = $_POST['choice']; 
        $size = $_SESSION['boardSize'] ?? 64; 

        // retrieve prices and piece counts from session
        $sessionKeyPrice = 'mosaic_prices_' . $imageId;
        $sessionKeyCount = 'mosaic_counts_' . $imageId;
        
        $price = $_SESSION[$sessionKeyPrice][$style] ?? 0;
        $pieces = $_SESSION[$sessionKeyCount][$style] ?? 0;

        $imagesModel = new ImagesModel();
        $userId = $_SESSION['user_id'] ?? null;
        $image = $imagesModel->getImageById($imageId, $userId);
        
        // get generated preview image
        $sessionKeyMosaics = 'mosaics_' . $imageId;
        $previewDataUrl = $_SESSION[$sessionKeyMosaics][$style]['img'] ?? '';
        
        $imgData = '';
        $imgType = 'image/png';
        
        // parse data url to extract base64 content
        if ($previewDataUrl && preg_match('/^data:(image\/[a-z]+);base64,(.+)$/', $previewDataUrl, $matches)) {
            $imgType = $matches[1];
            $imgData = $matches[2];
        } else {
            $imgData = $image ? base64_encode($image->file) : '';
            $imgType = $image ? $image->file_type : 'image/png';
        }
        
        // build new cart item array
        $newItem = [
            'id_unique' => uniqid(),
            'image_id' => $imageId,
            'style' => $style,
            'size' => $size,
            'price' => $price,
            'pieces_count' => $pieces,
            'image_data' => $imgData,
            'image_type' => $imgType
        ];

        // direct purchase flow
        if ($action === 'buy_now') {
            $_SESSION['purchase_context'] = [
                'source' => 'direct',
                'items' => [$newItem]
            ];
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/payment");
            exit;

        } else {
            // standard add to cart flow
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            $_SESSION['cart'][] = $newItem;
            
            // handle ajax requests
            if (isset($_POST['is_ajax']) && $_POST['is_ajax'] === 'true') {
                if (ob_get_length()) { ob_clean(); }
                
                $cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
                
                header('Content-Type: application/json');
                
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Ajouté au panier',
                    'cart_count' => $cartCount
                ]);
                exit;
            }
        }
    }
}