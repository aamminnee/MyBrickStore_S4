<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDO;

/**
 * class commandemodel
 * manages order data and status updates.
 * handles retrieval of order details, history, and invoice information, and triggers shipping notifications.
 * @package App\Models
 */
class CommandeModel extends Model {

    /** @var string the database table associated with the model. */
    protected $table = 'CustomerOrder';

    /**
     * updates the status of a specific order and triggers a notification if shipped.
     * @param int $id order identifier
     * @param string $status new status string
     * @return bool true on success
     */
    public function updateStatus($id, $status) {
        $db = Db::getInstance();
        $sql = "UPDATE " . $this->table . " SET status = ? WHERE id_Order = ?";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([$status, $id]);

        // trigger a notification if the order is shipped
        if ($result && (strtolower($status) === 'expédiée' || strtolower($status) === 'shipped')) {
            $sqlUser = "SELECT id_Customer FROM " . $this->table . " WHERE id_Order = ?";
            $stmtUser = $db->prepare($sqlUser);
            $stmtUser->execute([$id]);
            $userId = $stmtUser->fetchColumn();
            
            if ($userId) {
                // assume NotificationModel is available
                $modeleNotif = new \App\Models\NotificationModel();
                $modeleNotif->ajouterNotification($userId, "Commande Expédiée", "Bonne nouvelle ! Votre commande #$id est en route vers chez vous.");
            }
        }

        return $result;
    }
    
    /**
     * retrieves comprehensive order details including invoice and customer info.
     * @param int $orderId the order identifier
     * @return array|false associative array of order details
     */
    public function getOrderDetails($orderId) {
        $db = Db::getInstance();
        $sql = "SELECT 
                    co.id_Order, 
                    co.total_amount, 
                    co.order_date,
                    i.invoice_number, 
                    i.issue_date,
                    i.billing_full_address as adress, 
                    i.billing_first_name as first_name, 
                    i.billing_last_name as last_name, 
                    i.billing_email as email,
                    i.billing_phone as phone 
                FROM CustomerOrder co
                LEFT JOIN Invoice i ON co.id_Order = i.id_Order
                WHERE co.id_Order = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * fetches the order history for a logged-in user.
     * @param int $userId the customer identifier
     * @return array list of order objects
     */
    public function getCommandeByUserId($userId) {
        $db = Db::getInstance();
        
        $sql = "SELECT 
                    co.id_Order as id_commande,
                    co.order_date as date_commande,
                    co.total_amount as montant,
                    co.status,
                    (SELECT m.id_Mosaic FROM Mosaic m WHERE m.id_Order = co.id_Order LIMIT 1) as id_Mosaic
                FROM CustomerOrder co
                WHERE co.id_Customer = ?
                ORDER BY co.order_date DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * retrieves a single order by its identifier.
     * @param int $id the order identifier
     * @return object|false
     */
    public function getCommandeById($id) {
        $db = Db::getInstance();
        $sql = "SELECT co.*, co.id_Image as id_images, i.billing_full_address as adress 
                FROM CustomerOrder co
                LEFT JOIN Invoice i ON co.id_Order = i.id_Order
                WHERE co.id_Order = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * fetches the current status of an order.
     * @param int $id the order identifier
     * @return string status or 'inconnu'
     */
    public function getCommandeStatusById($id) {
        $db = Db::getInstance();
        $stmt = $db->prepare("SELECT status FROM CustomerOrder WHERE id_Order = ?");
        $stmt->execute([$id]);
        return $stmt->fetchColumn() ?: 'Inconnu';
    }
}