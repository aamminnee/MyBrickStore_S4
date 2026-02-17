<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\AdminModel;
use App\Models\UsersModel;
use App\Models\FinancialModel;
use App\Models\StockModel;

/**
 * Class AdminController
 * 
 ** manages the back-office (administration panel) of the application
 ** handles the main dashboard, global statistics, and supplier/factory order management
 ** access to this controller is strictly restricted to users with the 'admin' role
 * 
 * @package App\Controllers
 */
class AdminController extends Controller {

    /** @var AdminModel Handles admin-specific database queries. */
    private $admin_model;

    /** @var UsersModel Handles user management data. */
    private $user_model;

    /** @var FinancialModel Handles financial statistics (revenue, orders). */
    private $financial_model;

    /** @var StockModel Handles inventory data. */
    private $stock_model;

    /**
     * initializes models and enforces security protocols
     */
    public function __construct() {
        parent::__construct();
        
        $this->admin_model = new AdminModel();
        $this->user_model = new UsersModel();
        $this->financial_model = new FinancialModel();
        $this->stock_model = new StockModel();
        
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $baseUrl = $_ENV['BASE_URL'] ?? '';
            header("Location: $baseUrl/index.php");
            exit;
        }
    }

    /**
     * displays the dashboard with key performance indicators (kpis)
     *
     * @return void
     */
    public function index() {

        $stats = [
            'revenue'      => $this->financial_model->getTotalRevenue() ?? 0,
            'orders_count' => $this->financial_model->countOrders() ?? 0,
            'users_count'  => $this->user_model->countUsers() ?? 0,
            'low_stock'    => $this->stock_model->countLowStockItems(50) ?? 0
        ];

        $lastOrders = $this->financial_model->getLastOrders(5);
        if (!$lastOrders) {
            $lastOrders = [];
        }

        $this->render('admin_views', [
            'stats' => $stats,
            'lastOrders' => $lastOrders,
            'css' => 'admin_views.css' 
        ]);
    }

    /**
     * renders the detailed statistics page
     *
     * @return void
     */
    public function stats() {
        $this->render('admin_stats_views', [
            'css' => 'admin_stats_views.css'
        ]);
    }

    /**
     * retrieves and structures supplier orders for display
     *
     * @return void
     */
    public function supplier() {
        $rawOrders = $this->admin_model->getFactoryOrdersWithDetails();
        
        $groupedOrders = [];
        if ($rawOrders) {
            foreach ($rawOrders as $row) {
                $id = $row->id_FactoryOrder;
                if (!isset($groupedOrders[$id])) {
                    $groupedOrders[$id] = [
                        'info' => $row,
                        'items' => []
                    ];
                }
                $groupedOrders[$id]['items'][] = $row;
            }
        }

        $this->render('admin_supplier_views', [
            'orders' => $groupedOrders,
            'css' => 'admin_supplier_views.css'
        ]);
    }

    /**
     * executes factory operations via the external java application
     *
     * @return void
     */
    public function runFactory() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: ' . ($_ENV['BASE_URL'] ?? '') . '/index.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $allowedActions = ['refill', 'order', 'proactive', 'restock'];
            
            if (in_array($action, $allowedActions)) {
                
                $projectRoot = realpath(__DIR__ . '/../../'); 
                $legoToolsDir = $projectRoot . '/JAVA/legotools'; 
                $jarPath = $legoToolsDir . '/target/legotools-1.0-SNAPSHOT.jar';

                $javaBin = trim(shell_exec("which java"));
                if (empty($javaBin)) $javaBin = '/usr/bin/java';

                $cmd = "cd " . escapeshellarg($legoToolsDir) . " && " . $javaBin . " -Dfile.encoding=UTF-8 -jar " . escapeshellarg($jarPath) . " " . escapeshellarg($action) . " 2>&1";
                
                $output = [];
                $returnCode = 0;
                exec($cmd, $output, $returnCode);

                $resultText = implode("\n", $output);
                $_SESSION['factory_output'] = $resultText;

                if (preg_match('/Nouveau solde : (\d+)/', $resultText, $matches)) {
                    $_SESSION['last_factory_balance'] = $matches[1];
                } elseif (preg_match('/Solde : (\d+)/', $resultText, $matches)) {
                    $_SESSION['last_factory_balance'] = $matches[1];
                }
                header('Location: ' . ($_ENV['BASE_URL'] ?? '') . '/admin/supplier');
                exit;
            }
            header('Location: ' . ($_ENV['BASE_URL'] ?? '') . '/admin/index');
            exit;
        }
    }
}