<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\TranslationModel;
use Dotenv\Dotenv;

/**
 * Class SettingController
 * 
 ** Manages user preferences including language selection,
 ** Visual theme toggling, and displaying the settings interface
 * 
 * @package App\Controllers
 */
class SettingController extends Controller {

    /** @var array Key/Value pair of translations. */
    private $translation_model;

    /**
     * Constructor.
     * Initializes the controller with translation capabilities
     */
    public function __construct() {
        parent::__construct();
        $dotenv = Dotenv::createImmutable(ROOT);
        $dotenv->load();
        $this->translation_model = new TranslationModel();
    }

    /**
     * Displays the settings page and handles theme switching logic
     *
     * @return void
     */
    public function index() {
        if (isset($_GET['action']) && $_GET['action'] === 'setTheme' && isset($_GET['theme'])) {
            $_SESSION['theme'] = $_GET['theme'];
            $baseUrl = $_ENV['BASE_URL'] ?? '';
            header("Location: $baseUrl/setting");
            exit;
        }

        $lang = $_SESSION['lang'] ?? 'fr';
        $translations = $this->translation_model->getTranslations($lang);

        $this->render('setting_views', [
            'css' => 'setting_views.css',
            'trans' => $translations,
            'success' => $_SESSION['success'] ?? null,
            'error'   => $_SESSION['error'] ?? null
        ]);
        
        unset($_SESSION['success'], $_SESSION['error']);
    }

    /**
     * Updates the session language and redirects user back to previous page
     *
     * @return void
     */
    public function setLanguage() {
        if (isset($_GET['lang'])) {
            $lang = $_GET['lang'];
            if (in_array($lang, ['fr', 'en'])) {
                $_SESSION['lang'] = $lang;
            }
        }

        if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
        } else {
            $baseUrl = $_ENV['BASE_URL'] ?? '';
            header('Location: ' . $baseUrl . '/index.php');
        }
        exit;
    }
}