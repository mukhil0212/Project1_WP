<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Game\Auth;
use Game\GameEngine;
use Game\Database;

/**
 * Integration tests for complete game flow
 */
class GameFlowTest extends TestCase
{
    private $auth;
    private $gameEngine;
    private $db;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create in-memory database for testing
        $this->db = new \PDO('sqlite::memory:');
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        $this->createTables();
        
        // Mock Database singleton
        $reflection = new \ReflectionClass(Database::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
        
        $mockDatabase = $this->createMock(Database::class);
        $mockDatabase->method('getConnection')->willReturn($this->db);
        $instance->setValue(null, $mockDatabase);
        
        $this->auth = new Auth();
        
        // Start session
        if (!isset($_SESSION)) {
            session_start();
        }
    }

    protected function tearDown(): void
    {
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
        
        $this->db->exec("
            CREATE TABLE remember_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                token VARCHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");
    }

    public function testCompleteUserRegistrationAndGameFlow(): void
    {
        // 1. Register a new user
        $registerResult = $this->auth->register('testplayer', 'password123');
        $this->assertTrue($registerResult['success']);
        $userId = $registerResult['user_id'];
        
        // 2. Login the user
        $loginResult = $this->auth->login('testplayer', 'password123');
        $this->assertTrue($loginResult['success']);
        $this->assertTrue($this->auth->isLoggedIn());
        
        // 3. Create game engine for the user
        $this->gameEngine = new GameEngine($userId);
        
        // 4. Start a new game
        $this->gameEngine->startNewGame();
        $this->assertArrayHasKey('game', $_SESSION);
        $this->assertEquals('start', $_SESSION['game']['current_scene']);
        
        // 5. Make a series of choices to complete a path
        $this->gameEngine->makeChoice('center'); // Go to sunny_meadow (+5 HP)
        $this->assertEquals('sunny_meadow', $_SESSION['game']['current_scene']);
        $this->assertEquals(100, $_SESSION['game']['hp']); // Should be capped at 100

        // 6. Continue the story
        $this->gameEngine->makeChoice('fountain'); // Go to fountain_power
        $this->assertEquals('fountain_power', $_SESSION['game']['current_scene']);

        // 7. Make final choice to victory (assuming fountain_power leads to victory)
        // For now, let's just verify we can make the choice
        $this->assertTrue($_SESSION['game']['hp'] > 0);
        
        // 8. Complete the game
        $this->gameEngine->completeGame('testplayer', 'victory');
        
        // 9. Verify leaderboard entry
        $stmt = $this->db->prepare('SELECT * FROM leaderboard WHERE username = ?');
        $stmt->execute(['testplayer']);
        $leaderboardEntry = $stmt->fetch();
        
        $this->assertNotFalse($leaderboardEntry);
        $this->assertEquals('victory', $leaderboardEntry['ending_reached']);
        $this->assertEquals(100, $leaderboardEntry['final_hp']);
        $this->assertEquals(3, $leaderboardEntry['choices_count']);
    }

    public function testGameSaveAndLoadFlow(): void
    {
        // 1. Register and login user
        $registerResult = $this->auth->register('saveplayer', 'password123');
        $userId = $registerResult['user_id'];
        $this->auth->login('saveplayer', 'password123');
        
        // 2. Start game and make some progress
        $this->gameEngine = new GameEngine($userId);
        $this->gameEngine->startNewGame();
        $this->gameEngine->makeChoice('left'); // Go to dark_woods (-5 HP)
        $this->gameEngine->makeChoice('fight'); // Fight the creature
        
        $originalHp = $_SESSION['game']['hp'];
        $originalScene = $_SESSION['game']['current_scene'];
        $originalChoices = $_SESSION['game']['choices_made'];
        
        // 3. Clear session to simulate logout
        unset($_SESSION['game']);
        
        // 4. Load the saved game
        $loaded = $this->gameEngine->loadGame();
        $this->assertTrue($loaded);
        
        // 5. Verify game state was restored
        $this->assertEquals($originalHp, $_SESSION['game']['hp']);
        $this->assertEquals($originalScene, $_SESSION['game']['current_scene']);
        $this->assertEquals(count($originalChoices), count($_SESSION['game']['choices_made']));
    }

    public function testMultipleUsersGameSeparation(): void
    {
        // 1. Create two users
        $user1Result = $this->auth->register('player1', 'password123');
        $user2Result = $this->auth->register('player2', 'password123');
        
        $user1Id = $user1Result['user_id'];
        $user2Id = $user2Result['user_id'];
        
        // 2. Create game engines for both users
        $gameEngine1 = new GameEngine($user1Id);
        $gameEngine2 = new GameEngine($user2Id);
        
        // 3. Start games for both users
        $gameEngine1->startNewGame();
        $gameEngine1->makeChoice('left'); // dark_woods
        
        $gameEngine2->startNewGame();
        $gameEngine2->makeChoice('right'); // riverside
        
        // 4. Verify games are saved separately
        $stmt = $this->db->prepare('SELECT current_scene FROM game_sessions WHERE user_id = ?');
        
        $stmt->execute([$user1Id]);
        $user1Game = $stmt->fetch();
        $this->assertEquals('dark_woods', $user1Game['current_scene']);
        
        $stmt->execute([$user2Id]);
        $user2Game = $stmt->fetch();
        $this->assertEquals('riverside', $user2Game['current_scene']);
    }

    public function testGameOverScenarios(): void
    {
        // 1. Setup user and game
        $registerResult = $this->auth->register('testplayer', 'password123');
        $userId = $registerResult['user_id'];
        $this->gameEngine = new GameEngine($userId);
        
        // 2. Test HP reaching 0
        $this->gameEngine->startNewGame();
        $_SESSION['game']['hp'] = 5;
        $this->gameEngine->makeChoice('left'); // -5 HP, should reach 0
        
        $this->assertTrue($this->gameEngine->isGameOver());
        $this->assertEquals(0, $_SESSION['game']['hp']);
        
        // 3. Test reaching ending scene
        $this->gameEngine->startNewGame();
        $_SESSION['game']['current_scene'] = 'victory';
        
        $this->assertTrue($this->gameEngine->isGameOver());
    }

    public function testInventoryAndChoiceTracking(): void
    {
        // 1. Setup user and game
        $registerResult = $this->auth->register('testplayer', 'password123');
        $userId = $registerResult['user_id'];
        $this->gameEngine = new GameEngine($userId);
        
        // 2. Start game and make choice that adds item
        $this->gameEngine->startNewGame();
        $this->gameEngine->makeChoice('right'); // Adds River Stone
        
        // 3. Verify inventory
        $this->assertContains('River Stone', $_SESSION['game']['inventory']);
        
        // 4. Verify choice tracking
        $this->assertCount(1, $_SESSION['game']['choices_made']);
        $this->assertEquals('start', $_SESSION['game']['choices_made'][0]['scene']);
        $this->assertEquals('right', $_SESSION['game']['choices_made'][0]['choice']);
        
        // 5. Make another choice
        $this->gameEngine->makeChoice('explore'); // Continue story
        
        // 6. Verify multiple choices tracked
        $this->assertCount(2, $_SESSION['game']['choices_made']);
    }

    public function testAuthenticationPersistence(): void
    {
        // 1. Register user
        $registerResult = $this->auth->register('persistuser', 'password123');
        $this->assertTrue($registerResult['success']);
        
        // 2. Login with remember me
        $loginResult = $this->auth->login('persistuser', 'password123', true);
        $this->assertTrue($loginResult['success']);
        
        // 3. Verify remember token was created
        $stmt = $this->db->prepare('SELECT * FROM remember_tokens WHERE user_id = ?');
        $stmt->execute([$registerResult['user_id']]);
        $token = $stmt->fetch();
        
        $this->assertNotFalse($token);
        $this->assertNotEmpty($token['token']);
        $this->assertGreaterThan(time(), strtotime($token['expires_at']));
    }

    public function testErrorHandling(): void
    {
        // 1. Test making choice without active game
        $registerResult = $this->auth->register('erroruser', 'password123');
        $userId = $registerResult['user_id'];
        $this->gameEngine = new GameEngine($userId);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No active game session');
        $this->gameEngine->makeChoice('left');
    }

    public function testLeaderboardIntegration(): void
    {
        // 1. Complete multiple games with different outcomes
        $users = ['winner1', 'winner2', 'loser1'];
        $endings = ['victory', 'victory', 'defeat'];
        $hps = [100, 80, 0];
        
        for ($i = 0; $i < 3; $i++) {
            $registerResult = $this->auth->register($users[$i], 'password123');
            $userId = $registerResult['user_id'];
            $gameEngine = new GameEngine($userId);
            
            $gameEngine->startNewGame();
            $_SESSION['game']['start_time'] = time() - (($i + 1) * 60); // Different play times
            $_SESSION['game']['hp'] = $hps[$i];
            $_SESSION['game']['choices_made'] = array_fill(0, $i + 3, ['test' => 'choice']);
            
            $gameEngine->completeGame($users[$i], $endings[$i]);
        }
        
        // 2. Verify leaderboard entries
        $stmt = $this->db->prepare('SELECT * FROM leaderboard ORDER BY completed_at');
        $stmt->execute();
        $entries = $stmt->fetchAll();
        
        $this->assertCount(3, $entries);
        $this->assertEquals('victory', $entries[0]['ending_reached']);
        $this->assertEquals('defeat', $entries[2]['ending_reached']);
    }
}
