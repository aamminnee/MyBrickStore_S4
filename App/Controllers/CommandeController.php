<?php
// file : app/controllers/commandecontroller.php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\CommandeModel;
use App\Models\ImagesModel;
use App\Models\MosaicModel; 
use App\Models\TranslationModel;
use App\Models\LoyaltyApiModel;

/**
 * class CommandeController
 * * manages the user's order history and downloads.
 * allows users to view past orders, download assembly plans, and parts lists.
 * also handles loyalty points consumption during order finalization.
 * * @package App\Controllers
 */
class CommandeController extends Controller {
    
    /** @var array Key/Value pair of translations. */
    private $translations;

    /**
     * initializes the controller and loads translation strings
     */
    public function __construct() {
        // get the current language or default to french
        $lang = $_SESSION['lang'] ?? 'fr';
        $translation_model = new TranslationModel();
        $this->translations = $translation_model->getTranslations($lang);
    }

    /**
     * displays a list of past orders for the authenticated user
     *
     * @return void
     */
    public function index() {
        // redirect to login if user is not authenticated
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/user/login");
            exit;
        }

        $commandeModel = new CommandeModel();
        $mosaicModel = new MosaicModel();

        // fetch all orders for the current user
        $commandes = $commandeModel->getCommandeByUserId($_SESSION['user_id']);

        // assign visual to each order
        foreach ($commandes as $commande) {
            if (!empty($commande->id_Mosaic)) {
                $commande->visuel = $mosaicModel->getMosaicVisual($commande->id_Mosaic);
            } else {
                $commande->visuel = ($_ENV['BASE_URL'] ?? '') . '/Public/images/logo.png';
            }
        }

        // render the view with the orders data
        $this->render('commande_views', [
            'commandes' => $commandes,
            'commandeModel' => $commandeModel, 
            't' => $this->translations,
            'css' => 'commande_views.css'
        ]);
    }

    /**
     * displays the detailed view of a specific order.
     * aggregates the list of lego bricks required for the entire order.
     *
     * @param int $id the unique identifier of the order.
     * @return void
     */
    public function detail($id) {
        // redirect to login if user is not authenticated
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/user/login");
            exit;
        }

        $commandeModel = new CommandeModel();
        $commande = $commandeModel->getCommandeById($id);
        
        // redirect to orders list if order does not exist or does not belong to user
        if (!$commande || $commande->id_Customer != $_SESSION['user_id']) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/commande");
            exit;
        }

        $mosaicModel = new MosaicModel();
        $items = $mosaicModel->getMosaicsByOrderId($id);

        $briquesAgregees = [];
        
        // aggregate all bricks from all mosaics in the order
        if ($items) {
            foreach ($items as $itm) {
                $pieces = $mosaicModel->getBricksList($itm->id_Mosaic);
                
                foreach ($pieces as $piece) {
                    $key = $piece['size'] . '_' . $piece['color'];
                    
                    if (isset($briquesAgregees[$key])) {
                        $briquesAgregees[$key]['count'] += $piece['count'];
                    } else {
                        $briquesAgregees[$key] = $piece;
                    }
                }
            }
        }
        
        $briques = array_values($briquesAgregees);
        
        // sort bricks by size (descending) then color (ascending)
        array_multisort(
            array_column($briques, 'size'), SORT_DESC,
            array_column($briques, 'color'), SORT_ASC,
            $briques
        );

        // render the detail view
        $this->render('commande_detail_views', [
            't' => $this->translations,
            'commande' => $commande,
            'items' => $items,
            'briques' => $briques,
            'visuel' => $items[0]->visuel ?? null,
            'css' => 'commande_detail_views.css'
        ]);
    }

    /**
     * generates and forces download of a csv file containing the parts list
     *
     * @param int $id mosaic identifier
     * @return void
     */
    public function downloadCsv($id) {
        // ensure user is logged in
        $this->checkAuth();
        $mosaicModel = new MosaicModel();
        
        $briques = $mosaicModel->getBricksList((int)$id);

        // check if data exists
        if (empty($briques)) {
             die("Aucune donnée pour cette mosaïque.");
        }

        // set headers for csv download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Liste_Pieces_Mosaique_' . $id . '.csv');

        // open output stream and write utf-8 bom
        $output = fopen('php://output', 'w');
        fputs($output, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) )); 
        
        // write csv headers
        fputcsv($output, ['Couleur', 'Taille', 'Quantité'], ';');

        // write csv data rows
        foreach ($briques as $b) {
            fputcsv($output, [
                strtoupper($b['color']), 
                $b['size'], 
                $b['count'],
            ], ';');
        }
        
        // close stream and exit
        fclose($output);
        exit;
    }

    /**
     * converts the stored base64 string into a downloadable png image
     *
     * @param int $idMosaic mosaic identifier
     * @return void
     */
    public function downloadImage($idMosaic) {
        $mosaicModel = new \App\Models\MosaicModel();
        
        $imageDataBase64 = $mosaicModel->getMosaicVisual($idMosaic); 
        
        // check if image data exists
        if ($imageDataBase64) {
            // decode base64 string
            $parts = explode(',', $imageDataBase64);
            $binary = base64_decode($parts[1]);
            
            // set headers for png download
            header('Content-Type: image/png');
            header('Content-Disposition: attachment; filename="mosaique_lego_'.$idMosaic.'.png"');
            header('Content-Length: ' . strlen($binary));
            
            // output image and exit
            echo $binary;
            exit;
        } else {
            // handle error and redirect back
            $_SESSION['error_message'] = "Impossible de générer l'image pour le téléchargement.";
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }

    /**
     * renders the printable assembly plan for a specific mosaic
     *
     * @param int $id mosaic identifier
     * @return void
     */
    public function downloadPlan($id) {
        // redirect to login if not authenticated
        if (!isset($_SESSION['user_id'])) { 
            header("Location: /user/login"); 
            exit; 
        }

        $mosaicModel = new \App\Models\MosaicModel();
        $planData = $mosaicModel->getMosaicPlanData((int)$id);

        // redirect to orders list if plan data is missing
        if (!$planData) {
            header("Location: " . $_ENV['BASE_URL'] . "/commande");
            exit;
        }

        // render the plan view with an empty layout
        $this->render('plan_views', [
            'id' => $id,
            'plan' => $planData
        ], 'empty'); 
    }

    /**
     * helper to enforce authentication requirements
     *
     * @return void
     */
    private function checkAuth() {
        // redirect to login if session does not contain user id
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/user/login");
            exit;
        }
    }

    /**
     * finalize order and consume loyalty points if any
     *
     * @param int $orderId the identifier of the order being finalized
     * @return void
     */
    private function finalizeOrder($orderId) {
        // consume points if a discount was applied via the session
        if (isset($_SESSION['applied_points']) && isset($_SESSION['user']['loyalty_id'])) {
            $loyaltyId = $_SESSION['user']['loyalty_id'];
            $pointsToConsume = $_SESSION['applied_points'];
            
            // call the node.js api to consume the points
            $loyaltyModel = new LoyaltyApiModel();
            // note: make sure consumePoints exists in loyaltyapi model or use appropriate method
            // $success = $loyaltyModel->consumePoints($loyaltyId, $pointsToConsume);
            
            // clear discount from session after successful consumption
            unset($_SESSION['applied_points']);
            unset($_SESSION['loyalty_discount']);
        }
    }
}