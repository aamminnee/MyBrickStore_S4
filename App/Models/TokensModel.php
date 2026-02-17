<?php
namespace App\Models;

use App\Core\Model;

/**
 * Class TokensModel
 * 
 ** Manages secure tokens used for critical actions
 ** Handles lifecycle for 'validation', 'reinitialisation', and '2fa' types
 * 
 * @package App\Models
 */
class TokensModel extends Model {

    /** @var string The database table associated with the model. */
    protected $table = 'Tokens';

    /**
     * Generates a short-lived numeric token and stores it
     *
     * @param int $user_id
     * @param string $type context (e.g. 'validation', '2fa')
     * @return string the generated 6-digit code
     */
    public function generateToken($user_id, $type) {
        $token = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
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
        $now = date('Y-m-d H:i:s');
        $sql = "SELECT * FROM {$this->table} WHERE token = ? AND expires_at > ?";
        
        return $this->requete($sql, [$token, $now])->fetch();
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
        $now = date('Y-m-d H:i:s');
        $this->requete("DELETE FROM {$this->table} WHERE expires_at < ?", [$now]);
    }
}