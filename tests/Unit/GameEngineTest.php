<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Game\GameEngine;
use Game\Database;

/**
 * Comprehensive unit tests for GameEngine class
 */
class GameEngineTest extends TestCase
{
    private $gameEngine;
    private $db;
    private $userId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create in-memory database for testing
        $this->db = new \PDO('sqlite::memory:');
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        // Create required tables
        $this->createTables();
        
        // Mock Database singleton
        $reflection = new \ReflectionClass(Database::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
        
        $mockDatabase = $this->createMock(Database::class);
        $mockDatabase->method('getConnection')->willReturn($this->db);
        $instance->setValue(null, $mockDatabase);
        
        $this->gameEngine = new GameEngine($this->userId);
        
        // Start session for testing
        if (!isset($_SESSION)) {
            session_start();
        }
    }

    protected function tearDown(): void
    {
        // Clear session
        $_SESSION = [];
        
        // Reset Database singleton
        $reflection = new \ReflectionClass(Database::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
        
        parent::tearDown();
    }

    private function createTables(): void
    {
        $this->db->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(50) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $this->db->exec("
            CREATE TABLE game_sessions (
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
        
        $this->db->exec("
            CREATE TABLE leaderboard (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(50) NOT NULL,
                ending_reached VARCHAR(100) NOT NULL,
                playtime_minutes INTEGER NOT NULL,
                final_hp INTEGER NOT NULL,
                choices_count INTEGER NOT NULL,
                completed_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    public function testStartNewGame(): void
    {
        $this->gameEngine->startNewGame();
        
        $this->assertArrayHasKey('game', $_SESSION);
        $this->assertEquals('start', $_SESSION['game']['current_scene']);
        $this->assertEquals(100, $_SESSION['game']['hp']);
        $this->assertIsArray($_SESSION['game']['inventory']);
        $this->assertIsArray($_SESSION['game']['choices_made']);
        $this->assertIsInt($_SESSION['game']['start_time']);
    }

    public function testLoadSceneSuccess(): void
    {
        $scene = $this->gameEngine->loadScene('start');
        
        $this->assertIsArray($scene);
        $this->assertArrayHasKey('title', $scene);
        $this->assertArrayHasKey('text', $scene);
        $this->assertArrayHasKey('choices', $scene);
        $this->assertIsString($scene['title']);
        $this->assertIsString($scene['text']);
        $this->assertIsArray($scene['choices']);
    }

    public function testLoadSceneFailure(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Scene 'nonexistent' not found");
        
        $this->gameEngine->loadScene('nonexistent');
    }

    public function testMakeChoiceSuccess(): void
    {
        $this->gameEngine->startNewGame();
        
        $result = $this->gameEngine->makeChoice('left');
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('next_scene', $result);
        $this->assertEquals('dark_woods', $result['next_scene']);
        $this->assertEquals(-5, $result['hp_change']);
        $this->assertEquals(95, $_SESSION['game']['hp']);
    }

    public function testMakeChoiceWithInvalidChoice(): void
    {
        $this->gameEngine->startNewGame();
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid choice');
        
        $this->gameEngine->makeChoice('invalid');
    }

    public function testMakeChoiceWithoutActiveGame(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No active game session');
        
        $this->gameEngine->makeChoice('left');
    }

    public function testHpBoundaries(): void
    {
        $this->gameEngine->startNewGame();
        
        // Test HP doesn't go below 0
        $_SESSION['game']['hp'] = 5;
        $this->gameEngine->makeChoice('left'); // -5 HP
        $this->assertEquals(0, $_SESSION['game']['hp']);
        
        // Test HP doesn't go above 100
        $_SESSION['game']['hp'] = 95;
        $this->gameEngine->makeChoice('center'); // +5 HP
        $this->assertEquals(100, $_SESSION['game']['hp']);
    }

    public function testInventoryManagement(): void
    {
        $this->gameEngine->startNewGame();
        
        $this->gameEngine->makeChoice('right'); // Adds River Stone
        
        $this->assertContains('River Stone', $_SESSION['game']['inventory']);
    }

    public function testChoiceTracking(): void
    {
        $this->gameEngine->startNewGame();
        
        $this->gameEngine->makeChoice('left');
        
        $this->assertCount(1, $_SESSION['game']['choices_made']);
        $this->assertEquals('start', $_SESSION['game']['choices_made'][0]['scene']);
        $this->assertEquals('left', $_SESSION['game']['choices_made'][0]['choice']);
    }

    public function testSaveGame(): void
    {
        $this->gameEngine->startNewGame();
        $this->gameEngine->makeChoice('left');
        
        // Check if game was saved to database
        $stmt = $this->db->prepare('SELECT * FROM game_sessions WHERE user_id = ?');
        $stmt->execute([$this->userId]);
        $savedGame = $stmt->fetch();
        
        $this->assertNotFalse($savedGame);
        $this->assertEquals('dark_woods', $savedGame['current_scene']);
        $this->assertEquals(95, $savedGame['hp']);
    }

    public function testLoadGame(): void
    {
        // Insert a saved game
        $stmt = $this->db->prepare('
            INSERT INTO game_sessions (user_id, current_scene, hp, inventory, choices_made) 
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $this->userId,
            'sunny_meadow',
            80,
            json_encode(['Magic Sword']),
            json_encode([['scene' => 'start', 'choice' => 'center']])
        ]);
        
        $loaded = $this->gameEngine->loadGame();
        
        $this->assertTrue($loaded);
        $this->assertEquals('sunny_meadow', $_SESSION['game']['current_scene']);
        $this->assertEquals(80, $_SESSION['game']['hp']);
        $this->assertContains('Magic Sword', $_SESSION['game']['inventory']);
    }

    public function testGetCurrentScene(): void
    {
        $this->gameEngine->startNewGame();
        
        $scene = $this->gameEngine->getCurrentScene();
        
        $this->assertIsArray($scene);
        $this->assertEquals('The Enchanted Forest', $scene['title']);
    }

    public function testGetGameStats(): void
    {
        $this->gameEngine->startNewGame();
        $this->gameEngine->makeChoice('left');
        
        $stats = $this->gameEngine->getGameStats();
        
        $this->assertArrayHasKey('hp', $stats);
        $this->assertArrayHasKey('inventory', $stats);
        $this->assertArrayHasKey('choices_count', $stats);
        $this->assertEquals(95, $stats['hp']);
        $this->assertEquals(1, $stats['choices_count']);
    }

    public function testIsGameOver(): void
    {
        $this->gameEngine->startNewGame();
        
        // Test with HP > 0
        $this->assertFalse($this->gameEngine->isGameOver());
        
        // Test with HP = 0
        $_SESSION['game']['hp'] = 0;
        $this->assertTrue($this->gameEngine->isGameOver());
        
        // Test with ending scene
        $_SESSION['game']['hp'] = 100;
        $_SESSION['game']['current_scene'] = 'victory';
        $this->assertTrue($this->gameEngine->isGameOver());
    }

    public function testCompleteGame(): void
    {
        // Insert user for leaderboard
        $stmt = $this->db->prepare('INSERT INTO users (username) VALUES (?)');
        $stmt->execute(['testuser']);
        
        $this->gameEngine->startNewGame();
        $_SESSION['game']['current_scene'] = 'victory';
        $_SESSION['game']['start_time'] = time() - 300; // 5 minutes ago
        
        $this->gameEngine->completeGame('testuser', 'victory');
        
        // Check leaderboard entry
        $stmt = $this->db->prepare('SELECT * FROM leaderboard WHERE username = ?');
        $stmt->execute(['testuser']);
        $entry = $stmt->fetch();
        
        $this->assertNotFalse($entry);
        $this->assertEquals('victory', $entry['ending_reached']);
        $this->assertEquals(5, $entry['playtime_minutes']);
    }
}
