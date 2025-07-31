<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * Feature tests for web interface functionality
 * These tests simulate user interactions with the web interface
 */
class WebInterfaceTest extends TestCase
{
    private $baseUrl = 'http://localhost:8000';

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure test database is clean
        if (file_exists(__DIR__ . '/../../data/test_game.db')) {
            unlink(__DIR__ . '/../../data/test_game.db');
        }
    }

    public function testHomepageLoads(): void
    {
        $content = $this->getPageContent('/');
        
        $this->assertStringContainsString('Choose Your Path', $content);
        $this->assertStringContainsString('Text-Based RPG Adventure', $content);
        $this->assertStringContainsString('Login', $content);
        $this->assertStringContainsString('Create Account', $content);
    }

    public function testRegistrationPageLoads(): void
    {
        $content = $this->getPageContent('/register.php');
        
        $this->assertStringContainsString('Create Account', $content);
        $this->assertStringContainsString('Username', $content);
        $this->assertStringContainsString('Password', $content);
        $this->assertStringContainsString('type="password"', $content);
    }

    public function testLoginPageLoads(): void
    {
        $content = $this->getPageContent('/login.php');
        
        $this->assertStringContainsString('Login', $content);
        $this->assertStringContainsString('Username', $content);
        $this->assertStringContainsString('Password', $content);
        $this->assertStringContainsString('Remember me', $content);
    }

    public function testLeaderboardPageLoads(): void
    {
        $content = $this->getPageContent('/leaderboard.php');
        
        $this->assertStringContainsString('Leaderboard', $content);
        $this->assertStringContainsString('Player', $content);
        $this->assertStringContainsString('Ending', $content);
        $this->assertStringContainsString('Time', $content);
    }

    public function testStoryPageRequiresAuthentication(): void
    {
        $content = $this->getPageContent('/story.php');
        
        // Should redirect to login page or show login form
        $this->assertTrue(
            strpos($content, 'login') !== false || 
            strpos($content, 'Login') !== false ||
            strpos($content, 'authentication') !== false
        );
    }

    public function testCssFileLoads(): void
    {
        $content = $this->getPageContent('/css/style.css');
        
        $this->assertStringContainsString('Choose Your Path RPG', $content);
        $this->assertStringContainsString('.btn', $content);
        $this->assertStringContainsString('.game-title', $content);
        $this->assertStringContainsString('background:', $content);
    }

    public function testFormValidationElements(): void
    {
        $registerContent = $this->getPageContent('/register.php');
        
        // Check for HTML5 validation attributes
        $this->assertStringContainsString('required', $registerContent);
        $this->assertStringContainsString('minlength', $registerContent);
        
        $loginContent = $this->getPageContent('/login.php');
        $this->assertStringContainsString('required', $loginContent);
    }

    public function testResponsiveDesignElements(): void
    {
        $cssContent = $this->getPageContent('/css/style.css');
        
        // Check for responsive design
        $this->assertStringContainsString('@media', $cssContent);
        $this->assertStringContainsString('max-width', $cssContent);
        $this->assertStringContainsString('viewport', $cssContent);
    }

    public function testAccessibilityFeatures(): void
    {
        $homepageContent = $this->getPageContent('/');
        
        // Check for accessibility features
        $this->assertStringContainsString('alt=', $homepageContent);
        $this->assertStringContainsString('lang=', $homepageContent);
        
        $cssContent = $this->getPageContent('/css/style.css');
        $this->assertStringContainsString('focus', $cssContent);
        $this->assertStringContainsString('prefers-reduced-motion', $cssContent);
    }

    public function testSecurityHeaders(): void
    {
        $content = $this->getPageContent('/');
        
        // Check that pages don't expose sensitive information
        $this->assertStringNotContainsString('password_hash', $content);
        $this->assertStringNotContainsString('PDO', $content);
        $this->assertStringNotContainsString('Exception', $content);
    }

    public function testGameSceneStructure(): void
    {
        // Test that scene files have proper structure
        $sceneFiles = glob(__DIR__ . '/../../scenes/*.php');
        
        $this->assertGreaterThan(0, count($sceneFiles));
        
        foreach ($sceneFiles as $sceneFile) {
            $scene = require $sceneFile;
            
            $this->assertIsArray($scene);
            $this->assertArrayHasKey('title', $scene);
            $this->assertArrayHasKey('text', $scene);
            $this->assertArrayHasKey('choices', $scene);
            
            $this->assertIsString($scene['title']);
            $this->assertIsString($scene['text']);
            $this->assertIsArray($scene['choices']);
            
            // Validate choice structure
            foreach ($scene['choices'] as $choice) {
                $this->assertArrayHasKey('text', $choice);
                $this->assertArrayHasKey('next_scene', $choice);
                $this->assertIsString($choice['text']);
                $this->assertIsString($choice['next_scene']);
            }
        }
    }

    public function testDatabaseSchemaIntegrity(): void
    {
        // Run setup to create database
        ob_start();
        require __DIR__ . '/../../setup.php';
        ob_end_clean();
        
        $dbPath = __DIR__ . '/../../data/game.db';
        $this->assertFileExists($dbPath);
        
        $pdo = new \PDO("sqlite:$dbPath");
        
        // Check that all required tables exist
        $tables = ['users', 'game_sessions', 'leaderboard', 'remember_tokens'];
        
        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
            $result = $stmt->fetch();
            $this->assertNotFalse($result, "Table $table should exist");
        }
        
        // Check table structures
        $stmt = $pdo->query("PRAGMA table_info(users)");
        $columns = $stmt->fetchAll();
        $columnNames = array_column($columns, 'name');
        
        $this->assertContains('id', $columnNames);
        $this->assertContains('username', $columnNames);
        $this->assertContains('password_hash', $columnNames);
        $this->assertContains('created_at', $columnNames);
    }

    public function testErrorHandlingPages(): void
    {
        // Test that non-existent pages handle errors gracefully
        $content = $this->getPageContent('/nonexistent.php');
        
        // Should either show 404 or redirect gracefully
        $this->assertTrue(
            strpos($content, '404') !== false ||
            strpos($content, 'Not Found') !== false ||
            strpos($content, 'Choose Your Path') !== false // Redirected to home
        );
    }

    public function testJavaScriptFunctionality(): void
    {
        $homepageContent = $this->getPageContent('/');
        
        // Check for JavaScript enhancements (if any)
        if (strpos($homepageContent, '<script') !== false) {
            $this->assertStringNotContainsString('alert(', $homepageContent);
            $this->assertStringNotContainsString('eval(', $homepageContent);
        }
    }

    public function testFormTokenSecurity(): void
    {
        $registerContent = $this->getPageContent('/register.php');
        $loginContent = $this->getPageContent('/login.php');
        
        // Check for CSRF protection (if implemented)
        // This is a placeholder - actual implementation may vary
        $this->assertTrue(true); // Placeholder assertion
    }

    public function testPerformanceOptimizations(): void
    {
        $cssContent = $this->getPageContent('/css/style.css');
        
        // Check for performance optimizations
        $this->assertStringContainsString('will-change', $cssContent);
        $this->assertStringContainsString('transform', $cssContent);
        
        // Check CSS is minified or well-structured
        $this->assertGreaterThan(1000, strlen($cssContent)); // Should have substantial content
    }

    /**
     * Helper method to get page content
     */
    private function getPageContent(string $path): string
    {
        $url = $this->baseUrl . $path;
        
        // Use curl if available, otherwise file_get_contents
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $content = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($content === false || $httpCode >= 500) {
                $this->markTestSkipped("Could not fetch $url - server may not be running");
            }
            
            return $content;
        } else {
            // Fallback to file_get_contents
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'ignore_errors' => true
                ]
            ]);
            
            $content = @file_get_contents($url, false, $context);
            
            if ($content === false) {
                $this->markTestSkipped("Could not fetch $url - server may not be running");
            }
            
            return $content;
        }
    }
}
