<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialModel;
use App\Models\TranslationModel;
use App\Models\MosaicModel;
use App\Models\CommandeModel;
use App\Models\UsersModel;
use App\Models\ImagesModel;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Class PaymentController
 * * Handles the checkout process, payment simulation, and order finalization.
 * Supports full cart checkout, single item checkout, and direct buy.
 * * @package App\Controllers
 */
class PaymentController extends Controller {

    /** * @var array Key/Value pair of translations. 
     */
    private $translations;

    /** * @var string Base URL for PayPal API (Sandbox). 
     */
    private $paypalBaseUrl = 'https://api-m.sandbox.paypal.com';

    /**
     * Constructor.
     * Initializes the controller and loads translation strings.
     */
    public function __construct() {
        $lang = $_SESSION['lang'] ?? 'fr';
        $translation_model = new TranslationModel();
        $this->translations = $translation_model->getTranslations($lang);
    }

    /**
     * Displays the checkout page with order summary.
     * Uses 'purchase_context' session to determine what is being bought.
     *
     * @return void
     */
    public function index() {
        if (!isset($_SESSION['user_id'])) { 
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/user/login"); 
            exit; 
        }

        // fallback to full cart or redirect if no purchase context exists
        if (!isset($_SESSION['purchase_context'])) {
            if (!empty($_SESSION['cart'])) {
                $_SESSION['purchase_context'] = [
                    'source' => 'full_cart',
                    'items' => $_SESSION['cart']
                ];
            } else {
                header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/cart");
                exit;
            }
        }

        $itemsToPay = $_SESSION['purchase_context']['items'];
        
        $subTotal = 0;
        foreach ($itemsToPay as $item) { 
            $item = (array)$item; 
            $subTotal += $item['price']; 
        }

        $delivery = \App\Models\MosaicModel::DELIVERY_FEE;
        $totalPrice = $subTotal + $delivery;

        $usersModel = new UsersModel();
        $clientInfo = (array) $usersModel->getUserById($_SESSION['user_id']);

        $this->render('payment_views', [
            't' => $this->translations,
            'total' => $totalPrice,
            'items' => $itemsToPay,
            'user' => $clientInfo, // fixed the variable name from 'client' to 'user'
            'css' => 'payment_views.css'
        ]);
    }

    /**
     * Initiates the paypal payment flow.
     *
     * @return void
     */
    public function process() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_SESSION['purchase_context']) || empty($_SESSION['purchase_context']['items'])) { 
                header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/cart"); 
                exit; 
            }

            $_SESSION['billing_temp'] = [
                'first_name'   => $_POST['first_name'] ?? '',
                'last_name'    => $_POST['last_name'] ?? '',
                'phone'        => $_POST['phone'] ?? '',
                'address_line' => $_POST['address_line'] ?? ($_POST['adress'] ?? ''),
                'zip_code'     => $_POST['zip_code'] ?? '',
                'city'         => $_POST['city'] ?? ''
            ];

            $itemsToPay = $_SESSION['purchase_context']['items'];
            $subTotal = 0;
            foreach ($itemsToPay as $item) { 
                $item = (array)$item; 
                $subTotal += $item['price']; 
            }
            $delivery = \App\Models\MosaicModel::DELIVERY_FEE;
            $totalAmount = $subTotal + $delivery;

            $accessToken = $this->getPayPalAccessToken();
            if (!$accessToken) die("Erreur connexion PayPal Sandbox. Vérifie ton PAYPAL_ID et PAYPAL_KEY.");

            $baseUrlEnv = $_ENV['BASE_URL'] ?? '';
            if (strpos($baseUrlEnv, 'http') === 0) {
                $absoluteBaseUrl = rtrim($baseUrlEnv, '/');
            } else {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                $domainName = $_SERVER['HTTP_HOST'];
                $absoluteBaseUrl = $protocol . $domainName . $baseUrlEnv;
            }

            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => 'EUR',
                        'value' => number_format($totalAmount, 2, '.', '')
                    ]
                ]],
                'application_context' => [
                    'return_url' => $absoluteBaseUrl . '/payment/success',
                    'cancel_url' => $absoluteBaseUrl . '/payment/cancel'
                ]
            ];

            $response = $this->callPayPalApi('/v2/checkout/orders', $orderData, $accessToken);

            if (isset($response->links)) {
                foreach ($response->links as $link) {
                    if ($link->rel === 'approve') {
                        header("Location: " . $link->href);
                        exit;
                    }
                }
            }
        }
    }

    /**
     * Handles the callback from paypal after user approval.
     *
     * @return void
     */
    public function success() {
        if (!isset($_GET['token'])) { 
            header("Location: " . ($_ENV['BASE_URL']) . "/cart"); 
            exit; 
        }

        $paypalOrderId = $_GET['token'];
        $accessToken = $this->getPayPalAccessToken();
        $captureResponse = $this->callPayPalApi("/v2/checkout/orders/$paypalOrderId/capture", (object)[], $accessToken);

        if (isset($captureResponse->status) && $captureResponse->status === 'COMPLETED') {
            $this->finalizeOrder($captureResponse);
        } else {
            echo '<div style="font-family:sans-serif; padding:20px; color:#D92328;">';
            echo '<h1>Paiement non validé</h1>';
            echo '<p>Le paiement n\'a pas pu être capturé par PayPal.</p>';
            
            echo '<h3>Détails techniques (Debug) :</h3>';
            echo '<pre style="background:#f4f4f4; padding:10px; border-radius:5px;">';
            print_r($captureResponse);
            echo '</pre>';
            
            echo '<p><a href="' . ($_ENV['BASE_URL'] ?? '') . '/payment">Retourner à la page de paiement</a></p>';
            echo '</div>';
        }
    }

    /**
     * Handles cases where the user aborts the payment process.
     *
     * @return void
     */
    public function cancel() {
        header("Location: " . ($_ENV['BASE_URL']) . "/payment");
        exit;
    }

    /**
     * Persists the order to the database, generates final mosaic files, and updates stock.
     *
     * @param object $paypalData response data from paypal api
     * @return void
     */
    private function finalizeOrder($paypalData) {
        $userId = $_SESSION['user_id'];
        $usersModel = new \App\Models\UsersModel(); 
        $billingTemp = $_SESSION['billing_temp'] ?? [];
        $userInfo = (array) $usersModel->getUserById($userId);
        $fullAddress = trim(($billingTemp['address_line'] ?? '') . ' - ' . ($billingTemp['zip_code'] ?? '') . ' ' . ($billingTemp['city'] ?? ''));
        if ($fullAddress === '-') $fullAddress = 'Adresse non fournie';
        
        $billingInfo = [
            'first_name'   => !empty($billingTemp['first_name']) ? $billingTemp['first_name'] : ($userInfo['first_name'] ?? 'Client'),
            'last_name'    => !empty($billingTemp['last_name']) ? $billingTemp['last_name'] : ($userInfo['last_name'] ?? 'Inconnu'),
            'phone'        => $billingTemp['phone'] ?? ($userInfo['phone'] ?? ''),
            'address_line' => $billingTemp['address_line'] ?? '',
            'zip_code'     => $billingTemp['zip_code'] ?? '',
            'city'         => $billingTemp['city'] ?? '',
            'full_address' => $fullAddress,
            'email'        => $userInfo['email'] ?? 'email@test.com'
        ];

        $itemsToProcess = $_SESSION['purchase_context']['items'] ?? [];

        $subTotal = 0;
        foreach ($itemsToProcess as $item) { 
            $item = (array)$item; 
            $subTotal += $item['price']; 
        }
        $totalAmount = $subTotal + \App\Models\MosaicModel::DELIVERY_FEE;

        $cardInfo = [
            'number' => $paypalData->id,
            'expiry' => date('Y-m', strtotime('+1 year')),
            'cvv'    => '000',
            'brand'  => 'PayPal'
        ];

        $mosaicModel = new MosaicModel();
        $imagesModel = new ImagesModel();
        $realMosaicIds = []; 
        
        foreach ($itemsToProcess as $item) {
            $item = (array)$item;
            $imgId = $item['image_id'];
            $style = $item['style'];
            $imgDb = $imagesModel->getImageById($imgId, $userId);

            if (!$imgDb) {
                $orphanCheck = $imagesModel->getImageById($imgId, null);
                if ($orphanCheck && $orphanCheck->id_Customer === null) {
                    $imagesModel->assignImageToUser($imgId, $userId);
                    $imgDb = $orphanCheck;
                }
            }
            
            if ($imgDb) {
                $ext = (strpos($imgDb->file_type, 'png') !== false) ? 'png' : 'jpg';
                
                try {
                    $genResults = $mosaicModel->generateTemporaryMosaics($imgId, $imgDb->file, $ext);
                    $pavageContent = $genResults[$style]['txt'] ?? null;

                    if ($pavageContent) {
                        $newMosaicId = $mosaicModel->saveSelectedMosaic($imgId, $pavageContent, $style);
                        if ($newMosaicId) $realMosaicIds[] = $newMosaicId;
                    } else {
                        error_log("Erreur Payment: Contenu pavage vide pour img $imgId style $style");
                    }
                } catch (\Exception $e) {
                    error_log("Exception Payment Java: " . $e->getMessage());
                }
            } else {
                error_log("Erreur Payment: Image $imgId introuvable pour User $userId");
            }
        }

        if (empty($realMosaicIds)) { 
            echo "Erreur critique : Impossible de générer les mosaïques finales."; 
            exit; 
        }

        $financialModel = new FinancialModel();
        $result = $financialModel->processOrder($userId, $realMosaicIds[0], $cardInfo, $totalAmount, $billingInfo);

        if (!is_numeric($result)) { 
            echo "Erreur BDD : " . $result; 
            return; 
        }
        
        $orderId = (int)$result;

        foreach ($realMosaicIds as $idMosaic) {
            $mosaicModel->requete("UPDATE Mosaic SET id_Order = ? WHERE id_Mosaic = ?", [$orderId, $idMosaic]);
            if (!$mosaicModel->hasComposition($idMosaic)) {
                $mosaicModel->saveMosaicComposition($idMosaic);
            }
            $mosaicModel->deductStockFromMosaic($idMosaic);
        }

        $commandeModel = new CommandeModel(); 
        $orderDetails = $commandeModel->getOrderDetails($orderId);
        $orderDetails['total_amount'] = $totalAmount; 
        
        try {
            $this->sendInvoiceEmail($billingInfo['email'], $orderDetails);
        } catch (\Throwable $e) {
            error_log("Erreur envoi mail facture : " . $e->getMessage());
        }
        
        if (isset($_SESSION['purchase_context']['source'])) {
            if ($_SESSION['purchase_context']['source'] === 'full_cart') {
                unset($_SESSION['cart']);
            } elseif ($_SESSION['purchase_context']['source'] === 'single_cart_item') {
                $originId = $_SESSION['purchase_context']['origin_id'] ?? null;
                if ($originId && isset($_SESSION['cart'])) {
                    foreach ($_SESSION['cart'] as $key => $cartItem) {
                        if ($cartItem['id_unique'] === $originId) {
                            unset($_SESSION['cart'][$key]);
                            break;
                        }
                    }
                    $_SESSION['cart'] = array_values($_SESSION['cart']);
                }
            }
        }
        
        unset($_SESSION['purchase_context']);
        unset($_SESSION['billing_temp']);

        header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/payment/confirmation?id=" . $orderId);
        exit;
    }

    /**
     * Renders the order confirmation and invoice breakdown view.
     *
     * @return void
     */
    public function confirmation() {
        if (!isset($_GET['id'])) { 
            header("Location: " . ($_ENV['BASE_URL']) . "/index.php"); 
            exit; 
        }

        $orderId = (int)$_GET['id'];
        $commandeModel = new CommandeModel();
        $mosaicModel = new MosaicModel();
        $orderDetails = $commandeModel->getOrderDetails($orderId);

        if (!$orderDetails) { 
            header("Location: " . ($_ENV['BASE_URL']) . "/index.php"); 
            exit; 
        }

        $orderDetails = (array) $orderDetails; 
        $items = $mosaicModel->getMosaicsByOrderId($orderId);
        $totalHandling = 0;
        $itemsTotalTTC = 0;
        $handlingUnit = \App\Models\MosaicModel::HANDLING_FEE; 

        foreach ($items as $item) {
            $pavage = is_object($item) ? $item->paving : $item['paving'];
            
            $price = $mosaicModel->calculatePriceFromContent($pavage);
            $pieces = $mosaicModel->countPiecesFromContent($pavage);
            if (is_object($item)) { 
                $item->price = $price; 
                $item->pieces = $pieces; 
            } else { 
                $item['price'] = $price; 
                $item['pieces'] = $pieces; 
            }
            $totalHandling += $handlingUnit;
            $itemsTotalTTC += $price; 
        }

        $deliveryTTC = \App\Models\MosaicModel::DELIVERY_FEE;
        $totalTTC = $itemsTotalTTC + $deliveryTTC;
        $tvaRate = 0.20;
        $coeff = 1 + $tvaRate;
        $itemsHT = $itemsTotalTTC / $coeff;    
        $deliveryHT = $deliveryTTC / $coeff;      
        $totalHT = $totalTTC / $coeff;        
        $totalTVA = $totalTTC - $totalHT;

        $this->render('invoice_views', [
            't' => $this->translations, 
            'order' => $orderDetails,   
            'items' => $items,
            'totalHandling' => $totalHandling,
            'handlingUnit' => $handlingUnit,
            'itemsTotalTTC' => $itemsTotalTTC,
            'itemsHT' => $itemsHT,
            'deliveryTTC' => $deliveryTTC,
            'deliveryHT' => $deliveryHT,
            'totalHT' => $totalHT,
            'totalTVA' => $totalTVA,
            'totalTTC' => $totalTTC,
            'css' => 'invoice_views.css'
        ]);
    }

    /**
     * Sends the order invoice via email to the client.
     *
     * @param string $email
     * @param array $order
     * @return void
     */
    private function sendInvoiceEmail($email, $order) {
        $mail = new PHPMailer(true);
        $mosaicModel = new MosaicModel();
        $items = $mosaicModel->getMosaicsByOrderId($order['id_Order']);
        $handlingUnit = \App\Models\MosaicModel::HANDLING_FEE;
        $rowsHtml = '';
        foreach ($items as $item) {
            $pavage = is_object($item) ? $item->paving : $item['paving'];
            $price = $mosaicModel->calculatePriceFromContent($item->paving);

            $rowsHtml .= '<tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                    Mosaïque Briques®<br>
                    <small style="color:#666; font-size: 11px;">Dont '.$handlingUnit.'€ préparation inclus</small>
                </td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">1</td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">'.number_format($price, 2).' €</td>
            </tr>';
        }
        $delivery = \App\Models\MosaicModel::DELIVERY_FEE;
        $rowsHtml .= '<tr style="background-color: #fdfdfd;"><td colspan="2" style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right; color: #555;">Livraison</td><td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">'.number_format($delivery, 2).' €</td></tr>';
        try {
            $mail->isSMTP();
            $mail->Host       = $_ENV['MAILJET_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MAILJET_USERNAME'];
            $mail->Password   = $_ENV['MAILJET_PASSWORD'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $_ENV['MAILJET_PORT'];
            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $invoiceNum = $order['invoice_number'] ?? $order['id_Order'];
            $mail->Subject = "Votre facture LegoFactory - Commande #$invoiceNum";
            $total = number_format($order['total_amount'] ?? 0, 2);
            $mail->Body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; color: #333;'><h1 style='color: #006CB7;'>Merci pour votre commande !</h1><p>Voici le récapitulatif de votre commande <strong>#$invoiceNum</strong>.</p><table style='width: 100%; border-collapse: collapse; margin-top: 20px;'><thead><tr style='background-color: #f8f9fa;'><th style='padding: 10px; text-align: left;'>Article</th><th style='padding: 10px; text-align: right;'>Qté</th><th style='padding: 10px; text-align: right;'>Prix</th></tr></thead><tbody>$rowsHtml</tbody><tfoot><tr><td colspan='2' style='padding: 10px; text-align: right; font-weight: bold;'>TOTAL</td><td style='padding: 10px; text-align: right; font-weight: bold; color: #D92328;'>$total €</td></tr></tfoot></table></div>";
            $mail->send();
        } catch (Exception $e) { 
            error_log("Mailer Error: " . $mail->ErrorInfo); 
        }
    }

    /**
     * Retrieves a new oauth2 access token from paypal.
     *
     * @return string|null access token
     */
    private function getPayPalAccessToken() {
        $clientId = $_ENV['PAYPAL_ID'];
        $secret = $_ENV['PAYPAL_KEY'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->paypalBaseUrl . '/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $clientId . ":" . $secret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        $result = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($result);
        return $json->access_token ?? null;
    }

    /**
     * Helper to make API calls to PayPal.
     *
     * @param string $endpoint
     * @param mixed $postData
     * @param string $token
     * @return mixed
     */
    private function callPayPalApi($endpoint, $postData, $token) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->paypalBaseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: Bearer " . $token]);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result);
    }
}