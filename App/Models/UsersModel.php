<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDOException;

/**
 * Class UsersModel
 * ** Manages user accounts data layer
 ** Handles operations across 'user' (credentials) and 'savecustomer' (profile) tables
 * * @package App\Models
 */
class UsersModel extends Model {

    /** @var string The database table associated with the model. */
    protected $table = 'User';

    /**
     * Retrieves full user profile by internal id
     *
     * @param int $id_user the user identifier
     * @return object|false user data object or false if not found
     */
    public function getUserById($id_user) {
        $sql = "SELECT 
                    u.id_Customer as id_user, 
                    u.password as mdp, 
                    u.status as status, 
                    u.two_factor_method as mode,
                    u.role,
                    u.email,
                    s.first_name, 
                    s.last_name,
                    s.phone,
                    s.address_line,
                    s.zip_code,
                    s.city
                FROM User u 
                LEFT JOIN SaveCustomer s ON u.id_Customer = s.id_Customer 
                WHERE u.id_Customer = ?";
        
        return $this->requete($sql, [$id_user])->fetch();
    }

    /**
     * Retrieves user data by email for authentication
     *
     * @param string $email the login email
     * @return object|false user data
     */
    public function getUserByEmail($email){
        $sql = "SELECT 
                    id_Customer as id_user, 
                    password as mdp, 
                    status as etat,
                    two_factor_method as mode, 
                    role,
                    email 
                FROM User 
                WHERE email = ?";
        
        return $this->requete($sql, [$email])->fetch();
    }

    /**
     * Fetches only the email address for a specific user
     *
     * @param int $id_user
     * @return object|false
     */
    public function getEmailById($id_user) {
        $sql = "SELECT email FROM User WHERE id_Customer = ?";
        return $this->requete($sql, [$id_user])->fetch();
    }

    /**
     * Checks the account status (e.g., active, banned, pending)
     *
     * @param int $id_user
     * @return object|false
     */
    public function getStatusById($id_user) {
        return $this->requete("SELECT status as etat FROM User WHERE id_Customer = ?", [$id_user])->fetch();
    }
    
    /**
     * Retrieves the 2fa mode setting for the user
     *
     * @param int $id_user
     * @return string|null '2FA' or null
     */
    public function getModeById($id_user) {
        $result = $this->requete("SELECT two_factor_method as mode FROM User WHERE id_Customer = ?", [$id_user])->fetch();
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
        return $this->requete("UPDATE User SET two_factor_method = ? WHERE id_Customer = ?", [$mode, $id_user]);
    }

    /**
     * Registers a new user by creating records in both required tables
     *
     * @param string $email
     * @param string $password plain text password
     * @return bool|string true on success, "duplicate" if email exists, false on error
     */
    public function addUser($email, $password) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $db = Db::getInstance();
        
        try {
            $sql = "INSERT INTO User (email, password, status, two_factor_method, role) VALUES (?, ?, 'invalide', NULL, 'user')";
            $stmt = $db->prepare($sql);
            $stmt->execute([$email, $hashed]);
            
            return true;

        } catch (PDOException $e) {
            if ($e->getCode() == '23000') { 
                return "duplicate";
            }
            error_log("Erreur SQL lors de l'inscription : " . $e->getMessage());
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
        return $this->requete("UPDATE User SET status = 'valide' WHERE id_Customer = ?", [$id_user]);
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

        $sql = "SELECT password FROM User WHERE id_Customer = ?";
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
        $sql = "UPDATE User SET password = ? WHERE id_Customer = ?";
        $this->requete($sql, [$newHash, $userId]);
    }

    /**
     * Counts total registered standard users for admin statistics
     *
     * @return int
     */
    public function countUsers() {
        $sql = "SELECT COUNT(*) as total FROM User WHERE role = 'user'";
        $res = Db::getInstance()->query($sql)->fetch();
        return $res->total ?? 0;
    }

    /**
     * Updates user profile information in both User and SaveCustomer tables
     *
     * @param int $id_user
     * @param array $data
     * @return bool|string true on success, error message on failure
     */
    public function updateUserProfile($id_user, $data) {
        $db = Db::getInstance();
        
        try {
            $db->beginTransaction();

            // update email in user table if provided
            if (!empty($data['email'])) {
                // check for email uniqueness
                $stmtCheckEmail = $db->prepare("SELECT id_Customer FROM User WHERE email = ? AND id_Customer != ?");
                $stmtCheckEmail->execute([$data['email'], $id_user]);
                if ($stmtCheckEmail->fetchColumn()) {
                    throw new \Exception("Cet email est déjà utilisé par un autre compte.");
                }

                $stmtEmail = $db->prepare("UPDATE User SET email = ? WHERE id_Customer = ?");
                $stmtEmail->execute([$data['email'], $id_user]);
            }

            // check if savecustomer record already exists for this user
            $stmtCheckProfile = $db->prepare("SELECT id_SaveCustomer FROM SaveCustomer WHERE id_Customer = ?");
            $stmtCheckProfile->execute([$id_user]);
            $profileExists = $stmtCheckProfile->fetchColumn();

            if ($profileExists) {
                // update existing profile
                $sqlUpdate = "UPDATE SaveCustomer SET first_name = ?, last_name = ?, phone = ?, address_line = ?, zip_code = ?, city = ? WHERE id_Customer = ?";
                $stmtUpdate = $db->prepare($sqlUpdate);
                $stmtUpdate->execute([
                    $data['first_name'] ?? null,
                    $data['last_name'] ?? null,
                    $data['phone'] ?? null,
                    $data['address_line'] ?? null,
                    $data['zip_code'] ?? null,
                    $data['city'] ?? null,
                    $id_user
                ]);
            } else {
                // insert new profile record
                $sqlInsert = "INSERT INTO SaveCustomer (id_Customer, first_name, last_name, phone, address_line, zip_code, city) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmtInsert = $db->prepare($sqlInsert);
                $stmtInsert->execute([
                    $id_user,
                    $data['first_name'] ?? null,
                    $data['last_name'] ?? null,
                    $data['phone'] ?? null,
                    $data['address_line'] ?? null,
                    $data['zip_code'] ?? null,
                    $data['city'] ?? null
                ]);
            }

            $db->commit();
            return true;

        } catch (\Exception $e) {
            $db->rollBack();
            return $e->getMessage();
        }
    }
}