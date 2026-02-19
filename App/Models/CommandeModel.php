<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDO;

/**
 * Class CommandeModel
 * 
 ** Manages order data and status updates
 ** Handles retrieval of order details, history, and invoice information
 * 
 * @package App\Models
 */
class CommandeModel extends Model {

    /** @var string The database table associated with the model. */
    protected $table = 'CustomerOrder';

    /**
     * Updates the status of a specific order
     *
     * @param int $id order identifier
     * @param string $status new status string
     * @return bool true on success
     */
    public function updateStatus($id, $status) {
        $db = Db::getInstance();
        $sql = "UPDATE " . $this->table . " SET status = ? WHERE id_Order = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$status, $id]);
    }
    
    /**
     * Retrieves comprehensive order details including invoice and customer info
     *
     * @param int $orderId
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
     * Fetches the order history for a logged-in user
     *
     * @param int $userId
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
     * Retrieves a single order by its identifier
     *
     * @param int $id
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
     * Fetches the current status of an order
     *
     * @param int $id
     * @return string status or 'inconnu'
     */
    public function getCommandeStatusById($id) {
        $db = Db::getInstance();
        $stmt = $db->prepare("SELECT status FROM CustomerOrder WHERE id_Order = ?");
        $stmt->execute([$id]);
        return $stmt->fetchColumn() ?: 'Inconnu';
    }
}