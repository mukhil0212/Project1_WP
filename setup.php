<?php
/**
 * Database setup script for Choose Your Path RPG
 * Run this file once to initialize the SQLite database
 */

$dbPath = __DIR__ . '/data/game.db';
$dataDir = dirname($dbPath);

// Create data directory if it doesn't exist
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

try {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Users table for authentication
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Game sessions table for save states
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS game_sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            current_scene VARCHAR(50) NOT NULL,
            hp INTEGER DEFAULT 100,
            inventory TEXT DEFAULT '[]',
            choices_made TEXT DEFAULT '[]',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");
    
    // Leaderboard table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS leaderboard (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) NOT NULL,
            ending_reached VARCHAR(100) NOT NULL,
            playtime_minutes INTEGER NOT NULL,
            final_hp INTEGER NOT NULL,
            choices_count INTEGER NOT NULL,
            completed_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Remember me tokens table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS remember_tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            token VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");
    
    echo "✅ Database setup completed successfully!\n";
    echo "📂 Database created at: $dbPath\n";
    echo "🚀 Run: php -S localhost:8000 -t public/\n";
    
} catch (PDOException $e) {
    echo "❌ Database setup failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>