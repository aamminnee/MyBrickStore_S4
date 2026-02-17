<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\StockModel;

/**
 * Class StockController
 *
 ** Manages the inventory of Lego bricks.
 ** Handles listing, filtering, and stock adjustments (Manual removal vs Factory purchase).
 ** Access is restricted to administrators.
 *
 * @package App\Controllers
 */
class StockController extends Controller {

    /** @var StockModel Instance of the stock management model. */
    private $stockModel;

    /**
     * Constructor.
     * Initializes the controller and enforces administrator access.
     */
    public function __construct() {
        parent::__construct();
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/index.php");
            exit;
        }
        $this->stockModel = new StockModel();
    }

    /**
     * Displays the paginated stock list with optional filters.
     * Retrieves flash messages (debug/error/success) from the session.
     *
     * @return void
     */
    public function index() {
        $errorMessage = $_SESSION['stock_error'] ?? null;
        unset($_SESSION['stock_error']);

        $successMessage = $_SESSION['stock_message'] ?? null;
        unset($_SESSION['stock_message']);

        $page = (int)($_GET['page'] ?? 1);
        $limit = 50; 
        
        $filterShape = $_GET['filter_shape'] ?? null;
        $filterColor = $_GET['filter_color'] ?? null;
        $filterStatus = $_GET['filter_status'] ?? 'all'; 
        
        $stocks = $this->stockModel->getPaginatedStock($limit, $page, $filterShape, $filterColor, $filterStatus);
        $totalItems = $this->stockModel->countStockItems($filterShape, $filterColor, $filterStatus);
        $totalPages = ceil($totalItems / $limit);

        $allItems = $this->stockModel->getAllItemsForSearch();
        $shapes = $this->stockModel->getAllShapes();
        $colors = $this->stockModel->getAllColors();
        
        $this->render('stock_views', [
            'stocks' => $stocks,
            'allItems' => $allItems,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'shapesList' => $shapes,
            'colorsList' => $colors,
            'currentStatus' => $filterStatus,
            'error' => $errorMessage,
            'success' => $successMessage,
            'css' => 'stock_views.css'
        ]);
    }

    /**
     * Handles stock adjustment form submissions.
     * - Negative quantity: Direct database update (Loss/Breakage).
     * - Positive quantity: Triggers a Java command to "buy" stock from the factory.
     *
     * @return void
     */
    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $itemId = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
            $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

            if ($itemId > 0 && $quantity != 0) {
                if ($quantity < 0) {
                    $this->stockModel->updateStock($itemId, $quantity);
                    $_SESSION['stock_message'] = "Stock mis à jour (suppression manuelle).";
                } 
                else {
                    $ref = $this->stockModel->getItemReferenceById($itemId);
                    if ($ref) {
                        $this->runJavaBuy($ref, $quantity);
                    } else {
                        $_SESSION['stock_error'] = "Erreur : Référence introuvable pour l'item ID $itemId";
                    }
                }
            }
        }
        header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/stock");
        exit;
    }

    /**
     * Executes the external Java application to process a purchase ('buy' command).
     * Ensures the command runs from the project root to properly load environment variables.
     *
     * @param string $ref The brick reference (e.g., "3001/21").
     * @param int $qty The quantity to purchase.
     * @return void
     */
    /**
     * Executes the external Java application to process a purchase.
     * Nettoyage agressif pour éviter les problèmes d'encodage.
     */
    private function runJavaBuy($ref, $qty) {
        $projectRoot = dirname(__DIR__, 2);
    
        $possiblePaths = [
            $projectRoot . '/bin/legotools-1.0-SNAPSHOT.jar',
            $projectRoot . '/legotools/target/legotools-1.0-SNAPSHOT.jar',
            $projectRoot . '/target/legotools-1.0-SNAPSHOT.jar'
        ];

        $jarPath = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $jarPath = realpath($path);
                break;
            }
        }

        if (!$jarPath) {
            $_SESSION['stock_error'] = "Erreur critique : Fichier .jar introuvable.";
            return;
        }

        $javaBin = 'java';
        if (file_exists('/usr/bin/java')) $javaBin = '/usr/bin/java';

        $command = sprintf(
            'cd %s && LC_ALL=C %s -jar %s buy %s %s 2>&1',
            escapeshellarg($projectRoot), 
            $javaBin,
            escapeshellarg($jarPath),
            escapeshellarg($ref),
            escapeshellarg($qty)
        );

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            $_SESSION['stock_error'] = "Erreur Achat : " . implode(" ", $output);
        } else {
            $cleanLines = [];
            
            foreach ($output as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '//') === 0) {
                    continue;
                }
                
                $accents = ['é', 'è', 'ê', 'à', 'â', 'ï', 'î', 'ô', 'ö', 'ç', 'ù'];
                $sansAccents = ['e', 'e', 'e', 'a', 'a', 'i', 'i', 'o', 'o', 'c', 'u'];
                $line = str_replace($accents, $sansAccents, $line);

                $line = preg_replace('/[^a-zA-Z0-9\s\.\-\(\)\:]/', '', $line);
                
                $cleanLines[] = $line;
            }

            $finalMessage = implode(" - ", $cleanLines);

            if (empty($finalMessage)) {
                $finalMessage = "Operation effectuee (Voir logs serveur pour details).";
            }

            $_SESSION['stock_message'] = "Succes : " . $finalMessage;
        }
    }
}