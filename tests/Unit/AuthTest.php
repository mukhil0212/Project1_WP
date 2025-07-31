<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Game\Auth;
use Game\Database;

/**
 * Comprehensive unit tests for Auth class
 */
class AuthTest extends TestCase
{
    private $auth;
    private $db;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create in-memory database for testing
        $this->db = new \PDO('sqlite::memory:');
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        // Create users table
        $this->db->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(50) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Create remember_tokens table
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
        
        // Mock Database singleton
        $reflection = new \ReflectionClass(Database::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
        
        $mockDatabase = $this->createMock(Database::class);
        $mockDatabase->method('getConnection')->willReturn($this->db);
        $instance->setValue(null, $mockDatabase);
        
        $this->auth = new Auth();
    }

    protected function tearDown(): void
    {
        // Reset Database singleton
        $reflection = new \ReflectionClass(Database::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
        
        parent::tearDown();
    }

    public function testSuccessfulRegistration(): void
    {
        $result = $this->auth->register('testuser', 'password123');
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertIsInt($result['user_id']);
    }

    public function testRegistrationWithShortUsername(): void
    {
        $result = $this->auth->register('ab', 'password123');
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Username must be 3-50 characters', $result['error']);
    }

    public function testRegistrationWithLongUsername(): void
    {
        $longUsername = str_repeat('a', 51);
        $result = $this->auth->register($longUsername, 'password123');
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Username must be 3-50 characters', $result['error']);
    }

    public function testRegistrationWithShortPassword(): void
    {
        $result = $this->auth->register('testuser', '12345');
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Password must be at least 6 characters', $result['error']);
    }

    public function testRegistrationWithDuplicateUsername(): void
    {
        // First registration
        $this->auth->register('testuser', 'password123');
        
        // Second registration with same username
        $result = $this->auth->register('testuser', 'password456');
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Username already exists', $result['error']);
    }

    public function testSuccessfulLogin(): void
    {
        // Register user first
        $this->auth->register('testuser', 'password123');
        
        // Start session for login test
        if (!isset($_SESSION)) {
            session_start();
        }
        
        $result = $this->auth->login('testuser', 'password123');
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals('testuser', $result['user']['username']);
    }

    public function testLoginWithInvalidUsername(): void
    {
        $result = $this->auth->login('nonexistent', 'password123');
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid username or password', $result['error']);
    }

    public function testLoginWithInvalidPassword(): void
    {
        // Register user first
        $this->auth->register('testuser', 'password123');
        
        $result = $this->auth->login('testuser', 'wrongpassword');
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid username or password', $result['error']);
    }

    public function testPasswordHashing(): void
    {
        $this->auth->register('testuser', 'password123');
        
        // Check that password is hashed in database
        $stmt = $this->db->prepare('SELECT password_hash FROM users WHERE username = ?');
        $stmt->execute(['testuser']);
        $user = $stmt->fetch();
        
        $this->assertNotEquals('password123', $user['password_hash']);
        $this->assertTrue(password_verify('password123', $user['password_hash']));
    }

    public function testIsLoggedInWithoutSession(): void
    {
        $this->assertFalse($this->auth->isLoggedIn());
    }

    public function testIsLoggedInWithSession(): void
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'testuser';
        
        $this->assertTrue($this->auth->isLoggedIn());
    }

    public function testLogout(): void
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'testuser';
        
        $this->auth->logout();
        
        $this->assertArrayNotHasKey('user_id', $_SESSION);
        $this->assertArrayNotHasKey('username', $_SESSION);
    }

    public function testGetCurrentUserIdWithSession(): void
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        $_SESSION['user_id'] = 123;
        
        $this->assertEquals(123, $this->auth->getCurrentUserId());
    }

    public function testGetCurrentUserIdWithoutSession(): void
    {
        $this->assertNull($this->auth->getCurrentUserId());
    }
}
