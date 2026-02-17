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
 * 
 ** Manages the user dashboard ("mon compte")
 ** Displays user profile information and account status
 * 
 * @package App\Controllers
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

        $this->render('compte_views', [
            'user' => $user, 
            't' => $this->translations,
            'css' => 'compte_views.css' 
        ]);
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