<?php
namespace Game;

/**
 * Achievement system for tracking player accomplishments
 */
class AchievementSystem 
{
    private $db;
    private $userId;
    
    // Achievement definitions
    private $achievements = [
        'first_steps' => [
            'name' => 'First Steps',
            'description' => 'Start your first adventure',
            'icon' => '👣',
            'condition' => 'start_game'
        ],
        'explorer' => [
            'name' => 'Explorer',
            'description' => 'Make 10 choices in a single game',
            'icon' => '🗺️',
            'condition' => 'choices_count',
            'threshold' => 10
        ],
        'survivor' => [
            'name' => 'Survivor',
            'description' => 'Complete a game with full health',
            'icon' => '💪',
            'condition' => 'full_health_victory'
        ],
        'collector' => [
            'name' => 'Collector',
            'description' => 'Collect 5 items in a single game',
            'icon' => '🎒',
            'condition' => 'items_count',
            'threshold' => 5
        ],
        'speed_runner' => [
            'name' => 'Speed Runner',
            'description' => 'Complete a game in under 5 minutes',
            'icon' => '⚡',
            'condition' => 'fast_completion',
            'threshold' => 300 // seconds
        ],
        'wise_one' => [
            'name' => 'The Wise One',
            'description' => 'Find the sage and accept his wisdom',
            'icon' => '🧙‍♂️',
            'condition' => 'sage_wisdom'
        ],
        'nature_friend' => [
            'name' => 'Friend of Nature',
            'description' => 'Help the wounded fox',
            'icon' => '🦊',
            'condition' => 'help_fox'
        ],
        'crystal_master' => [
            'name' => 'Crystal Master',
            'description' => 'Discover the power of the crystals',
            'icon' => '💎',
            'condition' => 'crystal_power'
        ],
        'completionist' => [
            'name' => 'Completionist',
            'description' => 'Experience all possible endings',
            'icon' => '🏆',
            'condition' => 'all_endings'
        ],
        'persistent' => [
            'name' => 'Persistent',
            'description' => 'Play 10 games',
            'icon' => '🎯',
            'condition' => 'games_played',
            'threshold' => 10
        ]
    ];
    
    public function __construct(int $userId) 
    {
        $this->db = Database::getInstance()->getConnection();
        $this->userId = $userId;
        $this->initializeAchievementTable();
    }
    
    /**
     * Initialize achievement table if it doesn't exist
     */
    private function initializeAchievementTable(): void 
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS user_achievements (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                achievement_id VARCHAR(50) NOT NULL,
                unlocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(user_id, achievement_id),
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");
    }
    
    /**
     * Check and unlock achievements based on game state
     */
    public function checkAchievements(array $gameState, string $action = '', array $context = []): array 
    {
        $newAchievements = [];
        
        foreach ($this->achievements as $id => $achievement) {
            if ($this->isAchievementUnlocked($id)) {
                continue; // Already unlocked
            }
            
            if ($this->checkAchievementCondition($achievement, $gameState, $action, $context)) {
                $this->unlockAchievement($id);
                $newAchievements[] = $achievement + ['id' => $id];
            }
        }
        
        return $newAchievements;
    }
    
    /**
     * Check if specific achievement condition is met
     */
    private function checkAchievementCondition(array $achievement, array $gameState, string $action, array $context): bool 
    {
        switch ($achievement['condition']) {
            case 'start_game':
                return $action === 'start_game';
                
            case 'choices_count':
                return count($gameState['choices_made'] ?? []) >= $achievement['threshold'];
                
            case 'full_health_victory':
                return $action === 'game_complete' && 
                       ($context['ending'] ?? '') === 'victory' && 
                       ($gameState['hp'] ?? 0) >= 100;
                
            case 'items_count':
                return count($gameState['inventory'] ?? []) >= $achievement['threshold'];
                
            case 'fast_completion':
                if ($action === 'game_complete' && isset($gameState['start_time'])) {
                    $duration = time() - $gameState['start_time'];
                    return $duration <= $achievement['threshold'];
                }
                return false;
                
            case 'sage_wisdom':
                return $action === 'choice_made' && 
                       ($context['scene'] ?? '') === 'sage_wisdom' && 
                       ($context['choice'] ?? '') === 'wisdom';
                
            case 'help_fox':
                return $action === 'choice_made' && 
                       ($context['scene'] ?? '') === 'dark_woods' && 
                       ($context['choice'] ?? '') === 'help';
                
            case 'crystal_power':
                return in_array('Crystal Power', $gameState['inventory'] ?? []) ||
                       in_array('Crystal Water', $gameState['inventory'] ?? []);
                
            case 'all_endings':
                return $this->hasExperiencedAllEndings();
                
            case 'games_played':
                return $this->getGamesPlayedCount() >= $achievement['threshold'];
                
            default:
                return false;
        }
    }
    
    /**
     * Unlock an achievement for the user
     */
    private function unlockAchievement(string $achievementId): void 
    {
        $stmt = $this->db->prepare('
            INSERT OR IGNORE INTO user_achievements (user_id, achievement_id) 
            VALUES (?, ?)
        ');
        $stmt->execute([$this->userId, $achievementId]);
    }
    
    /**
     * Check if achievement is already unlocked
     */
    private function isAchievementUnlocked(string $achievementId): bool 
    {
        $stmt = $this->db->prepare('
            SELECT 1 FROM user_achievements 
            WHERE user_id = ? AND achievement_id = ?
        ');
        $stmt->execute([$this->userId, $achievementId]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Get all unlocked achievements for user
     */
    public function getUnlockedAchievements(): array 
    {
        $stmt = $this->db->prepare('
            SELECT achievement_id, unlocked_at 
            FROM user_achievements 
            WHERE user_id = ? 
            ORDER BY unlocked_at DESC
        ');
        $stmt->execute([$this->userId]);
        $unlocked = $stmt->fetchAll();
        
        $result = [];
        foreach ($unlocked as $row) {
            $id = $row['achievement_id'];
            if (isset($this->achievements[$id])) {
                $result[] = $this->achievements[$id] + [
                    'id' => $id,
                    'unlocked_at' => $row['unlocked_at']
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * Get achievement progress statistics
     */
    public function getAchievementStats(): array 
    {
        $total = count($this->achievements);
        $unlocked = count($this->getUnlockedAchievements());
        
        return [
            'total' => $total,
            'unlocked' => $unlocked,
            'percentage' => $total > 0 ? round(($unlocked / $total) * 100, 1) : 0
        ];
    }
    
    /**
     * Check if user has experienced all endings
     */
    private function hasExperiencedAllEndings(): bool 
    {
        $stmt = $this->db->prepare('
            SELECT DISTINCT ending_reached 
            FROM leaderboard 
            WHERE username = (SELECT username FROM users WHERE id = ?)
        ');
        $stmt->execute([$this->userId]);
        $endings = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        
        $requiredEndings = ['victory', 'defeat', 'peaceful_rest'];
        return count(array_intersect($endings, $requiredEndings)) === count($requiredEndings);
    }
    
    /**
     * Get total games played by user
     */
    private function getGamesPlayedCount(): int 
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(*) 
            FROM leaderboard 
            WHERE username = (SELECT username FROM users WHERE id = ?)
        ');
        $stmt->execute([$this->userId]);
        return (int) $stmt->fetchColumn();
    }
    
    /**
     * Get all available achievements (for display)
     */
    public function getAllAchievements(): array 
    {
        $unlocked = array_column($this->getUnlockedAchievements(), 'unlocked_at', 'id');
        
        $result = [];
        foreach ($this->achievements as $id => $achievement) {
            $result[] = $achievement + [
                'id' => $id,
                'unlocked' => isset($unlocked[$id]),
                'unlocked_at' => $unlocked[$id] ?? null
            ];
        }
        
        return $result;
    }
}
