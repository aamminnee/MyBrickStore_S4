<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\TranslationModel;
use Dotenv\Dotenv;

/**
 * Class SettingController
 * * Manages user preferences including language selection,
 * * Visual theme toggling, and displaying the settings interface
 * 
 * @package App\Controllers
 */
class SettingController extends Controller {

    /** @var array Key/Value pair of translations. */
    private $translation_model;
    
    /** @var \App\Models\UsersModel Instance of the users model. */
    private $usersModel;

    /**
     * Constructor.
     * Initializes the controller with translation capabilities
     */
    public function __construct() {
        parent::__construct();
        $dotenv = Dotenv::createImmutable(ROOT);
        $dotenv->load();
        $this->translation_model = new TranslationModel();
        
        // initialize the model using the correct property name (lowercase u)
        $this->usersModel = new \App\Models\UsersModel();
    }

    /**
     * Displays the settings page and handles theme switching logic
     *
     * @return void
     */
    public function index() {
        // check for theme change request
        if (isset($_GET['action']) && $_GET['action'] === 'setTheme' && isset($_GET['theme'])) {
            $_SESSION['theme'] = $_GET['theme'];
            $baseUrl = $_ENV['BASE_URL'] ?? '';
            header("Location: $baseUrl/setting");
            exit;
        }

        $lang = $_SESSION['lang'] ?? 'fr';
        $translations = $this->translation_model->getTranslations($lang);

        $userId = $_SESSION['user_id'] ?? null;
        $totpSecret = null;
        if ($userId) {
            $user = $this->usersModel->getUserById($userId);
            $totpSecret = is_object($user) ? ($user->totp_secret ?? null) : ($user['totp_secret'] ?? null);
        }

        $this->render('setting_views', [
            'css' => 'setting_views.css',
            'trans' => $translations,
            'totpSecret' => $totpSecret,
            'success' => $_SESSION['success_message'] ?? null,
            'error'   => $_SESSION['error'] ?? null
        ]);
        
        unset($_SESSION['success_message'], $_SESSION['error']);
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

    /**
     * Update the standard 2FA settings for the user (email or none)
     *
     * @return void
     */
    public function update2fa() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . ($_ENV['BASE_URL'] ?? '') . '/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['2fa_type'])) {
            $userId = $_SESSION['user_id'];
            $type = $_POST['2fa_type'];

            $allowedTypes = ['email', 'none'];
            
            if (in_array($type, $allowedTypes)) {
                $this->usersModel->update2FAType($userId, $type);
                
                $_SESSION['mode'] = ($type === 'none') ? null : $type;
                
                unset($_SESSION['setup_totp']);
                
                $_SESSION['success_message'] = $this->trans['js_2fa_update_success'] ?? 'Vos préférences 2FA ont été mises à jour.';
            }

            header('Location: ' . ($_ENV['BASE_URL'] ?? '') . '/setting');
            exit;
        }
    }

    /**
     * Initiates the setup phase for TOTP authentication app
     *
     * @return void
     */
    public function setup2faApp() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . ($_ENV['BASE_URL'] ?? '') . '/login');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $user = $this->usersModel->getUserById($userId);
        $existingSecret = is_object($user) ? ($user->totp_secret ?? null) : ($user['totp_secret'] ?? null);
        
        if (empty($existingSecret)) {
            $secret = '';
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
            for($i = 0; $i < 16; $i++) {
                $secret .= $chars[random_int(0, 31)];
            }
            $this->usersModel->updateTotpSecret($userId, $secret);
        }

        $_SESSION['setup_totp'] = true;
        
        header('Location: ' . ($_ENV['BASE_URL'] ?? '') . '/setting');
        exit;
    }

    /**
     * Confirms and activates the TOTP app by verifying the first code
     *
     * @return void
     */
    public function confirm2faApp() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . ($_ENV['BASE_URL'] ?? '') . '/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['totp_code'])) {
            $userId = $_SESSION['user_id'];
            $code = $_POST['totp_code'];
            
            $user = $this->usersModel->getUserById($userId);
            $secret = is_object($user) ? ($user->totp_secret ?? null) : ($user['totp_secret'] ?? null);

            if ($this->usersModel->verifyTOTP($secret, $code)) {
                $this->usersModel->update2FAType($userId, 'app');
                $_SESSION['mode'] = 'app';
                unset($_SESSION['setup_totp']);
                $_SESSION['success_message'] = $this->trans['js_2fa_app_success'] ?? 'Authentification par application activée avec succès !';
            } else {
                $_SESSION['error'] = $this->trans['js_2fa_code_error'] ?? 'Le code saisi est incorrect. Veuillez réessayer.';
                $_SESSION['setup_totp'] = true; 
            }
        }
        
        header('Location: ' . ($_ENV['BASE_URL'] ?? '') . '/setting');
        exit;
    }

    /**
     * Cancels the TOTP setup process
     *
     * @return void
     */
    public function cancel2faApp() {
        unset($_SESSION['setup_totp']);
        header('Location: ' . ($_ENV['BASE_URL'] ?? '') . '/setting');
        exit;
    }
}