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
 * * Manages user authentication lifecycle including login, registration,
 * password recovery and security settings like 2fa.
 * * @package App\Controllers
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
     * Initializes models and mailer configuration.
     */
    public function __construct() {
        parent::__construct();
        
        // initialize database models
        $this->user_model = new UsersModel();
        $this->token_model = new TokensModel();
        
        // initialize mailer instance
        $this->mail = new PHPMailer(true);
        
        // load environment variables
        $dotenv = Dotenv::createImmutable(ROOT);
        $dotenv->load();

        // assign translations from parent
        $this->translations = $this->trans;
    }

    /**
     * Retrieves a translation for a given key.
     *
     * @param string $key the translation key
     * @param string $default default text if key is missing
     * @return string translated text
     */
    private function t($key, $default = '') {
        // return translated text or fallback default
        return $this->translations[$key] ?? $default;
    }

    /**
     * Private method for verifying the hCaptcha.
     * * @return bool true if verified, false otherwise
     */
    private function verifyHCaptcha() {
        // fetch secret key and user response
        $hcaptchaSecret   = $_ENV['CAPTCHA_SECRET_KEY'] ?? '';
        $hcaptchaResponse = $_POST['h-captcha-response'] ?? '';

        // fail early if no response
        if (empty($hcaptchaResponse)) {
            return false;
        }

        // contact hcaptcha api to verify response
        $verify = file_get_contents(
            'https://hcaptcha.com/siteverify?secret=' . urlencode($hcaptchaSecret) .
            '&response=' . urlencode($hcaptchaResponse) .
            '&remoteip=' . $_SERVER['REMOTE_ADDR']
        );
        
        // decode json response and check success status
        $captchaSuccess = json_decode($verify, true);
        return (!empty($captchaSuccess['success']) && $captchaSuccess['success'] === true);
    }

    /**
     * Handles user login process with captcha and 2fa support.
     *
     * @return void
     */
    public function login() {
        $baseUrl = $_ENV['BASE_URL'] ?? '';

        // process form if request is post
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email']) && !empty($_POST['password'])) {

            // verify captcha first
            if (!$this->verifyHCaptcha()) {
                $message = $this->t('captcha_invalid', "Veuillez valider le Captcha.");
                $this->render('login_views', ['message' => $message, 'css' => 'login_views.css']);
                return;
            }

            // trim inputs
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            
            // fetch user from database
            $user = $this->user_model->getUserByEmail($email);

            // safely extract user data handling both array and object returns
            $userMdp   = is_object($user) ? ($user->mdp ?? null) : ($user['mdp'] ?? null);
            $userId    = is_object($user) ? ($user->id_user ?? null) : ($user['id_user'] ?? null);
            $userMode  = is_object($user) ? ($user->mode ?? null) : ($user['mode'] ?? null);
            $userEmail = is_object($user) ? ($user->email ?? null) : ($user['email'] ?? null);

            // verify user exists and password is correct
            if ($user && password_verify($password, $userMdp)) {
                
                // normalize mode value
                $modeClean = strtolower($userMode ?? '');
                if ($modeClean === '2fa') $modeClean = 'email'; 

                // check if any 2fa is enabled 
                if (in_array($modeClean, ['email', 'app'])) {
                    
                    // set temporary session variables for 2fa flow
                    $_SESSION['temp_2fa_user_id'] = $userId;
                    $_SESSION['temp_2fa_email']   = $userEmail;
                    $_SESSION['temp_2fa_mode']    = $modeClean;
                    
                    // send verification email if mode is not app (totp)
                    if ($modeClean !== 'app') {
                        $token = $this->token_model->generateToken($userId, "2FA");
                        $this->sendVerificationEmail($userEmail, $token);
                    }
                    
                    // redirect to verification view
                    header("Location: $baseUrl/user/verify");
                    exit;
                }

                // finalize login directly if no 2fa is active
                $this->finalizeLogin($user);
                exit;
            } else {
                // handle wrong credentials
                $message = $this->t('login_error', "Email ou mot de passe incorrect.");
                $this->render('login_views', ['message' => $message, 'css' => 'login_views.css']);
            }
        } else {
            // render login form
            $this->render('login_views', ['css' => 'login_views.css']);
        }
    }

    /**
     * finalizes the login process by setting session variables and redirecting
     *
     * @param object|array $userFull user data object or array
     * @return void
     */
    private function finalizeLogin($userFull) {
        $baseUrl = $_ENV['BASE_URL'] ?? '';
        
        if ($userFull) {
            // populate final session variables securely
            $idUser = is_object($userFull) ? ($userFull->id_user ?? null) : ($userFull['id_user'] ?? null);
            $email  = is_object($userFull) ? ($userFull->email ?? null) : ($userFull['email'] ?? null);
            $etat   = is_object($userFull) ? ($userFull->status ?? ($userFull->etat ?? null)) : ($userFull['status'] ?? ($userFull['etat'] ?? null));
            $mode   = is_object($userFull) ? ($userFull->mode ?? null) : ($userFull['mode'] ?? null);
            $role   = is_object($userFull) ? ($userFull->role ?? 'user') : ($userFull['role'] ?? 'user');
            
            // optional username setup avoiding object as array fetch errors
            $username = is_object($userFull) ? ($userFull->username ?? null) : ($userFull['username'] ?? null);
            if ($username !== null) {
                $_SESSION['username'] = $username;
            }

            // confirm connection
            $_SESSION['user_id']  = $idUser;
            $_SESSION['email']    = $email;
            $_SESSION['status']   = $etat;
            $_SESSION['mode']     = $mode;
            $_SESSION['role']     = $role;
            
            // clear temporary session data
            unset($_SESSION['temp_2fa_user_id'], $_SESSION['temp_2fa_email'], $_SESSION['temp_2fa_mode']);

            // attach guest data to logged account
            $this->mergeGuestData($idUser);
            
            // route user correctly
            if ($role === 'admin') {
                header("Location: $baseUrl/admin");
            } else {
                if (isset($_SESSION['purchase_context'])) {
                    header("Location: $baseUrl/payment");
                } elseif (!empty($_SESSION['cart'])) {
                    header("Location: $baseUrl/cart");
                } else {
                    header("Location: $baseUrl/index.php");
                }
            }
        } else {
            // fallback error
            $message = "Erreur critique : utilisateur introuvable.";
            $this->render('login_views', ['message' => $message, 'css' => 'login_views.css']);
        }
    }

    /**
     * Redirects authorized users to the admin dashboard.
     *
     * @return void
     */
    public function admin() {
        $baseUrl = $_ENV['BASE_URL'] ?? '';
        
        // redirect to home if not admin
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header("Location: $baseUrl/index.php");
            exit;
        }

        // redirect to admin panel
        header("Location: $baseUrl/admin");
        exit;
    }

    /**
     * Handles new user registration and validation email sending.
     *
     * @return void
     */
    public function register() {
        $baseUrl = $_ENV['BASE_URL'];

        // process form if request is post
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {

            // verify captcha first
            if (!$this->verifyHCaptcha()) {
                $msg = $this->t('captcha_invalid', "Veuillez valider le Captcha.");
                $this->render('register_views', ['error' => $msg, 'css' => 'register_views.css']);
                return;
            }

            // get user input
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // check password confirmation
            if ($password !== $confirm_password) {
                $this->render('register_views', ['error' => "Les mots de passe ne correspondent pas.", 'css' => 'register_views.css']);
                return;
            }

            // validate password complexity
            $passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
            if (!preg_match($passwordPattern, $password)) {
                $msg = $this->t('password_invalid', "Le mot de passe doit contenir 8 caractères min, avec majuscule, minuscule, chiffre et spécial.");
                $this->render('register_views', ['error' => $msg, 'css' => 'register_views.css']);
                return;
            }

            // try adding user to database
            $result = $this->user_model->addUser($email, $password);
            
            // handle result scenarios
            if ($result === true) {
                $user = $this->user_model->getUserByEmail($email);
                $userId = is_object($user) ? ($user->id_user ?? null) : ($user['id_user'] ?? null);
                
                if ($userId) {
                    // generate token and send welcome email
                    $token = $this->token_model->generateToken($userId, "validation");
                    $this->sendVerificationEmail($email, $token);
                    header("Location: $baseUrl/user/verify");
                    exit;
                }
            } elseif ($result === "duplicate") {
                // handle email already exists
                $msg = $this->t('username_exists', "Cet email est déjà utilisé.");
                $this->render('register_views', ['error' => $msg, 'css' => 'register_views.css']);
                exit;
            } else {
                // handle generic failure
                 $msg = $this->t('register_error', "L'inscription a échoué, veuillez réessayer.");
                 $this->render('register_views', ['error' => $msg, 'css' => 'register_views.css']);
                exit;
            }
        } else {
            // retrieve any pending errors from session
            $error = $_SESSION['register_message'] ?? null;
            unset($_SESSION['register_message']);

            // render register form
            $this->render('register_views', ['error' => $error, 'css' => 'register_views.css']);
        }
    }

    /**
     * Processes the final password update after validation.
     *
     * @return void
     */
    public function resetPasswordForm() {
        // process form submission
        if (isset($_POST['reset_password'])) {
            $password = $_POST['password'];
            $password_confirm = $_POST['password_confirm'];

            // verify passwords match
            if ($password !== $password_confirm) {
                $error = "Les mots de passe ne correspondent pas.";
                $this->render('reset_password_views', [
                    'error' => $error,
                    'css' => 'reset_password_views.css'
                ]);
                return;
            }

            // validate new password constraints
            $validation = $this->user_model->validateNewPassword($_SESSION['user_id'], $password);

            if ($validation !== true) {
                $this->render('reset_password_views', [
                    'error' => $validation,
                    'css' => 'reset_password_views.css'
                ]);
                return;
            }

            // update password in database
            $this->user_model->updatePassword($_SESSION['user_id'], $password);
            
            // set success flash message
            $_SESSION['success_message'] = "Mot de passe modifié avec succès.";
            
            // redirect to settings
            header('Location: ' . $_ENV['BASE_URL'] . '/setting');
            exit;

        } else {
            // render form
            $this->render('reset_password_views', [
                'css' => 'reset_password_views.css'
            ]);
        }
    }

    /**
     * Initiates the password reset flow by sending an email link.
     *
     * @return void
     */
    public function resetPassword() {
        $baseUrl = $_ENV['BASE_URL'] ?? '';

        // handle external request via email input
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
            $email = trim($_POST['email']);
            
            // fetch user account
            $user = $this->user_model->getUserByEmail($email);

            if ($user) {
                $userId    = is_object($user) ? ($user->id_user ?? null) : ($user['id_user'] ?? null);
                $userEmail = is_object($user) ? ($user->email ?? null) : ($user['email'] ?? null);

                // save email to session for flow context
                $_SESSION['email'] = $userEmail; 

                // generate recovery token and mail it
                $token = $this->token_model->generateToken($userId, "reinitialisation");
                $this->sendVerificationEmail($userEmail, $token);

                header("Location: $baseUrl/user/verify");
                exit;
            } else {
                // show error if account not found
                $message = "Aucun compte associé à cet email.";
                $this->render('forgot_password_views', [
                    'message' => $message,
                    'css' => 'login_views.css'
                ]);
            }
        }
        // handle internal request from logged in user
        elseif (isset($_SESSION['user_id'])) {
            $token = $this->token_model->generateToken($_SESSION['user_id'], "reinitialisation");
            $this->sendVerificationEmail($_SESSION['email'], $token);
            header("Location: $baseUrl/user/verify");
            exit;
        }
        else {
            // render forgot password form
            $this->render('forgot_password_views', [
                'css' => 'login_views.css'
            ]);
        }
    }

    /**
     * verifies tokens for account activation, password reset, 2fa or profile updates.
     *
     * @return void
     */
    public function verify() {
        $baseUrl = $_ENV['BASE_URL'] ?? '';

        // process form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
            $token = $_POST['token'];
            
            $isAppLogin = (isset($_SESSION['temp_2fa_mode']) && $_SESSION['temp_2fa_mode'] === 'app');
            
            // check if token is valid in database (for email tokens)
            $token_data = $this->token_model->verifyToken($token);

            // 1. handle totp (app) verification specifically without database token
            if ($isAppLogin && !$token_data) {
                $userId = $_SESSION['temp_2fa_user_id'] ?? null;
                $userFull = $this->user_model->getUserById($userId);
                $secret = is_object($userFull) ? ($userFull->totp_secret ?? null) : ($userFull['totp_secret'] ?? null);
                
                // verify pure time based logic using model
                if ($this->user_model->verifyTOTP($secret, $token)) {
                    $this->finalizeLogin($userFull);
                    exit;
                } else {
                    $message = $this->t('token_invalid', "Code Google Authenticator invalide ou expiré.");
                    $this->render('verify_views', [
                        'message' => $message,
                        'css' => 'verify_views.css'
                    ]); 
                    return;
                }
            }

            // 2. handle standard database tokens
            if ($token_data) {
                // mark token as used and cleanup
                $this->token_model->consumeToken($token);
                $this->token_model->deleteToken();
                
                // extract data from token safely
                $userId = is_object($token_data) ? ($token_data->id_Customer ?? null) : ($token_data['id_Customer'] ?? null);
                $types  = is_object($token_data) ? ($token_data->types ?? null) : ($token_data['types'] ?? null);

                // handle validation type
                if ($types === 'validation') {
                    $this->user_model->activateUser($userId);
                    if(isset($_SESSION['user_id'])) {
                        $_SESSION['status'] = 'valide';
                        header("Location: $baseUrl/index.php");
                        exit;
                    }
                    header("Location: $baseUrl/user/login");
                    exit;

                // handle reset type
                } elseif ($types === 'reinitialisation') {
                    $_SESSION['user_id'] = $userId; 
                    header("Location: $baseUrl/user/resetPasswordForm"); 
                    exit;

                // handle email 2fa type
                } elseif ($types === '2FA') {
                    $userFull = $this->user_model->getUserById($userId); 
                    if ($userFull) {
                        $this->finalizeLogin($userFull);
                        exit;
                    }
                    
                // handle profile update type
                } elseif ($types === 'profile_update') {
                    // execute the pending database update
                    if (isset($_SESSION['pending_profile_update'])) {
                        $data = $_SESSION['pending_profile_update'];
                        $result = $this->user_model->updateUserProfile($userId, $data);

                        if ($result === true) {
                            $_SESSION['email'] = $data['email'];
                            if (!empty($data['first_name'])) {
                                $_SESSION['user_name'] = $data['first_name'];
                            }
                            $_SESSION['profile_success'] = "Vos informations ont été mises à jour avec succès.";
                        } else {
                            $_SESSION['profile_error'] = $result;
                        }
                        
                        // cleanup
                        unset($_SESSION['pending_profile_update']);
                        header("Location: $baseUrl/compte");
                        exit;
                    }
                }
            } else {
                // token is invalid or expired
                $message = $this->t('token_invalid', "Code invalide ou expiré.");
                $this->render('verify_views', [
                    'message' => $message,
                    'css' => 'verify_views.css'
                ]); 
            }
        } else {
            // render standard verify view
            $this->render('verify_views', [
                'css' => 'verify_views.css'
            ]);
        }
    }

    /**
     * Sends an email using smtp with mailjet configuration.
     *
     * @param string $email recipient address
     * @param string $token verification code to embed
     * @return void
     */
    private function sendVerificationEmail($email, $token) {
        try {
            // configure phpmailer for mailjet
            $this->mail->isSMTP();
            $this->mail->Host       = $_ENV['MAILJET_HOST'];
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = $_ENV['MAILJET_USERNAME'];
            $this->mail->Password   = $_ENV['MAILJET_PASSWORD'];
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port       = $_ENV['MAILJET_PORT'];
            
            // set sender and recipient
            $this->mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
            $this->mail->addAddress($email);
            
            // prepare email content
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
            // log silently if mail fails
            error_log("Mail error: " . $this->mail->ErrorInfo);
        }
    }

    /**
     * Enables or disables 2fa for the current user.
     *
     * @return void
     */
    public function toggle2FA() {
        $baseUrl = $_ENV['BASE_URL'];

        // require active session
        if (!isset($_SESSION['user_id'])) {
            header("Location: $baseUrl/user/login");
            exit;
        }

        $id_user = $_SESSION['user_id'];
        $action = $_POST['mode'] ?? null;
        
        // update database mode and session based on requested action
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
        
        // render settings with feedback
        $this->render('setting_views', [
            'message' => $message,
            'css' => 'setting_views.css',   
            'trans' => $this->translations 
        ]);
    }

    /**
     * Destroys user session and redirects to login.
     *
     * @return void
     */
    public function logout() {
        $baseUrl = $_ENV['BASE_URL'];
        
        // destroy all session data
        session_unset();
        session_destroy();
        
        // send user to login page
        header("Location: $baseUrl/user/login");
        exit;
    }

    /**
     * Merges guest session data (cart images) into the logged-in user account.
     *
     * @param int $userId
     */
    private function mergeGuestData($userId) {
        $imagesModel = new ImagesModel();
        
        // link cart items to user id
        if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                if (isset($item['image_id'])) {
                    $imagesModel->assignImageToUser($item['image_id'], $userId);
                }
            }
        }

        // link purchase context items to user id
        if (!empty($_SESSION['purchase_context']['items']) && is_array($_SESSION['purchase_context']['items'])) {
            foreach ($_SESSION['purchase_context']['items'] as $item) {
                if (isset($item['image_id'])) {
                    $imagesModel->assignImageToUser($item['image_id'], $userId);
                }
            }
        }
    }
}