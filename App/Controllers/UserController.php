<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\UsersModel;
use App\Models\TokensModel;
use App\Models\TranslationModel;
use App\Models\ImagesModel;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

/**
 * Class UserController
 * 
 ** Manages user authentication lifecycle including login, registration,
 ** Password recovery and security settings like 2fa
 * 
 * @package App\Controllers
 */
class UserController extends Controller {

    /** @var UsersModel Handles user database operations. */
    private $user_model;

    /** @var TokensModel Handles token generation and verification. */
    private $token_model;

    /** @var PHPMailer Instance of the mailer service. */
    private $mail;

    /** @var array Key/Value pair of translations. */
    private $translations;

    /**
     * Constructor.
     * Initializes models and mailer configuration
     */
    public function __construct() {
        parent::__construct();
        
        $this->user_model = new UsersModel();
        $this->token_model = new TokensModel();
        $this->mail = new PHPMailer(true);
        
        $dotenv = Dotenv::createImmutable(ROOT);
        $dotenv->load();

        $this->translations = $this->trans;
    }

    /**
     * Retrieves a translation for a given key
     *
     * @param string $key the translation key
     * @param string $default default text if key is missing
     * @return string translated text
     */
    private function t($key, $default = '') {
        return $this->translations[$key] ?? $default;
    }

    /**
     * Handles user login process with captcha and 2fa support
     *
     * @return void
     */
    public function login() {
        $baseUrl = $_ENV['BASE_URL'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email']) && !empty($_POST['password'])) {
            $userCaptcha = trim($_POST['captcha'] ?? '');
            $token = trim($_POST['captcha_token'] ?? '');
            
            if (empty($token) || empty($userCaptcha) || strcasecmp($userCaptcha, $token) !== 0) {
                $message = $this->t('captcha_invalid', "Incorrect captcha. Please try again.");
                $this->render('login_views', ['message' => $message, 'css' => 'login_views.css']);
                return;
            }

            $email = trim($_POST['email']);
            $password = $_POST['password'];
            
            $user = $this->user_model->getUserByEmail($email);

            $userMdp = is_object($user) ? $user->mdp : ($user['mdp'] ?? null);
            $userId = is_object($user) ? $user->id_user : ($user['id_user'] ?? null);
            $userEtat = is_object($user) ? $user->etat : ($user['etat'] ?? null);
            $userMode = is_object($user) ? $user->mode : ($user['mode'] ?? null);
            $userEmail = is_object($user) ? $user->email : ($user['email'] ?? null);
            $userRole = is_object($user) ? ($user->role ?? 'user') : ($user['role'] ?? 'user');

            if ($user && password_verify($password, $userMdp)) {
                
                if ($userMode === '2FA' || $userMode === 'EMAIL') {
                    $_SESSION['temp_2fa_user_id'] = $userId;
                    $_SESSION['temp_2fa_email']   = $userEmail;
                    
                    $token = $this->token_model->generateToken($userId, "2FA");
                    $this->sendVerificationEmail($userEmail, $token);
                    
                    header("Location: $baseUrl/user/verify");
                    exit;
                }

                $_SESSION['user_id']  = $userId;
                $_SESSION['email']    = $userEmail;
                $_SESSION['status']   = $userEtat;
                $_SESSION['mode']     = $userMode;
                $_SESSION['role']     = $userRole;
                
                if ($userRole === 'admin') {
                    header("Location: $baseUrl/admin");
                } else {
                    if (!empty($_SESSION['cart'])) {
                        header("Location: $baseUrl/cart");
                    } else {
                        header("Location: $baseUrl/index.php");
                    }
                }
                exit;
            } else {
                $message = $this->t('login_error', "Email ou mot de passe incorrect.");
                $this->render('login_views', ['message' => $message, 'css' => 'login_views.css']);
            }
        } else {
            $this->render('login_views', ['css' => 'login_views.css']);
        }
    }

    /**
     * Redirects authorized users to the admin dashboard
     *
     * @return void
     */
    public function admin() {
        $baseUrl = $_ENV['BASE_URL'] ?? '';
        
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header("Location: $baseUrl/index.php");
            exit;
        }

        header("Location: $baseUrl/admin");
        exit;
    }

    /**
     * Handles new user registration and validation email sending
     *
     * @return void
     */
    public function register() {
        $baseUrl = $_ENV['BASE_URL'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if ($password !== $confirm_password) {
                $this->render('register_views', ['error' => "Les mots de passe ne correspondent pas.", 'css' => 'register_views.css']);
                return;
            }

            $passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
            if (!preg_match($passwordPattern, $password)) {
                $msg = $this->t('password_invalid', "Le mot de passe doit contenir 8 caractères min, avec majuscule, minuscule, chiffre et spécial.");
                $this->render('register_views', ['error' => $msg, 'css' => 'register_views.css']);
                return;
            }

            $result = $this->user_model->addUser($email, $password);
            
            if ($result === true) {
                $user = $this->user_model->getUserByEmail($email);
                $userId = is_object($user) ? $user->id_user : ($user['id_user'] ?? null);
                
                if ($userId) {
                    $token = $this->token_model->generateToken($userId, "validation");
                    $this->sendVerificationEmail($email, $token);
                    header("Location: $baseUrl/user/verify");
                    exit;
                }
            } elseif ($result === "duplicate") {
                $msg = $this->t('username_exists', "Cet email est déjà utilisé.");
                $this->render('register_views', ['error' => $msg, 'css' => 'register_views.css']);
                exit;
            } else {
                 $msg = $this->t('register_error', "L'inscription a échoué, veuillez réessayer.");
                 $this->render('register_views', ['error' => $msg, 'css' => 'register_views.css']);
                exit;
            }
        } else {
            $error = $_SESSION['register_message'] ?? null;
            unset($_SESSION['register_message']);

            $this->render('register_views', ['error' => $error, 'css' => 'register_views.css']);
        }
    }

    /**
     * Processes the final password update after validation
     *
     * @return void
     */
    public function resetPasswordForm() {
        if (isset($_POST['reset_password'])) {
            $password = $_POST['password'];
            $password_confirm = $_POST['password_confirm'];

            if ($password !== $password_confirm) {
                $error = "Les mots de passe ne correspondent pas.";
                $this->render('reset_password_views', [
                    'error' => $error,
                    'css' => 'reset_password_views.css'
                ]);
                return;
            }

            $validation = $this->user_model->validateNewPassword($_SESSION['user_id'], $password);

            if ($validation !== true) {
                $this->render('reset_password_views', [
                    'error' => $validation,
                    'css' => 'reset_password_views.css'
                ]);
                return;
            }

            $this->user_model->updatePassword($_SESSION['user_id'], $password);
            
            $_SESSION['success_message'] = "Mot de passe modifié avec succès.";
            
            header('Location: ' . $_ENV['BASE_URL'] . '/setting');
            exit;

        } else {
            $this->render('reset_password_views', [
                'css' => 'reset_password_views.css'
            ]);
        }
    }

    /**
     * Initiates the password reset flow by sending an email link
     *
     * @return void
     */
    public function resetPassword() {
        $baseUrl = $_ENV['BASE_URL'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
            $email = trim($_POST['email']);
            
            $user = $this->user_model->getUserByEmail($email);

            if ($user) {
                $userId = is_object($user) ? $user->id_user : $user['id_user'];
                $userEmail = is_object($user) ? $user->email : $user['email'];

                $_SESSION['email'] = $userEmail; 

                $token = $this->token_model->generateToken($userId, "reinitialisation");
                $this->sendVerificationEmail($userEmail, $token);

                header("Location: $baseUrl/user/verify");
                exit;
            } else {
                $message = "Aucun compte associé à cet email.";
                $this->render('forgot_password_views', [
                    'message' => $message,
                    'css' => 'login_views.css'
                ]);
            }
        }
        elseif (isset($_SESSION['user_id'])) {
            $token = $this->token_model->generateToken($_SESSION['user_id'], "reinitialisation");
            $this->sendVerificationEmail($_SESSION['email'], $token);
            header("Location: $baseUrl/user/verify");
            exit;
        }
        else {
            $this->render('forgot_password_views', [
                'css' => 'login_views.css'
            ]);
        }
    }

    /**
     * Verifies tokens for account activation, password reset, or 2fa
     *
     * @return void
     */
    public function verify() {
        $baseUrl = $_ENV['BASE_URL'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
            $token = $_POST['token'];
            
            $token_data = $this->token_model->verifyToken($token);

            if ($token_data) {
                $this->token_model->consumeToken($token);
                $this->token_model->deleteToken();
                
                $userId = is_object($token_data) ? $token_data->id_Customer : $token_data['id_Customer'];
                $types = is_object($token_data) ? $token_data->types : $token_data['types'];

                if ($types === 'validation') {
                    $this->user_model->activateUser($userId);
                    if(isset($_SESSION['user_id'])) {
                        $_SESSION['status'] = 'valide';
                        header("Location: $baseUrl/index.php");
                        exit;
                    }
                    header("Location: $baseUrl/user/login");
                    exit;

                } elseif ($types === 'reinitialisation') {
                    $_SESSION['user_id'] = $userId; 
                    header("Location: $baseUrl/user/resetPasswordForm"); 
                    exit;

                } elseif ($types === '2FA') {
                    $userFull = $this->user_model->getUserById($userId); 
                    
                    if ($userFull) {
                        $idUser = is_object($userFull) ? $userFull->id_user : $userFull['id_user'];
                        $username = is_object($userFull) ? $userFull->username : $userFull['username'];
                        $email = is_object($userFull) ? $userFull->email : $userFull['email'];
                        $etat = is_object($userFull) ? $userFull->etat : $userFull['etat'];
                        $mode = is_object($userFull) ? $userFull->mode : $userFull['mode'];
                        $role = is_object($userFull) ? ($userFull->role ?? 'user') : ($userFull['role'] ?? 'user');
                        
                        $_SESSION['user_id']  = $idUser;
                        $_SESSION['username'] = $username;
                        $_SESSION['email']    = $email;
                        $_SESSION['status']   = $etat;
                        $_SESSION['mode']     = $mode;
                        $_SESSION['role']     = $role;
                        
                        unset($_SESSION['temp_2fa_user_id']);
                        unset($_SESSION['temp_2fa_email']);

                        $this->mergeGuestData($idUser);
                        
                        if ($role === 'admin') {
                            header("Location: $baseUrl/user/admin");
                        } else {
                            if (!empty($_SESSION['cart'])) {
                                header("Location: $baseUrl/cart");
                            } else {
                                header("Location: $baseUrl/index.php");
                            }
                        }
                        exit;
                    } else {
                        $message = "Erreur critique : utilisateur introuvable.";
                        $this->render('login_views', [
                            'message' => $message,
                            'css' => 'login_views.css'
                        ]);
                        exit;
                    }
                }
            } else {
                $message = $this->t('token_invalid', "Code invalide ou expiré.");
                $this->render('verify_views', [
                    'message' => $message,
                    'css' => 'verify_views.css'
                ]); 
            }
        } else {
            $this->render('verify_views', [
                'css' => 'verify_views.css'
            ]);
        }
    }

    /**
     * Sends an email using smtp with mailjet configuration
     *
     * @param string $email recipient address
     * @param string $token verification code to embed
     * @return void
     */
    private function sendVerificationEmail($email, $token) {
        try {
            $this->mail->isSMTP();
            $this->mail->Host       = $_ENV['MAILJET_HOST'];
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = $_ENV['MAILJET_USERNAME'];
            $this->mail->Password   = $_ENV['MAILJET_PASSWORD'];
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port       = $_ENV['MAILJET_PORT'];
            $this->mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
            $this->mail->addAddress($email);
            $this->mail->isHTML(true);
            $this->mail->Subject = $this->t('verification_code_subject', "Verification code");
            
            $bodyTemplate = $this->t('verification_code_body', "Your verification code is: %TOKEN%");
            if (empty($bodyTemplate)) {
                $bodyTemplate = "Your verification code is: %TOKEN%";
            }
            $body = str_replace('%TOKEN%', $token, $bodyTemplate);
            
            $this->mail->Body = $body;
            $this->mail->send();
        } catch (Exception $e) {
            error_log("Mail error: " . $this->mail->ErrorInfo);
        }
    }

    /**
     * Enables or disables 2fa for the current user
     *
     * @return void
     */
    public function toggle2FA() {
        $baseUrl = $_ENV['BASE_URL'];

        if (!isset($_SESSION['user_id'])) {
            header("Location: $baseUrl/user/login");
            exit;
        }

        $id_user = $_SESSION['user_id'];
        $action = $_POST['mode'] ?? null;
        
        if ($action === 'enable') {
            $this->user_model->setModeById($id_user, '2FA');
            $_SESSION['mode'] = '2FA';
            $message = $this->t('2fa_enabled', "Two-factor authentication enabled.");
        } elseif ($action === 'disable') {
            $this->user_model->setModeById($id_user, null);
            $_SESSION['mode'] = null;
            $message = $this->t('2fa_disabled', "Two-factor authentication disabled.");
        } else {
            $message = $this->t('invalid_request', "Invalid request.");
        }
        
        $this->render('setting_views', [
            'message' => $message,
            'css' => 'setting_views.css',   
            'trans' => $this->translations 
        ]);
    }

    /**
     * Destroys user session and redirects to login
     *
     * @return void
     */
    public function logout() {
        $baseUrl = $_ENV['BASE_URL'];
        session_unset();
        session_destroy();
        header("Location: $baseUrl/user/login");
        exit;
    }

    /**
     * Merges guest session data (cart images) into the logged-in user account
     *
     * @param int $userId
     */
    private function mergeGuestData($userId) {
        if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            $imagesModel = new ImagesModel();
            
            foreach ($_SESSION['cart'] as $item) {
                if (isset($item['image_id'])) {
                    $imagesModel->assignImageToUser($item['image_id'], $userId);
                }
            }
        }
    }
}