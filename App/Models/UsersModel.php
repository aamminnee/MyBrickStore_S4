<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDOException;

/**
 * Class UsersModel
 * 
 ** Manages user accounts data layer
 ** Handles operations across 'customer' (credentials) and 'savecustomer' (profile) tables
 * 
 * @package App\Models
 */
class UsersModel extends Model {

    /** @var string The database table associated with the model. */
    protected $table = 'Customer';

    /**
     * Retrieves full user profile by internal id
     *
     * @param int $id_user the user identifier
     * @return object|false user data object or false if not found
     */
    public function getUserById($id_user) {
        $sql = "SELECT 
                    c.id_Customer as id_user, 
                    c.password as mdp, 
                    c.etat, 
                    c.mode,
                    c.role,
                    s.first_name as username, 
                    s.last_name, 
                    s.email 
                FROM Customer c 
                JOIN SaveCustomer s ON c.id_SaveCustomer = s.id_SaveCustomer 
                WHERE c.id_Customer = ?";
        
        return $this->requete($sql, [$id_user])->fetch();
    }

    /**
     * Retrieves user data by username for authentication
     *
     * @param string $username the login username
     * @return object|false user data
     */
    public function getUserByUsername($username){
        $sql = "SELECT 
                    c.id_Customer as id_user, 
                    c.password as mdp, 
                    c.etat,
                    c.mode, 
                    c.role,
                    s.first_name as username, 
                    s.email 
                FROM Customer c 
                JOIN SaveCustomer s ON c.id_SaveCustomer = s.id_SaveCustomer 
                WHERE s.first_name = ?";
        
        return $this->requete($sql, [$username])->fetch();
    }

    /**
     * Fetches only the email address for a specific user
     *
     * @param int $id_user
     * @return object|false
     */
    public function getEmailById($id_user) {
        $sql = "SELECT s.email 
                FROM Customer c 
                JOIN SaveCustomer s ON c.id_SaveCustomer = s.id_SaveCustomer 
                WHERE c.id_Customer = ?";
        
        return $this->requete($sql, [$id_user])->fetch();
    }

    /**
     * Checks the account status (e.g., active, banned, pending)
     *
     * @param int $id_user
     * @return object|false
     */
    public function getStatusById($id_user) {
        return $this->requete("SELECT etat FROM Customer WHERE id_Customer = ?", [$id_user])->fetch();
    }
    
    /**
     * Retrieves the 2fa mode setting for the user
     *
     * @param int $id_user
     * @return string|null '2FA' or null
     */
    public function getModeById($id_user) {
        $result = $this->requete("SELECT mode FROM Customer WHERE id_Customer = ?", [$id_user])->fetch();
        return is_object($result) ? $result->mode : ($result['mode'] ?? null);
    }

    /**
     * Updates the 2fa preference for a user
     *
     * @param int $id_user
     * @param string|null $mode '2FA' to enable, null to disable
     * @return mixed
     */
    public function setModeById($id_user, $mode) {
        return $this->requete("UPDATE Customer SET mode = ? WHERE id_Customer = ?", [$mode, $id_user]);
    }

    /**
     * Registers a new user by creating records in both required tables
     *
     * @param string $email
     * @param string $username
     * @param string $password plain text password
     * @param string $lastname
     * @return bool|string true on success, "duplicate" if email exists, false on error
     */
    public function addUser($email, $username, $password, $lastname) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $db = Db::getInstance();
        
        try {
            $db->beginTransaction();
            
            $sql1 = "INSERT INTO SaveCustomer (first_name, last_name, email) 
                     VALUES (?, ?, ?)";
            $stmt1 = $db->prepare($sql1);
            $stmt1->execute([$username, $lastname, $email]);
            
            $id_save = $db->lastInsertId();
            
            $sql2 = "INSERT INTO Customer (password, id_SaveCustomer, etat, mode, role) VALUES (?, ?, 'invalide', NULL, 'user')";
            $stmt2 = $db->prepare($sql2);
            $stmt2->execute([$hashed, $id_save]);
            
            $db->commit();
            return true;
        } catch (PDOException $e) {
            $db->rollBack();
            if ($e->getCode() == '23000') {
                return "duplicate";
            }
            return false;
        }
    }

    /**
     * Activates a user account after successful email verification
     *
     * @param int $id_user
     * @return mixed
     */
    public function activateUser($id_user) {
        return $this->requete("UPDATE Customer SET etat = 'valide' WHERE id_Customer = ?", [$id_user]);
    }

    /**
     * Validates password complexity and history requirements
     *
     * @param int $userId
     * @param string $plainPassword
     * @return bool|string true if valid, error message string otherwise
     */
    public function validateNewPassword($userId, $plainPassword) {
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{12,}$/', $plainPassword)) {
            return "Le mot de passe doit contenir 12 caractères min, majuscule, minuscule, chiffre, caractère spécial.";
        }

        $sql = "SELECT password FROM Customer WHERE id_Customer = ?";
        $stmt = $this->requete($sql, [$userId]);
        $currentHash = $stmt->fetchColumn();

        if ($currentHash && password_verify($plainPassword, $currentHash)) {
            return "Le nouveau mot de passe doit être différent de l'ancien.";
        }

        return true;
    }

    /**
     * Updates the user's password with a new hash
     *
     * @param int $userId
     * @param string $plainPassword
     * @return void
     */
    public function updatePassword($userId, $plainPassword) {
        $newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE Customer SET password = ? WHERE id_Customer = ?";
        $this->requete($sql, [$newHash, $userId]);
    }

    /**
     * Finds a user by email, used primarily for password reset requests
     *
     * @param string $email
     * @return object|false
     */
    public function getUserByEmail($email) {
        $sql = "SELECT c.id_Customer as id_user, s.email 
                FROM Customer c 
                JOIN SaveCustomer s ON c.id_SaveCustomer = s.id_SaveCustomer 
                WHERE s.email = ?";
        
        return $this->requete($sql, [$email])->fetch();
    }

    /**
     * Counts total registered standard users for admin statistics
     *
     * @return int
     */
    public function countUsers() {
        $sql = "SELECT COUNT(*) as total FROM Customer WHERE role = 'user'";
        
        $res = \App\Core\Db::getInstance()->query($sql)->fetch();
        return $res->total ?? 0;
    }
}