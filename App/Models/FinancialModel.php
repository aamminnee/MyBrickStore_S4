<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDO;
use Exception;

/**
 * Class FinancialModel
 * 
 ** Manages payment processing and financial records
 ** Handles the transaction logic: saving payment details, creating orders, and generating invoices
 *
 * @package App\Models
 */
class FinancialModel extends Model {
    
    /**
    * Executes the complete checkout transaction in a safe, atomic manner
    *
    * @param int $userId the customer identifier
    * @param int $refMosaicId the mosaic being purchased
    * @param array $cardInfo payment provider details (e.g. paypal transaction id)
    * @param float $amount total cost
    * @param array $billingInfo shipping and contact details
    * @return int|string the new order id on success, or an error message on failure
    */
    public function processOrder($userId, $refMosaicId, $cardInfo, $amount, $billingInfo = []) {
        $db = Db::getInstance();
        
        try {
            $db->beginTransaction();

            $firstName = $billingInfo['first_name'];
            $lastName = $billingInfo['last_name'];
            $email = $billingInfo['email'];
            
            $sqlSave = "INSERT INTO SaveCustomer (first_name, last_name, email) VALUES (?, ?, ?)";
            $stmtSave = $db->prepare($sqlSave);
            $stmtSave->execute([$firstName, $lastName, $email]);
            $idSaveCustomer = $db->lastInsertId();

            if (!empty($billingInfo['phone'])) {
                $cleanPhone = substr(preg_replace('/[^0-9]/', '', $billingInfo['phone']), 0, 15);
                $stmtPhone = $db->prepare("UPDATE Customer SET phone = ? WHERE id_Customer = ?");
                $stmtPhone->execute([$cleanPhone, $userId]);
            }

            $paymentRef = $cardInfo['number']; 
            $brand = $cardInfo['brand'] ?? 'PayPal';
            $lastFour = 'PAYP'; 

            $sqlBank = "INSERT INTO BankDetails (id_Customer, bank_name, last_four, expire_at, payment_token, card_brand) 
                        VALUES (?, ?, ?, ?, ?, ?)";
            $stmtBank = $db->prepare($sqlBank);
            $stmtBank->execute([$userId, 'PayPal Sandbox', $lastFour, date('Y-m-d'), $paymentRef, $brand]);
            $idBankDetails = $db->lastInsertId();
            $stmtImg = $db->prepare("SELECT id_Image FROM Mosaic WHERE id_Mosaic = ?");
            $stmtImg->execute([$refMosaicId]);
            $idImage = $stmtImg->fetchColumn();
            $sqlOrder = "INSERT INTO CustomerOrder (order_date, status, total_amount, id_Customer, id_Image) 
                        VALUES (NOW(), 'Payée', ?, ?, ?)";
            $stmtOrder = $db->prepare($sqlOrder);
            $stmtOrder->execute([$amount, $userId, $idImage]);
            $orderId = $db->lastInsertId();

            $invoiceNumber = 'FAC-' . date('Ymd') . '-' . $orderId;
            $adress = $billingInfo['adress'] ?? ''; 

            $sqlInvoice = "INSERT INTO Invoice (invoice_number, issue_date, total_amount, id_Order, order_date, order_status, id_Bank_Details, id_SaveCustomer, adress) 
                        VALUES (?, NOW(), ?, ?, NOW(), 'Payée', ?, ?, ?)";
            $stmtInvoice = $db->prepare($sqlInvoice);
            $stmtInvoice->execute([$invoiceNumber, $amount, $orderId, $idBankDetails, $idSaveCustomer, $adress]);

            $db->commit();
            return $orderId;

        } catch (Exception $e) {
            $db->rollBack();
            return "Erreur SQL : " . $e->getMessage();
        }
    }

    /**
     * Calculates total revenue from valid orders for admin kpis
     *
     * @return float
     */
    public function getTotalRevenue() {
        $sql = "SELECT SUM(total_amount) as total FROM CustomerOrder WHERE status != 'Annulée'";
        $res = \App\Core\Db::getInstance()->query($sql)->fetch();
        return $res->total ?? 0;
    }

    /**
     * Counts total number of orders placed
     *
     * @return int
     */
    public function countOrders() {
        $sql = "SELECT COUNT(*) as total FROM CustomerOrder";
        $res = \App\Core\Db::getInstance()->query($sql)->fetch();
        return $res->total ?? 0;
    }

    /**
     * Retrieves the most recent orders for the admin dashboard
     *
     * @param int $limit number of orders to fetch
     * @return array
     */
    public function getLastOrders($limit = 5) {
        $sql = "SELECT 
                    o.id_Order as id, 
                    o.order_date as date, 
                    o.total_amount as amount, 
                    o.status,
                    CONCAT(c.first_name, ' ', c.last_name) as user
                FROM CustomerOrder o
                JOIN Customer cust ON o.id_Customer = cust.id_Customer
                JOIN SaveCustomer c ON cust.id_Customer = c.id_SaveCustomer 
                ORDER BY o.order_date DESC 
                LIMIT $limit";
        
        return \App\Core\Db::getInstance()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
}