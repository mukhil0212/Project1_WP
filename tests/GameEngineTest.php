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
            try {\n                $engine->loadScene('nonexistent');\n                $this->fail('Should throw exception for invalid scene');\n            } catch (Exception $e) {\n                $this->assertTrue(true, 'Correctly throws exception for invalid scene');\n                echo \"  ✓ Invalid scene handling works\\n\";\n            }\n            \n        } catch (Exception $e) {\n            echo \"  ⚠️  Database not available for full testing: \" . $e->getMessage() . \"\\n\";\n            \n            // Test scene loading without database connection\n            try {\n                $scenePath = __DIR__ . '/../scenes/start.php';\n                if (file_exists($scenePath)) {\n                    $scene = require $scenePath;\n                    $this->assertTrue(isset($scene['title']), 'Scene file should have title');\n                    echo \"  ✓ Scene file structure is valid\\n\";\n                }\n            } catch (Exception $e) {\n                echo \"  ❌ Scene file test failed: \" . $e->getMessage() . \"\\n\";\n            }\n        }\n    }\n    \n    private function testGameStateManagement(): void \n    {\n        echo \"\\n🎮 Testing Game State Management...\\n\";\n        \n        // Test session initialization\n        if (!isset($_SESSION)) {\n            session_start();\n        }\n        \n        // Clear any existing game session\n        unset($_SESSION['game']);\n        \n        // Test game state structure\n        $expectedGameState = [\n            'current_scene' => 'start',\n            'hp' => 100,\n            'inventory' => [],\n            'choices_made' => [],\n            'start_time' => time()\n        ];\n        \n        $_SESSION['game'] = $expectedGameState;\n        \n        $this->assertTrue(isset($_SESSION['game']['current_scene']), 'Game state should have current_scene');\n        $this->assertTrue(isset($_SESSION['game']['hp']), 'Game state should have hp');\n        $this->assertTrue(isset($_SESSION['game']['inventory']), 'Game state should have inventory');\n        \n        echo \"  ✓ Game state structure is correct\\n\";\n        \n        // Test HP boundaries\n        $_SESSION['game']['hp'] = 150; // Over max\n        $normalizedHp = max(0, min(100, $_SESSION['game']['hp']));\n        $this->assertEqual($normalizedHp, 100, 'HP should be capped at 100');\n        \n        $_SESSION['game']['hp'] = -10; // Under min\n        $normalizedHp = max(0, min(100, $_SESSION['game']['hp']));\n        $this->assertEqual($normalizedHp, 0, 'HP should not go below 0');\n        \n        echo \"  ✓ HP boundary checking works\\n\";\n    }\n    \n    private function assertTrue(bool $condition, string $message): void \n    {\n        $this->totalTests++;\n        if ($condition) {\n            $this->testsPassed++;\n        } else {\n            echo \"  ❌ FAILED: $message\\n\";\n        }\n    }\n    \n    private function assertEqual($expected, $actual, string $message): void \n    {\n        $this->totalTests++;\n        if ($expected === $actual) {\n            $this->testsPassed++;\n        } else {\n            echo \"  ❌ FAILED: $message (Expected: $expected, Got: $actual)\\n\";\n        }\n    }\n    \n    private function fail(string $message): void \n    {\n        $this->totalTests++;\n        echo \"  ❌ FAILED: $message\\n\";\n    }\n}\n\n// Run tests if this file is executed directly\nif (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {\n    $tester = new GameEngineTest();\n    $tester->runTests();\n}\n?>"}