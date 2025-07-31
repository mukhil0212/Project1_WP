<?php
namespace Game;

/**
 * Core game engine for managing game state and story progression
 */
class GameEngine 
{
    private $db;
    private $userId;
    
    public function __construct(int $userId) 
    {
        $this->db = Database::getInstance()->getConnection();
        $this->userId = $userId;
    }
    
    /**
     * Initialize new game session
     */
    public function startNewGame(): void 
    {
        // Clear existing session
        $this->clearSession();
        
        // Set initial game state
        $_SESSION['game'] = [
            'current_scene' => 'start',
            'hp' => 100,
            'inventory' => [],
            'choices_made' => [],
            'start_time' => time()
        ];
        
        // Save to database
        $this->saveGame();
    }
    
    /**
     * Load existing game from database
     */
    public function loadGame(): bool 
    {
        $stmt = $this->db->prepare('
            SELECT current_scene, hp, inventory, choices_made 
            FROM game_sessions 
            WHERE user_id = ? 
            ORDER BY updated_at DESC 
            LIMIT 1
        ');
        $stmt->execute([$this->userId]);
        $save = $stmt->fetch();
        
        if ($save) {
            $_SESSION['game'] = [
                'current_scene' => $save['current_scene'],
                'hp' => (int)$save['hp'],
                'inventory' => json_decode($save['inventory'], true),
                'choices_made' => json_decode($save['choices_made'], true),
                'start_time' => time() // Reset timer for continued games
            ];
            return true;
        }
        
        return false;
    }
    
    /**
     * Save current game state to database
     */
    public function saveGame(): void 
    {
        if (!isset($_SESSION['game'])) {
            return;
        }
        
        $game = $_SESSION['game'];
        
        $stmt = $this->db->prepare('
            INSERT OR REPLACE INTO game_sessions 
            (user_id, current_scene, hp, inventory, choices_made, updated_at) 
            VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ');
        
        $stmt->execute([
            $this->userId,
            $game['current_scene'],
            $game['hp'],
            json_encode($game['inventory']),
            json_encode($game['choices_made'])
        ]);
    }
    
    /**
     * Make a choice and progress the story
     */
    public function makeChoice(string $choice): array 
    {
        if (!isset($_SESSION['game'])) {
            throw new \Exception('No active game session');
        }
        
        $currentScene = $_SESSION['game']['current_scene'];
        $sceneData = $this->loadScene($currentScene);
        
        if (!isset($sceneData['choices'][$choice])) {
            throw new \Exception('Invalid choice');
        }
        
        $choiceData = $sceneData['choices'][$choice];
        
        // Record choice
        $_SESSION['game']['choices_made'][] = [
            'scene' => $currentScene,
            'choice' => $choice,
            'text' => $choiceData['text']
        ];
        
        // Apply effects
        if (isset($choiceData['hp_change'])) {
            $_SESSION['game']['hp'] += $choiceData['hp_change'];
            $_SESSION['game']['hp'] = max(0, min(100, $_SESSION['game']['hp']));
        }
        
        if (isset($choiceData['add_item'])) {
            $_SESSION['game']['inventory'][] = $choiceData['add_item'];
        }
        
        // Move to next scene
        $_SESSION['game']['current_scene'] = $choiceData['next_scene'];
        
        // Save progress
        $this->saveGame();
        
        return [
            'success' => true,
            'hp_change' => $choiceData['hp_change'] ?? 0,
            'item_gained' => $choiceData['add_item'] ?? null,
            'next_scene' => $choiceData['next_scene']
        ];
    }
    
    /**
     * Load scene data from file
     */
    public function loadScene(string $sceneName): array 
    {
        $scenePath = __DIR__ . "/../scenes/{$sceneName}.php";
        
        if (!file_exists($scenePath)) {
            throw new \Exception("Scene '{$sceneName}' not found");
        }
        
        return require $scenePath;
    }
    
    /**
     * Get current game state
     */
    public function getGameState(): array 
    {
        return $_SESSION['game'] ?? [];
    }
    
    /**
     * Check if game is over
     */
    public function isGameOver(): bool 
    {
        if (!isset($_SESSION['game'])) {
            return false;
        }
        
        $currentScene = $_SESSION['game']['current_scene'];
        return in_array($currentScene, ['victory', 'defeat', 'death']);
    }
    
    /**
     * End game and save to leaderboard
     */
    public function endGame(string $ending): void 
    {
        if (!isset($_SESSION['game'])) {
            return;
        }
        
        $game = $_SESSION['game'];
        $playtimeMinutes = round((time() - $game['start_time']) / 60);
        
        // Get username
        $stmt = $this->db->prepare('SELECT username FROM users WHERE id = ?');
        $stmt->execute([$this->userId]);
        $user = $stmt->fetch();
        
        // Save to leaderboard
        $stmt = $this->db->prepare('
            INSERT INTO leaderboard 
            (username, ending_reached, playtime_minutes, final_hp, choices_count) 
            VALUES (?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $user['username'],
            $ending,
            $playtimeMinutes,
            $game['hp'],
            count($game['choices_made'])
        ]);
        
        // Clear game session
        $this->clearSession();
    }
    
    /**
     * Clear game session
     */
    private function clearSession(): void 
    {
        unset($_SESSION['game']);
        
        // Delete from database
        $stmt = $this->db->prepare('DELETE FROM game_sessions WHERE user_id = ?');
        $stmt->execute([$this->userId]);
    }
}
?>