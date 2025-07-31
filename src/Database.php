<?php
namespace Game;

/**
 * Database Connection Singleton
 *
 * Provides a single point of access to the SQLite database connection
 * using the Singleton design pattern. This ensures only one database
 * connection exists throughout the application lifecycle.
 *
 * @package Game
 * @author Choose Your Path RPG Team
 * @version 1.0.0
 * @since 1.0.0
 */
class Database
{
    /** @var Database|null Singleton instance */
    private static $instance = null;

    /** @var \PDO Database connection object */
    private $pdo;

    /** @var string Default database path */
    private const DEFAULT_DB_PATH = __DIR__ . '/../data/game.db';

    /**
     * Private constructor to prevent direct instantiation
     *
     * Initializes the SQLite database connection with proper error handling
     * and sets default fetch mode to associative arrays.
     *
     * @throws \RuntimeException If database connection fails
     */
    private function __construct()
    {
        try {
            $dbPath = $_ENV['DB_PATH'] ?? self::DEFAULT_DB_PATH;

            // Ensure directory exists
            $dbDir = dirname($dbPath);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }

            // Check if database file exists (for production)
            if (!file_exists($dbPath) && !isset($_ENV['DB_PATH'])) {
                throw new \RuntimeException('Database not found. Run setup.php first.');
            }

            $this->pdo = new \PDO("sqlite:$dbPath");
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

            // Enable foreign key constraints
            $this->pdo->exec('PRAGMA foreign_keys = ON');

            // Set journal mode for better performance
            $this->pdo->exec('PRAGMA journal_mode = WAL');

        } catch (\PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new \RuntimeException("Unable to connect to database", 0, $e);
        }
    }

    /**
     * Get the singleton instance of the Database class
     *
     * @return Database The singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the PDO database connection
     *
     * @return \PDO The database connection object
     */
    public function getConnection(): \PDO
    {
        return $this->pdo;
    }

    /**
     * Execute a prepared statement with parameters
     *
     * @param string $query SQL query with placeholders
     * @param array $params Parameters to bind to the query
     * @return \PDOStatement The executed statement
     * @throws \PDOException If query execution fails
     */
    public function execute(string $query, array $params = []): \PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            error_log("Query execution failed: " . $e->getMessage() . " Query: " . $query);
            throw $e;
        }
    }

    /**
     * Begin a database transaction
     *
     * @return bool True on success
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit the current transaction
     *
     * @return bool True on success
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback the current transaction
     *
     * @return bool True on success
     */
    public function rollback(): bool
    {
        return $this->pdo->rollback();
    }

    /**
     * Get the last inserted row ID
     *
     * @return string The last insert ID
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Prevent cloning of the singleton instance
     *
     * @throws \RuntimeException Always throws exception
     */
    private function __clone()
    {
        throw new \RuntimeException("Cannot clone singleton instance");
    }

    /**
     * Prevent unserialization of the singleton instance
     *
     * @throws \RuntimeException Always throws exception
     */
    public function __wakeup()
    {
        throw new \RuntimeException("Cannot unserialize singleton instance");
    }

    /**
     * Clean up database connection on destruction
     */
    public function __destruct()
    {
        $this->pdo = null;
    }
}
?>