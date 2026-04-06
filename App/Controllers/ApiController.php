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
}