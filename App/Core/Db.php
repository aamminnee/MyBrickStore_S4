<?php
namespace App\Core;

use PDO;
use PDOException;

/**
 * Class Db
 *
 ** Database Connection Wrapper.
 ** Implements the Singleton Pattern to ensure a single active database connection per request.
 ** Extends PDO to provide direct access to database methods.
 *
 * @package App\Core
 */
class Db extends PDO {

    /** @var Db|null The single instance of the class. */
    private static $instance;

    /**
     * Private constructor to prevent direct instantiation.
     * Establishes the PDO connection using environment variables.
     */
    private function __construct() {
        $dbHost = $_ENV['DB_HOST'];
        $dbName = $_ENV['DB_NAME'];
        $dbUser = $_ENV['DB_USER'];
        $dbPass = $_ENV['DB_PASS'];

        $_dsn = 'mysql:dbname=' . $dbName . ';host=' . $dbHost;

        try {
            parent::__construct($_dsn, $dbUser, $dbPass);
            $this->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES utf8');
            $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    /**
     * Returns the single instance of the Database connection.
     *
     * @return self
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}