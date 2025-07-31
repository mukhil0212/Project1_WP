<?php
namespace Game;

/**
 * Database connection singleton for the RPG game
 */
class Database 
{
    private static $instance = null;
    private $pdo;
    
    private function __construct() 
    {
        $dbPath = __DIR__ . '/../data/game.db';
        
        if (!file_exists($dbPath)) {
            throw new \Exception('Database not found. Run setup.php first.');
        }
        
        $this->pdo = new \PDO("sqlite:$dbPath");
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    }
    
    /**
     * Get database instance
     */
    public static function getInstance(): self 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection(): \PDO 
    {
        return $this->pdo;
    }
}
?>