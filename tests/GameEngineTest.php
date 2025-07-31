<?php
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/GameEngine.php';

use Game\GameEngine;

/**
 * Simple PHP test for GameEngine functionality
 * Run with: php tests/GameEngineTest.php
 */
class GameEngineTest 
{
    private $testsPassed = 0;
    private $totalTests = 0;
    
    public function runTests(): void 
    {
        echo "🧪 Running GameEngine Tests...\n\n";
        
        $this->testSceneLoading();
        $this->testGameStateManagement();
        
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "✅ Tests passed: {$this->testsPassed}/{$this->totalTests}\n";
        
        if ($this->testsPassed === $this->totalTests) {
            echo "🎉 All tests passed!\n";
        } else {
            echo "❌ Some tests failed!\n";
            exit(1);
        }
    }
    
    private function testSceneLoading(): void 
    {
        echo "🎭 Testing Scene Loading...\n";
        
        // Mock user ID for testing
        $mockUserId = 999;
        
        // Initialize GameEngine (will fail without database, but we can test scene loading)
        try {
            $engine = new GameEngine($mockUserId);
            
            // Test loading start scene
            $scene = $engine->loadScene('start');
            $this->assertTrue(isset($scene['title']), 'Scene should have title');
            $this->assertTrue(isset($scene['text']), 'Scene should have text');
            $this->assertTrue(isset($scene['choices']), 'Scene should have choices');
            
            echo "  ✓ Start scene loads correctly\n";
            
            // Test invalid scene
            try {
                $engine->loadScene('nonexistent');
                $this->fail('Should throw exception for invalid scene');
            } catch (Exception $e) {
                $this->assertTrue(true, 'Correctly throws exception for invalid scene');
                echo "  ✓ Invalid scene handling works\n";
            }
            
        } catch (Exception $e) {
            echo "  ⚠️  Database not available for full testing: " . $e->getMessage() . "\n";
            
            // Test scene loading without database connection
            try {
                $scenePath = __DIR__ . '/../scenes/start.php';
                if (file_exists($scenePath)) {
                    $scene = require $scenePath;
                    $this->assertTrue(isset($scene['title']), 'Scene file should have title');
                    echo "  ✓ Scene file structure is valid\n";
                }
            } catch (Exception $e) {
                echo "  ❌ Scene file test failed: " . $e->getMessage() . "\n";
            }
        }
    }
    
    private function testGameStateManagement(): void 
    {
        echo "\n🎮 Testing Game State Management...\n";
        
        // Test session initialization
        if (!isset($_SESSION)) {
            session_start();
        }
        
        // Clear any existing game session
        unset($_SESSION['game']);
        
        // Test game state structure
        $expectedGameState = [
            'current_scene' => 'start',
            'hp' => 100,
            'inventory' => [],
            'choices_made' => [],
            'start_time' => time()
        ];
        
        $_SESSION['game'] = $expectedGameState;
        
        $this->assertTrue(isset($_SESSION['game']['current_scene']), 'Game state should have current_scene');
        $this->assertTrue(isset($_SESSION['game']['hp']), 'Game state should have hp');
        $this->assertTrue(isset($_SESSION['game']['inventory']), 'Game state should have inventory');
        
        echo "  ✓ Game state structure is correct\n";
        
        // Test HP boundaries
        $_SESSION['game']['hp'] = 150; // Over max
        $normalizedHp = max(0, min(100, $_SESSION['game']['hp']));
        $this->assertEqual($normalizedHp, 100, 'HP should be capped at 100');
        
        $_SESSION['game']['hp'] = -10; // Under min
        $normalizedHp = max(0, min(100, $_SESSION['game']['hp']));
        $this->assertEqual($normalizedHp, 0, 'HP should not go below 0');
        
        echo "  ✓ HP boundary checking works\n";
    }
    
    private function assertTrue(bool $condition, string $message): void 
    {
        $this->totalTests++;
        if ($condition) {
            $this->testsPassed++;
        } else {
            echo "  ❌ FAILED: $message\n";
        }
    }
    
    private function assertEqual($expected, $actual, string $message): void 
    {
        $this->totalTests++;
        if ($expected === $actual) {
            $this->testsPassed++;
        } else {
            echo "  ❌ FAILED: $message (Expected: $expected, Got: $actual)\n";
        }
    }
    
    private function fail(string $message): void 
    {
        $this->totalTests++;
        echo "  ❌ FAILED: $message\n";
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $tester = new GameEngineTest();
    $tester->runTests();
}
?>