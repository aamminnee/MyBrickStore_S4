<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\UsersModel;
use App\Models\TokensModel;
use App\Models\TranslationModel;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

/**
 * Class CompteController
 * ** Manages the user dashboard ("mon compte")
 ** Displays user profile information and handles updates
 * * @package App\Controllers
 */
class CompteController extends Controller {

    /** @var UsersModel Handles user database operations. */
    private $user_model;

    /** @var TokensModel Handles authentication/activation tokens. */
    private $token_model;

    /** @var PHPMailer Instance of the mailer for sending emails. */
    private $mail;

    /** @var array Key/Value pair of translations. */
    private $translations;

    /**
     * Initializes models and mailer services
     */
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $lang = $_SESSION['lang'] ?? 'fr';
        
        $this->user_model = new UsersModel();
        $this->token_model = new TokensModel();
        $this->mail = new PHPMailer(true);

        $translation_model = new TranslationModel();
        $this->translations = $translation_model->getTranslations($lang);
        
        $dotenv = Dotenv::createImmutable(ROOT);
        $dotenv->load();
    }

    /**
     * Displays the main user dashboard with profile details
     *
     * @return void
     */
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $_ENV['BASE_URL'] . '/user/login');
            exit;
        }

        $id_user = $_SESSION['user_id'];
        $user = $this->user_model->getUserById($id_user);

        // retrieve flash messages for the view
        $success = $_SESSION['profile_success'] ?? null;
        $error = $_SESSION['profile_error'] ?? null;
        unset($_SESSION['profile_success'], $_SESSION['profile_error']);

        $this->render('compte_views', [
            'user' => $user, 
            't' => $this->translations,
            'success' => $success,
            'error' => $error,
            'css' => 'compte_views.css' 
        ]);
    }

    /**
     * handles the form submission to update user profile data.
     * stores data in session and requires email verification.
     *
     * @return void
     */
    public function update() {
        $baseUrl = $_ENV['BASE_URL'] ?? '';

        // check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header("Location: $baseUrl/user/login");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_user = $_SESSION['user_id'];
            
            $data = [
                'email'        => trim($_POST['email'] ?? ''),
                'first_name'   => trim($_POST['first_name'] ?? ''),
                'last_name'    => trim($_POST['last_name'] ?? ''),
                'phone'        => trim($_POST['phone'] ?? ''),
                'address_line' => trim($_POST['address_line'] ?? ''),
                'zip_code'     => trim($_POST['zip_code'] ?? ''),
                'city'         => trim($_POST['city'] ?? '')
            ];

            // validate email format
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $_SESSION['profile_error'] = $this->translations['invalid_email'] ?? "Le format de l'adresse email est invalide.";
                header("Location: $baseUrl/compte");
                exit;
            }

            // store pending data in session
            $_SESSION['pending_profile_update'] = $data;

            // generate validation token for profile update
            $token = $this->token_model->generateToken($id_user, "profile_update");
            
            // fetch current email to send the security code
            $currentEmail = $_SESSION['email']; 
            
            // send the verification email
            $this->sendProfileUpdateEmail($currentEmail, $token);

            // redirect user to the verification page
            header("Location: $baseUrl/user/verify");
            exit;
        }

        header("Location: $baseUrl/compte");
        exit;
    }

    /**
     * dispatches the profile update verification email via smtp
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
            
            $this->mail->Subject = "Validation de modification de profil";
            $this->mail->Body = "Pour valider les modifications de vos informations personnelles, veuillez entrer ce code de sécurité : <b>" . $token . "</b>";
            
            $this->mail->send();
        } catch (Exception $e) {
            // log the error if email fails to send
            error_log("mail error: " . $this->mail->ErrorInfo);
        }
    }

    /**
     * Triggers the account activation process for existing users
     *
     * @return void
     */
    public function activer() {

        if (session_status() === PHP_SESSION_NONE) session_start();
        $baseUrl = $_ENV['BASE_URL'] ?? '';

        if (!isset($_SESSION['user_id'])) {
            header("Location: $baseUrl/user/login");
            exit;
        }

        $id_user = $_SESSION['user_id'];
        $email = $_SESSION['email'];

        $token = $this->token_model->generateToken($id_user, "validation");
        
        $this->sendVerificationEmail($email, $token);

        header("Location: $baseUrl/user/verify");
        exit;
    }

    /**
     * Dispatches the activation email via smtp
     *
     * @param string $email recipient address
     * @param string $token activation code
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
            $this->mail->Subject = "Code d'activation";
            $this->mail->Body = "Votre code d'activation est : " . $token;
            $this->mail->send();
        } catch (Exception $e) {
            error_log("Mail error: " . $this->mail->ErrorInfo);
        }
    }
}