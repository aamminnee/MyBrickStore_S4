<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDOException;

/**
 * Class UsersModel
 * Manages user accounts data layer
 * Handles operations across 'user' (credentials) and 'savecustomer' (profile) tables
 * @package App\Models
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
        // sql query to fetch user, totp data and customer profile details
        $sql = "SELECT 
                    u.id_Customer as id_user, 
                    u.password as mdp, 
                    u.status as status, 
                    u.two_factor_method as mode,
                    u.totp_secret as totp_secret,
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
        // sql query to fetch credentials and totp secret by email
        $sql = "SELECT 
                    id_Customer as id_user, 
                    password as mdp, 
                    status as etat,
                    two_factor_method as mode, 
                    totp_secret as totp_secret,
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
        // fetch simple email using user id
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
        // fetch user status state
        return $this->requete("SELECT status as etat FROM User WHERE id_Customer = ?", [$id_user])->fetch();
    }
    
    /**
     * Retrieves the 2fa mode setting for the user
     *
     * @param int $id_user
     * @return string|null '2FA' or null
     */
    public function getModeById($id_user) {
        // fetch two factor configuration mode
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
        // update 2fa mode directly
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
        // hash user password
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $db = Db::getInstance();
        
        try {
            // insert user into table
            $sql = "INSERT INTO User (email, password, status, two_factor_method, role) VALUES (?, ?, 'invalide', NULL, 'user')";
            $stmt = $db->prepare($sql);
            $stmt->execute([$email, $hashed]);
            
            return true;

        } catch (PDOException $e) {
            // check if exception is a duplicate key constraint
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
        // mark user as valid
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
        // check for regex complexity constraints
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{12,}$/', $plainPassword)) {
            return "Le mot de passe doit contenir 12 caractères min, majuscule, minuscule, chiffre, caractère spécial.";
        }

        // ensure password isn't identical to current one
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
        // hash and save new user password
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
        // sum up standard users only
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
            // start transaction for multiple queries
            $db->beginTransaction();

            // update email in user table if provided
            if (!empty($data['email'])) {
                // check for email uniqueness against other accounts
                $stmtCheckEmail = $db->prepare("SELECT id_Customer FROM User WHERE email = ? AND id_Customer != ?");
                $stmtCheckEmail->execute([$data['email'], $id_user]);
                if ($stmtCheckEmail->fetchColumn()) {
                    throw new \Exception("Cet email est déjà utilisé par un autre compte.");
                }

                // execute email update
                $stmtEmail = $db->prepare("UPDATE User SET email = ? WHERE id_Customer = ?");
                $stmtEmail->execute([$data['email'], $id_user]);
            }

            // check if savecustomer record already exists for this user
            $stmtCheckProfile = $db->prepare("SELECT id_SaveCustomer FROM SaveCustomer WHERE id_Customer = ?");
            $stmtCheckProfile->execute([$id_user]);
            $profileExists = $stmtCheckProfile->fetchColumn();

            if ($profileExists) {
                // update existing profile data
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
                // insert new profile record if missing
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

            // apply transaction
            $db->commit();
            return true;

        } catch (\Exception $e) {
            // revert in case of error
            $db->rollBack();
            return $e->getMessage();
        }
    }

    /**
     * Update the two-factor authentication method for a user.
     *
     * @param int $userId The ID of the user.
     * @param string $type The 2FA type ('email', 'app', 'none').
     * @return bool Returns true on success, false on failure.
     */
    public function update2FAType(int $userId, string $type): bool
    {
        // convert 'none' to null for database compatibility
        $mode = ($type === 'none') ? null : $type;

        // prepare update query using the proper table and fields for this database architecture
        $sql = "UPDATE User SET two_factor_method = ? WHERE id_Customer = ?";
        
        // execute the query using the parent requete method
        $this->requete($sql, [$mode, $userId]);
        
        return true;
    }

    /**
     * Stores the generated google authenticator secret for a user
     *
     * @param int $userId
     * @param string $secret
     * @return mixed
     */
    public function updateTotpSecret($userId, $secret) {
        // update the dedicated totp secret column
        $sql = "UPDATE User SET totp_secret = ? WHERE id_Customer = ?";
        return $this->requete($sql, [$secret, $userId]);
    }

    /**
     * Verifies a totp code against a secret key mathematically
     *
     * @param string $secret the user's base32 secret
     * @param string $code the 6 digit code entered
     * @return bool true if valid, false otherwise
     */
    public function verifyTOTP($secret, $code) {
        // validate basic requirements
        if (empty($secret) || strlen($code) != 6) return false;
        
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper($secret);
        $binary = '';
        
        // decode base32 to binary string
        for ($i = 0; $i < strlen($secret); $i++) {
            $char = $secret[$i];
            $val = strpos($base32chars, $char);
            if ($val === false) return false;
            $binary .= str_pad(base_convert((string)$val, 10, 2), 5, '0', STR_PAD_LEFT);
        }
        
        // pack binary string into bytes
        $binaryKey = '';
        foreach (str_split($binary, 8) as $chunk) {
            $binaryKey .= chr(bindec(str_pad($chunk, 8, '0', STR_PAD_RIGHT)));
        }
        
        // standard time step is 30 seconds
        $timestamp = floor(time() / 30);
        
        // allow 30 seconds margin (previous, current, next window)
        for ($i = -1; $i <= 1; $i++) {
            // generate 8 byte time marker
            $time = pack('N*', 0) . pack('N*', $timestamp + $i);
            
            // hmac sha1 signature
            $hash = hash_hmac('sha1', $time, $binaryKey, true);
            
            // extract dynamic offset
            $offset = ord(substr($hash, -1)) & 0x0F;
            
            // truncate down to 6 digit integer
            $totp = (
                ((ord($hash[$offset+0]) & 0x7F) << 24) |
                ((ord($hash[$offset+1]) & 0xFF) << 16) |
                ((ord($hash[$offset+2]) & 0xFF) << 8) |
                (ord($hash[$offset+3]) & 0xFF)
            ) % 1000000;
            
            // strict string comparison
            if (str_pad((string)$totp, 6, '0', STR_PAD_LEFT) === $code) {
                return true;
            }
        }
        return false;
    }
}