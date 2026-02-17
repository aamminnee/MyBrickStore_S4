<?php
namespace App\Core;

/**
 * Class Model
 *
 ** Base Model Class providing generic database interaction methods.
 ** Implements common CRUD operations using the Db Singleton.
 *
 * @package App\Core
 */
class Model {

    /** @var string The database table associated with the model. */
    protected $table;

    /** @var string The database */
    private $db;

    /**
     * Retrieves all records from the associated table.
     *
     * @return array|false List of records.
     */
    public function findAll() {
        $query = $this->requete('SELECT * FROM ' . $this->table);
        return $query->fetchAll();
    }

    /**
     * Finds a specific record by its ID.
     *
     * @param int $id The record identifier.
     * @return mixed The record object or false if not found.
     */
    public function find(int $id) {
        return $this->requete("SELECT * FROM {$this->table} WHERE id = ?", [$id])->fetch();
    }

    /**
     * Executes a SQL query (prepared or direct).
     *
     * @param string $sql The SQL query string.
     * @param array|null $attributs Optional parameters for prepared statements.
     * @return \PDOStatement|false The result statement.
     */
    public function requete(string $sql, ?array $attributs = null) {
        $this->db = Db::getInstance();

        if ($attributs !== null) {
            $query = $this->db->prepare($sql);
            $query->execute($attributs);
            return $query;
        } else {
            return $this->db->query($sql);
        }
    }
}