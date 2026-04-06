<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\NotificationModel;

/**
 * class apicontroller
 * handles api requests for the mobile application.
 * @package App\Controllers
 */
class ApiController extends Controller {

    /**
     * fetches unread notifications for a given user id and marks them as read.
     * @return void
     */
    public function notifications() {
        // set response type to json
        header('Content-Type: application/json');

        // retrieve user_id from the get request
        $idClient = $_GET['user_id'] ?? null;

        // return empty array if no user id provided
        if (!$idClient) {
            echo json_encode([]);
            exit;
        }

        $modeleNotif = new NotificationModel();
        // fetch unread notifications
        $notifications = $modeleNotif->getNotificationsNonLues($idClient);

        // extract ids to mark as read immediately
        $ids = array_column($notifications, 'id');
        if (!empty($ids)) {
            $modeleNotif->marquerCommeLues($ids);
        }

        // return notifications as json
        echo json_encode($notifications);
        exit;
    }

    /**
     * returns the daily image data to be pushed to the mobile app.
     * @return void
     */
    public function dailyImage() {
        // set response type to json
        header('Content-Type: application/json');

        // define the content of the daily image notification
        // this could be fetched from a database for dynamic images
        $data = [
            'title' => 'Image du jour',
            'message' => 'Découvrez notre nouvelle création en briques à prix préférentiel !',
            'url' => '/image-du-jour'
        ];

        // output data as json
        echo json_encode($data);
        exit;
    }

    /**
     * endpoint called periodically by the mobile app to check for loyalty notifications.
     * @return void
     */
    public function verifierFidelite() {
        // allowing cors if needed
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: POST");

        // get json payload from the app
        $donneesRecues = json_decode(file_get_contents("php://input"));

        // check if user id is provided in the ping request
        if (isset($donneesRecues->id_utilisateur)) {
            $modeleNotification = new NotificationModel();
            
            // check inactivity status
            $doitAfficherNotification = $modeleNotification->verifierInactiviteFidelite($donneesRecues->id_utilisateur);

            if ($doitAfficherNotification) {
                // prepare the notification payload to send back to the app
                $reponse = [
                    "afficher_notification" => true,
                    "titre" => "Vous nous manquez !",
                    "message" => "Cela fait un moment ! Venez découvrir nos nouveautés ou faire une partie."
                ];
            } else {
                // no notification needed
                $reponse = [
                    "afficher_notification" => false
                ];
            }

            // send the json response
            http_response_code(200);
            echo json_encode($reponse);

        } else {
            // return bad request if user id is missing
            http_response_code(400);
            echo json_encode(["erreur" => "id_utilisateur manquant"]);
        }
        exit;
    }

    /**
     * updates the user's last activity date when they open the app or login.
     * @return void
     */
    public function marquerPresence() {
        // allowing cors
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: POST");

        // get json payload
        $donneesRecues = json_decode(file_get_contents("php://input"));

        if (isset($donneesRecues->id_utilisateur)) {
            $modeleNotification = new NotificationModel();
            $modeleNotification->actualiserActivite($donneesRecues->id_utilisateur);
            
            http_response_code(200);
            echo json_encode(["message" => "activite mise a jour avec succes"]);
        } else {
            http_response_code(400);
            echo json_encode(["erreur" => "id_utilisateur manquant"]);
        }
        exit;
    }
}