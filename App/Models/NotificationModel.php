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
     * @return bool true if inactive for more than 7 days
     */
    public function verifierInactiviteFidelite($idUtilisateur) {
        $db = Db::getInstance();
        
        // on recupere la date de la derniere activite depuis la table user
        $sql = "SELECT last_activity_date FROM User WHERE id_Customer = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idUtilisateur]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['last_activity_date']) {
            $dateDerniereActivite = strtotime($user['last_activity_date']);
            $maintenant = time();
            
            // calcul de la difference en jours (86400 secondes = 1 jour)
            $joursInactivite = floor(($maintenant - $dateDerniereActivite) / 86400);

            // si l'utilisateur est inactif depuis 7 jours ou plus, il est eligible
            if ($joursInactivite >= 7) {
                return true;
            }
        }

        return false;
    }

    /**
     * updates the last activity date for a user.
     * @param int $idUtilisateur the user id
     * @return void
     */
    public function actualiserActivite($idUtilisateur) {
        $db = Db::getInstance();
        $sql = "UPDATE User SET last_activity_date = NOW() WHERE id_Customer = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idUtilisateur]);
    }
}