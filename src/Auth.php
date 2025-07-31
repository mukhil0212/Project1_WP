<?php
namespace Game;

/**
 * Authentication handler for user registration and login
 */
class Auth 
{
    private $db;
    
    public function __construct() 
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Register a new user
     */
    public function register(string $username, string $password): array 
    {
        // Validate input
        if (strlen($username) < 3 || strlen($username) > 50) {
            return ['success' => false, 'error' => 'Username must be 3-50 characters'];
        }
        
        if (strlen($password) < 6) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters'];
        }
        
        try {
            // Check if username exists
            $stmt = $this->db->prepare('SELECT id FROM users WHERE username = ?');
            $stmt->execute([$username]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Username already exists'];
            }
            
            // Create user
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
            $stmt->execute([$username, $passwordHash]);
            
            return ['success' => true, 'user_id' => $this->db->lastInsertId()];
            
        } catch (\PDOException $e) {
            return ['success' => false, 'error' => 'Registration failed'];
        }
    }
    
    /**
     * Login user
     */
    public function login(string $username, string $password, bool $remember = false): array 
    {
        try {
            $stmt = $this->db->prepare('SELECT id, username, password_hash FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'error' => 'Invalid username or password'];
            }
            
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // Handle remember me
            if ($remember) {
                $this->setRememberToken($user['id']);
            }
            
            return ['success' => true, 'user' => $user];
            
        } catch (\PDOException $e) {
            return ['success' => false, 'error' => 'Login failed'];
        }
    }
    
    /**
     * Set remember me token
     */
    private function setRememberToken(int $userId): void 
    {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + (7 * 24 * 60 * 60)); // 7 days
        
        $stmt = $this->db->prepare('INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $token, $expires]);
        
        setcookie('remember_token', $token, time() + (7 * 24 * 60 * 60), '/', '', false, true);
    }
    
    /**
     * Logout user
     */
    public function logout(): void 
    {
        // Clear remember token if exists
        if (isset($_COOKIE['remember_token'])) {
            $stmt = $this->db->prepare('DELETE FROM remember_tokens WHERE token = ?');
            $stmt->execute([$_COOKIE['remember_token']]);
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // Clear session
        session_destroy();
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn(): bool 
    {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Get current user ID
     */
    public function getCurrentUserId(): ?int 
    {
        return $_SESSION['user_id'] ?? null;
    }
}
?>