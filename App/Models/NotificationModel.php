<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDO;

/**
 * class notificationmodel
 * manages user notifications for order updates and loyalty.
 * @package App\Models
 */
class NotificationModel extends Model {

    /** @var string the database table associated with the model. */
    protected $table = 'Notification';

    /**
     * adds a new notification for a specific user.
     * @param int $idClient the user id
     * @param string $titre the notification title
     * @param string $message the notification message
     * @return bool true if added successfully
     */
    public function ajouterNotification($idClient, $titre, $message) {
        $db = Db::getInstance();
        $sql = "INSERT INTO Notification (id_Customer, title, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$idClient, $titre, $message]);
    }

    /**
     * retrieves unread notifications for a user.
     * @param int $idClient the user id
     * @return array list of unread notifications
     */
    public function getNotificationsNonLues($idClient) {
        $db = Db::getInstance();
        $sql = "SELECT id_Notification as id, title, message FROM Notification WHERE id_Customer = ? AND is_read = 0";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idClient]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * marks specific notifications as read.
     * @param array $ids array of notification ids
     * @return void
     */
    public function marquerCommeLues($ids) {
        if (empty($ids)) return;
        $db = Db::getInstance();
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "UPDATE Notification SET is_read = 1 WHERE id_Notification IN ($placeholders)";
        $stmt = $db->prepare($sql);
        $stmt->execute($ids);
    }

    /**
     * checks if the user has been inactive for a certain period.
     * @param int $idUtilisateur the user id
     * @return bool true if inactive and eligible for loyalty notification
     */
    public function verifierInactiviteFidelite($idUtilisateur) {
        // query logic to check last login or last order date goes here
        // returning true as a simulation to trigger the loyalty notification
        return true;
    }
}