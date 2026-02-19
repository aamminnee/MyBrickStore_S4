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
            $phone = $billingInfo['phone'];
            $addressLine = $billingInfo['address_line'];
            $zipCode = $billingInfo['zip_code'];
            $city = $billingInfo['city'];
            $fullAddress = $billingInfo['full_address'];

            $stmtCheck = $db->prepare("SELECT id_SaveCustomer FROM SaveCustomer WHERE id_Customer = ?");
            $stmtCheck->execute([$userId]);
            $idSaveCustomer = $stmtCheck->fetchColumn();

            if ($idSaveCustomer) {
                $sqlUpdate = "UPDATE SaveCustomer SET first_name = ?, last_name = ?, phone = ?, address_line = ?, zip_code = ?, city = ? WHERE id_Customer = ?";
                $stmtUpdate = $db->prepare($sqlUpdate);
                $stmtUpdate->execute([$firstName, $lastName, $phone, $addressLine, $zipCode, $city, $userId]);
            } else {
                $sqlInsert = "INSERT INTO SaveCustomer (id_Customer, first_name, last_name, phone, address_line, zip_code, city) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmtInsert = $db->prepare($sqlInsert);
                $stmtInsert->execute([$userId, $firstName, $lastName, $phone, $addressLine, $zipCode, $city]);
                $idSaveCustomer = $db->lastInsertId();
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
            

            if (!$idImage) {
                $idImage = null;
            }

            $sqlOrder = "INSERT INTO CustomerOrder (order_date, status, total_amount, id_Customer, id_Image, shipping_last_name, shipping_first_name, shipping_full_address) 
                        VALUES (NOW(), 'Payée', ?, ?, ?, ?, ?, ?)";
            $stmtOrder = $db->prepare($sqlOrder);
            $stmtOrder->execute([$amount, $userId, $idImage, $lastName, $firstName, $fullAddress]);
            $orderId = $db->lastInsertId();
            
            $invoiceNumber = 'FAC-' . date('Ymd') . '-' . $orderId;

            $sqlInvoice = "INSERT INTO Invoice (invoice_number, issue_date, payment_date, total_amount, id_Order, order_date, order_status, id_Bank_Details, billing_last_name, billing_first_name, billing_email, billing_phone, billing_full_address) 
                        VALUES (?, NOW(), NOW(), ?, ?, NOW(), 'Payée', ?, ?, ?, ?, ?, ?)";
            $stmtInvoice = $db->prepare($sqlInvoice);
            $stmtInvoice->execute([
                $invoiceNumber, $amount, $orderId, $idBankDetails, 
                $lastName, $firstName, $email, $phone, $fullAddress
            ]);

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
                    id_Order as id, 
                    order_date as date, 
                    total_amount as amount, 
                    status,
                    CONCAT(shipping_first_name, ' ', shipping_last_name) as user
                FROM CustomerOrder
                ORDER BY order_date DESC 
                LIMIT $limit";
        
        return \App\Core\Db::getInstance()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
}