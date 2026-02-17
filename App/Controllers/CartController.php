<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\TranslationModel;
use App\Models\ImagesModel;

/**
 * Class CartController
 * 
 ** Manages the shopping cart stored in the user's session
 ** Handles adding, removing, and clearing items before checkout
 * 
 * @package App\Controllers
 */
class CartController extends Controller {

    /** @var array Key/Value pair of translations. */
    private $translations;

    /**
     * Constructor.
     * initializes the controller and ensures the cart session structure exists
     */
    public function __construct() {
        $lang = $_SESSION['lang'] ?? 'fr';
        $translation_model = new TranslationModel();
        $this->translations = $translation_model->getTranslations($lang);
        
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }

    /**
     * Displays the cart contents and calculates totals
     *
     * @return void
     */
    public function index() {
        if (!isset($_SESSION['user_id'])) { header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/user/login"); exit; }

        $items = $_SESSION['cart'];
        $subTotal = 0;
        foreach ($items as $item) {
            $subTotal += $item['price'];
        }

        $delivery = \App\Models\MosaicModel::DELIVERY_FEE;
        $total = $subTotal + $delivery;

        $this->render('cart_views', [
            't' => $this->translations,
            'items' => $items,
            'subTotal' => $subTotal,
            'delivery' => $delivery,
            'total' => $total,
            'css' => 'cart_views.css'
        ]);
    }

    /**
     * Adds a specific mosaic configuration to the cart
     *
     * @return void
     */
    public function add() {
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/images");
            exit;
        }

        $imageId = $_POST['image_id'];
        $style = $_POST['choice']; 
        $size = $_SESSION['boardSize'] ?? 64; 

        $sessionKeyPrice = 'mosaic_prices_' . $imageId;
        $sessionKeyCount = 'mosaic_counts_' . $imageId;
        
        $price = $_SESSION[$sessionKeyPrice][$style] ?? 0;
        $pieces = $_SESSION[$sessionKeyCount][$style] ?? 0;

        $imagesModel = new ImagesModel();
        $image = $imagesModel->getImageById($imageId, $_SESSION['user_id']);
        
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

        $_SESSION['cart'][] = $newItem;

        $_SESSION['success_message'] = "La mosaïque a été ajoutée au panier !";

        session_write_close();
        $redirectUrl = ($_ENV['BASE_URL'] ?? '') . "/reviewImages?img=" . $imageId;
        header("Location: " . $redirectUrl);
        exit;
    }

    /**
     * Removes a single item from the cart based on its unique id
     *
     * @return void
     */
    public function remove() {
        if (isset($_POST['cart_id'])) {
            $idToDelete = $_POST['cart_id'];
            
            foreach ($_SESSION['cart'] as $key => $item) {
                if ($item['id_unique'] === $idToDelete) {
                    unset($_SESSION['cart'][$key]);
                    break; 
                }
            }
            $_SESSION['cart'] = array_values($_SESSION['cart']);
        }
        header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/cart");
        exit;
    }
    
    /**
     * Removes all items from the shopping cart
     *
     * @return void
     */
    public function clear() {
        $_SESSION['cart'] = [];
        header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/cart");
        exit;
    }
}