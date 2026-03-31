<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDOException;

/**
 * class usersmodel
 * manages user accounts data layer.
 * handles operations across 'user' (credentials) and 'savecustomer' (profile) tables.
 *
 * @package App\Models
 */
class UsersModel extends Model {

    /**
     * @var string the database table associated with the model
     */
    protected $table = 'User';

    /**
     * retrieves full user profile by internal id.
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
                    u.totp_secret as totp_secret,
                    u.role,
                    u.email,
                    u.avatar,
                    u.loyalty_id,
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
     * retrieves user data by email for authentication.
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
                    totp_secret as totp_secret,
                    role,
                    email,
                    avatar,
                    loyalty_id
                FROM User 
                WHERE email = ?";
        
        return $this->requete($sql, [$email])->fetch();
    }

    /**
     * fetches only the email address for a specific user.
     *
     * @param int $id_user
     * @return object|false
     */
    public function getEmailById($id_user) {
        $sql = "SELECT email FROM User WHERE id_Customer = ?";
        return $this->requete($sql, [$id_user])->fetch();
    }

    /**
     * checks the account status (e.g., active, banned, pending).
     *
     * @param int $id_user
     * @return object|false
     */
    public function getStatusById($id_user) {
        return $this->requete("SELECT status as etat FROM User WHERE id_Customer = ?", [$id_user])->fetch();
    }
    
    /**
     * retrieves the 2fa mode setting for the user.
     *
     * @param int $id_user
     * @return string|null '2fa' or null
     */
    public function getModeById($id_user) {
        $result = $this->requete("SELECT two_factor_method as mode FROM User WHERE id_Customer = ?", [$id_user])->fetch();
        return is_object($result) ? $result->mode : ($result['mode'] ?? null);
    }

    /**
     * updates the 2fa preference for a user.
     *
     * @param int $id_user
     * @param string|null $mode '2fa' to enable, null to disable
     * @return mixed
     */
    public function setModeById($id_user, $mode) {
        return $this->requete("UPDATE User SET two_factor_method = ? WHERE id_Customer = ?", [$mode, $id_user]);
    }

    /**
     * registers a new user by creating records in both required tables.
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
            // catch duplicate entry error
            if ($e->getCode() == '23000') { 
                return "duplicate";
            }
            error_log("Erreur SQL lors de l'inscription : " . $e->getMessage());
            return false;
        }
    }

    /**
     * activates a user account after successful email verification.
     *
     * @param int $id_user
     * @return mixed
     */
    public function activateUser($id_user) {
        return $this->requete("UPDATE User SET status = 'valide' WHERE id_Customer = ?", [$id_user]);
    }

    /**
     * validates password complexity and history requirements.
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

        // check if new password is the same as the old one
        if ($currentHash && password_verify($plainPassword, $currentHash)) {
            return "Le nouveau mot de passe doit être différent de l'ancien.";
        }
        return true;
    }

    /**
     * updates the user's password with a new hash.
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
     * counts total registered standard users for admin statistics.
     *
     * @return int
     */
    public function countUsers() {
        $sql = "SELECT COUNT(*) as total FROM User WHERE role = 'user'";
        $res = Db::getInstance()->query($sql)->fetch();
        return $res->total ?? 0;
    }

    /**
     * updates user profile information in both user and savecustomer tables.
     *
     * @param int $id_user
     * @param array $data
     * @return bool|string true on success, error message on failure
     */
    public function updateUserProfile($id_user, $data) {
        $db = Db::getInstance();
        
        try {
            $db->beginTransaction();

            if (!empty($data['email'])) {
                $stmtCheckEmail = $db->prepare("SELECT id_Customer FROM User WHERE email = ? AND id_Customer != ?");
                $stmtCheckEmail->execute([$data['email'], $id_user]);
                if ($stmtCheckEmail->fetchColumn()) {
                    throw new \Exception("Cet email est déjà utilisé par un autre compte.");
                }

                $stmtEmail = $db->prepare("UPDATE User SET email = ? WHERE id_Customer = ?");
                $stmtEmail->execute([$data['email'], $id_user]);
            }

            $stmtCheckProfile = $db->prepare("SELECT id_SaveCustomer FROM SaveCustomer WHERE id_Customer = ?");
            $stmtCheckProfile->execute([$id_user]);
            $profileExists = $stmtCheckProfile->fetchColumn();

            if ($profileExists) {
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

    /**
     * update the two-factor authentication method for a user.
     *
     * @param int $userId the id of the user.
     * @param string $type the 2fa type ('email', 'app', 'none').
     * @return bool returns true on success, false on failure.
     */
    public function update2FAType(int $userId, string $type): bool {
        $mode = ($type === 'none') ? null : $type;

        $sql = "UPDATE User SET two_factor_method = ? WHERE id_Customer = ?";
        
        $this->requete($sql, [$mode, $userId]);
        
        return true;
    }

    /**
     * stores the generated google authenticator secret for a user.
     *
     * @param int $userId
     * @param string $secret
     * @return mixed
     */
    public function updateTotpSecret($userId, $secret) {
        $sql = "UPDATE User SET totp_secret = ? WHERE id_Customer = ?";
        return $this->requete($sql, [$secret, $userId]);
    }

    /**
     * verifies a totp code against a secret key mathematically.
     *
     * @param string $secret the user's base32 secret
     * @param string $code the 6 digit code entered
     * @return bool true if valid, false otherwise
     */
    public function verifyTOTP($secret, $code) {
        if (empty($secret) || strlen($code) != 6) return false;
        
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper($secret);
        $binary = '';
        
        for ($i = 0; $i < strlen($secret); $i++) {
            $char = $secret[$i];
            $val = strpos($base32chars, $char);
            if ($val === false) return false;
            $binary .= str_pad(base_convert((string)$val, 10, 2), 5, '0', STR_PAD_LEFT);
        }
        
        $binaryKey = '';
        foreach (str_split($binary, 8) as $chunk) {
            $binaryKey .= chr(bindec(str_pad($chunk, 8, '0', STR_PAD_RIGHT)));
        }
        
        $timestamp = floor(time() / 30);
        
        for ($i = -1; $i <= 1; $i++) {
            $time = pack('N*', 0) . pack('N*', $timestamp + $i);
            
            $hash = hash_hmac('sha1', $time, $binaryKey, true);
            
            $offset = ord(substr($hash, -1)) & 0x0F;
            
            $totp = (
                ((ord($hash[$offset+0]) & 0x7F) << 24) |
                ((ord($hash[$offset+1]) & 0xFF) << 16) |
                ((ord($hash[$offset+2]) & 0xFF) << 8) |
                (ord($hash[$offset+3]) & 0xFF)
            ) % 1000000;
            
            if (str_pad((string)$totp, 6, '0', STR_PAD_LEFT) === $code) {
                return true;
            }
        }
        return false;
    }

    /**
     * saves the image's binary data directly to the database.
     *
     * @param int $userId
     * @param string $binaryData raw content of the image file
     * @return bool
     */
    public function updateAvatarBlob($userId, $binaryData) {
        $sql = "UPDATE User SET avatar = ? WHERE id_Customer = ?";
        return $this->requete($sql, [$binaryData, $userId]);
    }

    /**
     * generate a unique loyalty id for a new user.
     *
     * @return string
     */
    public function generateLoyaltyId() {
        return bin2hex(random_bytes(16));
    }

    /**
     * link a loyalty id to an existing user account.
     *
     * @param int $userId
     * @param string $loyaltyId
     * @return mixed
     */
    public function setLoyaltyId($userId, $loyaltyId) {
        // use id_customer as per the database schema
        return $this->requete("UPDATE {$this->table} SET loyalty_id = ? WHERE id_Customer = ?", [$loyaltyId, $userId]);
    }
}