<?php
namespace App\Models;

use App\Core\Model;

/**
 * Class TokensModel
 * ** Manages secure tokens used for critical actions
 ** Handles lifecycle for 'validation', 'reinitialisation', and '2fa' types
 * * @package App\Models
 */
class TokensModel extends Model {

    /** @var string The database table associated with the model. */
    protected $table = 'Tokens';

    /**
     * deletes all existing tokens for a specific user and type
     * this ensures only the most recently requested token is valid
     *
     * @param int $userId
     * @param string $type
     * @return void
     */
    public function deleteUserTokens($userId, $type) {
        $sql = "DELETE FROM {$this->table} WHERE id_Customer = ? AND types = ?";
        $this->requete($sql, [$userId, $type]);
    }

    /**
     * Generates a short-lived numeric token and stores it
     *
     * @param int $user_id
     * @param string $type context (e.g. 'validation', '2fa')
     * @return string the generated 6-digit code
     */
    public function generateToken($user_id, $type) {
        // delete existing tokens for this context to ensure only the latest is valid
        $this->deleteUserTokens($user_id, $type);

        $token = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        // set expiration to 1 minute from now
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 minutes'));
        
        $sql = "INSERT INTO {$this->table} (id_Customer, token, types, expires_at) VALUES (?, ?, ?, ?)";
        $this->requete($sql, [$user_id, $token, $type, $expires_at]);
        
        return $token;
    }

    /**
     * Checks if a token exists and is still within its validity window
     *
     * @param string $token
     * @return mixed token object if valid, false otherwise
     */
    public function verifyToken($token) {
        // get current date and time
        $now = date('Y-m-d H:i:s');
        $sql = "SELECT * FROM {$this->table} WHERE token = ? AND expires_at > ?";
        
        return $this->requete($sql, [$token, $now])->fetch();
    }

    /**
     * Checks if a token exists but has explicitly expired
     *
     * @param string $token
     * @return bool true if the token is expired, false otherwise
     */
    public function isTokenExpired($token) {
        // get current date and time
        $now = date('Y-m-d H:i:s');
        // check if token exists but expiration date is in the past
        $sql = "SELECT * FROM {$this->table} WHERE token = ? AND expires_at <= ?";
        
        $result = $this->requete($sql, [$token, $now])->fetch();
        
        // return true if the token is found and expired
        return $result !== false;
    }

    /**
     * Removes a token permanently after successful use
     *
     * @param string $token
     * @return void
     */
    public function consumeToken($token) {
        $sql = "DELETE FROM {$this->table} WHERE token = ?";
        $this->requete($sql, [$token]);
    }

    /**
     * Cleans up all expired tokens from the database
     *
     * @return void
     */
    public function deleteToken() {
        // get current date and time
        $now = date('Y-m-d H:i:s');
        $this->requete("DELETE FROM {$this->table} WHERE expires_at < ?", [$now]);
    }
}