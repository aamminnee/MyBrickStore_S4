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
 * class UserController
 * manages user authentication lifecycle including login, registration,
 * password recovery and security settings like 2fa.
 * * @package App\Controllers
 */
class UserController extends Controller {

    /** * @var UsersModel handles user database operations
     */
    private $user_model;

    /** * @var TokensModel handles token generation and verification
     */
    private $token_model;

    /** * @var PHPMailer instance of the mailer service
     */
    private $mail;

    /** * @var array key/value pair of translations
     */
    private $translations;

    /**
     * constructor.
     * initializes models and mailer configuration.
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
     * retrieves a translation for a given key.
     *
     * @param string $key the translation key
     * @param string $default default text if key is missing
     * @return string translated text
     */
    private function t($key, $default = '') {
        return $this->translations[$key] ?? $default;
    }

    /**
     * private method for verifying the hcaptcha.
     * * @return bool true if verified, false otherwise
     */
    private function verifyHCaptcha() {
        $hcaptchaSecret   = $_ENV['CAPTCHA_SECRET_KEY'] ?? '';
        $hcaptchaResponse = $_POST['h-captcha-response'] ?? '';

        if (empty($hcaptchaResponse)) {
            return false;
        }

        $verify = file_get_contents(
            'https://hcaptcha.com/siteverify?secret=' . urlencode($hcaptchaSecret) .
            '&response=' . urlencode($hcaptchaResponse) .
            '&remoteip=' . $_SERVER['REMOTE_ADDR']
        );
        
        $captchaSuccess = json_decode($verify, true);
        return (!empty($captchaSuccess['success']) && $captchaSuccess['success'] === true);
    }

    /**
     * handles user login process with captcha and 2fa support.
     *
     * @return void
     */
    public function login() {
        $baseUrl = $_ENV['BASE_URL'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email']) && !empty($_POST['password'])) {

            if (!$this->verifyHCaptcha()) {
                $message = $this->t('captcha_invalid', "Veuillez valider le Captcha.");
                $this->render('login_views', ['message' => $message, 'css' => 'login_views.css']);
                return;
            }

            $email = trim($_POST['email']);
            $password = $_POST['password'];
            
            $user = $this->user_model->getUserByEmail($email);

            $userMdp   = is_object($user) ? ($user->mdp ?? null) : ($user['mdp'] ?? null);
            $userId    = is_object($user) ? ($user->id_user ?? null) : ($user['id_user'] ?? null);
            $userMode  = is_object($user) ? ($user->mode ?? null) : ($user['mode'] ?? null);
            $userEmail = is_object($user) ? ($user->email ?? null) : ($user['email'] ?? null);

            if ($user && password_verify($password, $userMdp)) {
                
                $modeClean = strtolower($userMode ?? '');
                if ($modeClean === '2fa') $modeClean = 'email'; 

                if (in_array($modeClean, ['email', 'app'])) {

                    $_SESSION['temp_2fa_user_id'] = $userId;
                    $_SESSION['temp_2fa_email']   = $userEmail;
                    $_SESSION['temp_2fa_mode']    = $modeClean;
                    
                    if ($modeClean !== 'app') {
                        $token = $this->token_model->generateToken($userId, "2FA");
                        $this->sendVerificationEmail($userEmail, $token);
                    }
                    
                    header("Location: $baseUrl/user/verify");
                    exit;
                }

                $user = $this->processLoyaltyId($user);
                $this->finalizeLogin($user);
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
     * finalizes the login process by setting session variables and redirecting.
     *
     * @param object|array $userFull user data object or array
     * @return void
     */
    private function finalizeLogin($userFull) {
        $baseUrl = $_ENV['BASE_URL'] ?? '';
        
        if ($userFull) {
            $idUser = is_object($userFull) ? ($userFull->id_user ?? null) : ($userFull['id_user'] ?? null);
            $email  = is_object($userFull) ? ($userFull->email ?? null) : ($userFull['email'] ?? null);
            $etat   = is_object($userFull) ? ($userFull->status ?? ($userFull->etat ?? null)) : ($userFull['status'] ?? ($userFull['etat'] ?? null));
            $mode   = is_object($userFull) ? ($userFull->mode ?? null) : ($userFull['mode'] ?? null);
            $role   = is_object($userFull) ? ($userFull->role ?? 'user') : ($userFull['role'] ?? 'user');

            $avatar = is_object($userFull) ? ($userFull->avatar ?? null) : ($userFull['avatar'] ?? null);
            if ($avatar) {
                $_SESSION['user_avatar'] = 'data:image/jpeg;base64,' . base64_encode($avatar);
            } else {
                $_SESSION['user_avatar'] = null;
            }
            
            $username = is_object($userFull) ? ($userFull->username ?? null) : ($userFull['username'] ?? null);
            if ($username !== null) {
                $_SESSION['username'] = $username;
            }

            $_SESSION['user_id']  = $idUser;
            $_SESSION['email']    = $email;
            $_SESSION['status']   = $etat;
            $_SESSION['mode']     = $mode;
            $_SESSION['role']     = $role;
            
            unset($_SESSION['temp_2fa_user_id'], $_SESSION['temp_2fa_email'], $_SESSION['temp_2fa_mode']);

            $this->mergeGuestData($idUser);
            
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
            $message = $this->t('user_not_found', "Erreur critique : utilisateur introuvable.");
            $this->render('login_views', ['message' => $message, 'css' => 'login_views.css']);
        }
    }

    /**
     * redirects authorized users to the admin dashboard.
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
     * handles new user registration and validation email sending.
     *
     * @return void
     */
    public function register() {
        $baseUrl = $_ENV['BASE_URL'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {

            if (!$this->verifyHCaptcha()) {
                $msg = $this->t('captcha_invalid', "Veuillez valider le Captcha.");
                $this->render('register_views', ['error' => $msg, 'css' => 'register_views.css']);
                return;
            }

            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if ($password !== $confirm_password) {
                $this->render('register_views', [
                    'error' => $this->t('passwords_not_matching', "Les mots de passe ne correspondent pas."),
                    'css' => 'register_views.css'
                ]);
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
                $user = $this->processLoyaltyId($user);
                $userId = is_object($user) ? ($user->id_user ?? null) : ($user['id_user'] ?? null);
                
                if ($userId) {
                    $_SESSION['email'] = $email;
                    
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
     * processes the final password update after validation.
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
            
            $_SESSION['success_message'] = $this->t('password_changed_success', "Mot de passe modifié avec succès.");
            
            header('Location: ' . $_ENV['BASE_URL'] . '/setting');
            exit;

        } else {
            $this->render('reset_password_views', [
                'css' => 'reset_password_views.css'
            ]);
        }
    }

    /**
     * initiates the password reset flow by sending an email link.
     *
     * @return void
     */
    public function resetPassword() {
        $baseUrl = $_ENV['BASE_URL'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
            $email = trim($_POST['email']);
            
            $user = $this->user_model->getUserByEmail($email);

            if ($user) {
                $userId    = is_object($user) ? ($user->id_user ?? null) : ($user['id_user'] ?? null);
                $userEmail = is_object($user) ? ($user->email ?? null) : ($user['email'] ?? null);

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
     * verifies tokens for account activation, password reset, 2fa or profile updates.
     *
     * @return void
     */
    public function verify() {
        $baseUrl = $_ENV['BASE_URL'] ?? '';
        
        $message = $_SESSION['verify_error'] ?? null;
        $success = $_SESSION['verify_success'] ?? null;
        unset($_SESSION['verify_error'], $_SESSION['verify_success']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
            $token = $_POST['token'];
            
            $isAppLogin = (isset($_SESSION['temp_2fa_mode']) && $_SESSION['temp_2fa_mode'] === 'app');
            
            $token_data = $this->token_model->verifyToken($token);

            if ($isAppLogin && !$token_data) {
                $userId = $_SESSION['temp_2fa_user_id'] ?? null;
                $userFull = $this->user_model->getUserById($userId);
                $secret = is_object($userFull) ? ($userFull->totp_secret ?? null) : ($userFull['totp_secret'] ?? null);
                
                if ($this->user_model->verifyTOTP($secret, $token)) {
                    $this->finalizeLogin($userFull);
                    exit;
                } else {
                    $message = $this->t('token_invalid', "Code Google Authenticator invalide ou expiré.");
                }
            }
            elseif ($token_data) {
                $this->token_model->consumeToken($token);
                $this->token_model->deleteToken();
                
                $userId = is_object($token_data) ? ($token_data->id_Customer ?? null) : ($token_data['id_Customer'] ?? null);
                $types  = is_object($token_data) ? ($token_data->types ?? null) : ($token_data['types'] ?? null);

                if ($types === 'validation') {
                    $this->user_model->activateUser($userId);

                    $userFull = $this->user_model->getUserById($userId);
                    if ($userFull) {
                        $this->finalizeLogin($userFull);
                        exit;
                    } else {
                        header("Location: $baseUrl/user/login");
                        exit;
                    }

                } elseif ($types === 'reinitialisation') {
                    $_SESSION['user_id'] = $userId; 
                    header("Location: $baseUrl/user/resetPasswordForm"); 
                    exit;

                } elseif ($types === '2FA') {
                    $userFull = $this->user_model->getUserById($userId); 
                    if ($userFull) {
                        $this->finalizeLogin($userFull);
                        exit;
                    }
                } elseif ($types === 'profile_update') {
                    if (isset($_SESSION['pending_profile_update'])) {
                        $data = $_SESSION['pending_profile_update'];
                        $result = $this->user_model->updateUserProfile($userId, $data);

                        if ($result === true) {
                            $_SESSION['email'] = $data['email'];
                            if (!empty($data['first_name'])) {
                                $_SESSION['user_name'] = $data['first_name'];
                            }
                            $_SESSION['profile_success'] = $this->t('profile_update_success', "Vos informations ont été mises à jour avec succès.");
                        } else {
                            $_SESSION['profile_error'] = $result;
                        }
                        
                        unset($_SESSION['pending_profile_update']);
                        header("Location: $baseUrl/compte");
                        exit;
                    }
                }
            } else {
                if (method_exists($this->token_model, 'isTokenExpired') && $this->token_model->isTokenExpired($token)) {
                    $message = $this->t('token_expired', "Votre code de sécurité a expiré. Veuillez en demander un nouveau.");
                } else {
                    $message = $this->t('token_invalid', "Code invalide ou introuvable.");
                }
            }
        } 
        
        $this->render('verify_views', [
            'message' => $message,
            'success' => $success,
            'css' => 'verify_views.css'
        ]);
    }

    /**
     * resends the verification code based on the current session context.
     *
     * @return void
     */
    public function resendCode() {
        $baseUrl = $_ENV['BASE_URL'] ?? '';
        $userId = null;
        $email = null;
        $type = null;

        if (isset($_SESSION['temp_2fa_user_id'], $_SESSION['temp_2fa_email'])) {
            $userId = $_SESSION['temp_2fa_user_id'];
            $email = $_SESSION['temp_2fa_email'];
            $type = '2FA';
        } elseif (isset($_SESSION['user_id'], $_SESSION['pending_profile_update'])) {
            $userId = $_SESSION['user_id'];
            $email = $_SESSION['email'];
            $type = 'profile_update';
        } elseif (isset($_SESSION['email'])) {
            $email = $_SESSION['email'];
            $user = $this->user_model->getUserByEmail($email);
            if ($user) {
                $userId = is_object($user) ? ($user->id_user ?? null) : ($user['id_user'] ?? null);
                $status = is_object($user) ? ($user->status ?? $user->etat ?? null) : ($user['status'] ?? $user['etat'] ?? null);
                $type = ($status === 'valide') ? 'reinitialisation' : 'validation';
            }
        }

        if ($userId && $email && $type) {
            $newToken = $this->token_model->generateToken($userId, $type);

            if ($type === 'profile_update') {
                $this->sendProfileUpdateEmail($email, $newToken);
            } else {
                $this->sendVerificationEmail($email, $newToken);
            }

            $_SESSION['verify_success'] = $this->t('Un nouveau code a été envoyé à votre adresse e-mail.');
        } else {
            $_SESSION['verify_error'] = $this->t("Impossible de renvoyer le code.");
        }

        header("Location: $baseUrl/user/verify");
        exit;
    }

    /**
     * sends an email using smtp with mailjet configuration.
     * contains a beautifully designed html template for better user experience.
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
            $this->mail->CharSet = 'UTF-8';
            $this->mail->Subject = $this->t('verification_code_subject', "Code de vérification de votre compte");
            
            $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; border-radius: 8px; border: 1px solid #e0e0e0;'>
                <h2 style='color: #006CB7; text-align: center; font-size: 24px;'>Vérification de sécurité requise</h2>
                <p style='color: #333; font-size: 16px; line-height: 1.6;'>Bonjour,</p>
                <p style='color: #333; font-size: 16px; line-height: 1.6;'>Nous avons bien reçu votre demande concernant votre compte MyBrickStore. La sécurité de vos données personnelles est notre priorité absolue.</p>
                <p style='color: #333; font-size: 16px; line-height: 1.6;'>Afin de confirmer que vous êtes bien à l'origine de cette opération, veuillez utiliser le code de vérification ci-dessous :</p>
                
                <div style='text-align: center; margin: 30px 0; padding: 20px; background-color: #ffffff; border-radius: 6px; border: 2px dashed #006CB7;'>
                    <span style='font-size: 38px; font-weight: bold; letter-spacing: 8px; color: #D92328;'>" . htmlspecialchars($token) . "</span>
                </div>
                
                <p style='color: #333; font-size: 16px; line-height: 1.6;'><strong>Information importante :</strong> Ce code est strictement personnel et confidentiel. Pour garantir un niveau de sécurité maximal, il <strong>expirera automatiquement dans 1 minute</strong>. S'il venait à expirer, vous devrez recommencer la procédure de vérification depuis notre site.</p>
                
                <p style='color: #333; font-size: 16px; line-height: 1.6;'>Si vous n'avez fait aucune demande ou si vous pensez qu'il s'agit d'une erreur, vous pouvez ignorer cet e-mail en toute sécurité. Votre compte reste parfaitement protégé.</p>
                
                <hr style='border: none; border-top: 1px solid #e0e0e0; margin: 30px 0;'>
                <p style='color: #888; font-size: 14px; text-align: center; line-height: 1.5;'>
                    Merci de votre confiance,<br>
                    <strong>L'équipe MyBrickStore</strong>
                </p>
            </div>
            ";
            
            $this->mail->Body = $body;
            $this->mail->send();
        } catch (Exception $e) {
            // log error
            error_log("mail error: " . $this->mail->ErrorInfo);
        }
    }

    /**
     * dispatches the profile update verification email via smtp.
     * included here so resendcode can access it directly.
     *
     * @param string $email recipient address
     * @param string $token verification code
     * @return void
     */
    private function sendProfileUpdateEmail($email, $token) {
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
            $this->mail->CharSet = 'UTF-8';
            $this->mail->Subject = "Validation de modification de votre profil";
            
            $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; border-radius: 8px; border: 1px solid #e0e0e0;'>
                <h2 style='color: #006CB7; text-align: center; font-size: 24px;'>Mise à jour de votre profil</h2>
                <p style='color: #333; font-size: 16px; line-height: 1.6;'>Bonjour,</p>
                <p style='color: #333; font-size: 16px; line-height: 1.6;'>Vous avez récemment demandé la modification de vos informations personnelles sur votre compte MyBrickStore.</p>
                <p style='color: #333; font-size: 16px; line-height: 1.6;'>Afin de protéger vos données et de valider définitivement ces changements, un code de confirmation est requis. Veuillez le saisir sur la page de vérification :</p>
                
                <div style='text-align: center; margin: 30px 0; padding: 20px; background-color: #ffffff; border-radius: 6px; border: 2px dashed #006CB7;'>
                    <span style='font-size: 38px; font-weight: bold; letter-spacing: 8px; color: #D92328;'>" . htmlspecialchars($token) . "</span>
                </div>
                
                <p style='color: #333; font-size: 16px; line-height: 1.6;'><strong>Information importante :</strong> Ce code de sécurité <strong>expirera dans 1 minute</strong>. Passé ce court délai, vos modifications seront automatiquement annulées et vous devrez recommencer l'opération.</p>
                
                <p style='color: #333; font-size: 16px; line-height: 1.6;'>Si vous n'avez effectué aucune modification, veuillez ignorer ce message. Vous pouvez également contacter notre support si vous suspectez une activité anormale sur votre compte.</p>
                
                <hr style='border: none; border-top: 1px solid #e0e0e0; margin: 30px 0;'>
                <p style='color: #888; font-size: 14px; text-align: center; line-height: 1.5;'>
                    Cordialement,<br>
                    <strong>L'équipe MyBrickStore</strong>
                </p>
            </div>
            ";
            
            $this->mail->Body = $body;
            $this->mail->send();
        } catch (Exception $e) {
            // log error
            error_log("mail error: " . $this->mail->ErrorInfo);
        }
    }

    /**
     * enables or disables 2fa for the current user.
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
     * destroys user session and redirects to login.
     *
     * @return void
     */
    public function logout() {
        $baseUrl = $_ENV['BASE_URL'];
        
        session_unset();
        session_destroy();
        
        header("Location: $baseUrl/");
        exit;
    }

    /**
     * merges guest session data (cart images) into the logged-in user account.
     *
     * @param int $userId
     * @return void
     */
    private function mergeGuestData($userId) {
        $imagesModel = new ImagesModel();
        
        if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                if (isset($item['image_id'])) {
                    $imagesModel->assignImageToUser($item['image_id'], $userId);
                }
            }
        }

        if (!empty($_SESSION['purchase_context']['items']) && is_array($_SESSION['purchase_context']['items'])) {
            foreach ($_SESSION['purchase_context']['items'] as $item) {
                if (isset($item['image_id'])) {
                    $imagesModel->assignImageToUser($item['image_id'], $userId);
                }
            }
        }
    }

    /**
     * handles the loyalty_id during login.
     * only reads from the database without ever generating an automatic id.
     *
     * @param object|array $user the user data from database
     * @return object|array updated user data
     */
    protected function processLoyaltyId($user) {
        // extract the current identifier safely
        $currentLoyaltyId = is_object($user) ? ($user->loyalty_id ?? null) : ($user['loyalty_id'] ?? null);

        if (!isset($_SESSION['user'])) {
            $_SESSION['user'] = [];
        }

        // if the player has an id, keep it in memory
        if (!empty($currentLoyaltyId)) {
            $_SESSION['user']['loyalty_id'] = $currentLoyaltyId;
        } else {
            // otherwise, ensure it remains explicitly empty (null) in memory
            $_SESSION['user']['loyalty_id'] = null;
        }

        return $user;
    }

    /**
     * retrieves the loyalty_id from the active session for the react application.
     * handles cors to allow the react app to read the php session cookie.
     *
     * @return void
     */
    public function getSessionLoyalty() {
        // allow react app (port 5173) to make requests with cookies
        header('Access-Control-Allow-Origin: http://localhost:5173');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Content-Type, Accept');
        
        // handle preflight options request from browser
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        header('Content-Type: application/json');

        // check if user is logged in and has a loyalty id
        if (isset($_SESSION['user']) && !empty($_SESSION['user']['loyalty_id'])) {
            echo json_encode(['loyalty_id' => $_SESSION['user']['loyalty_id']]);
        } else {
            echo json_encode(['loyalty_id' => null]);
        }
        exit;
    }
}