<?php
namespace Game;

/**
 * Character customization and progression system
 */
class CharacterSystem 
{
    private $db;
    private $userId;
    
    private $classes = [
        'warrior' => [
            'name' => 'Warrior',
            'description' => 'Strong and brave, excels in combat situations',
            'icon' => '⚔️',
            'starting_hp' => 120,
            'hp_bonus' => 20,
            'special_ability' => 'battle_fury',
            'strengths' => ['combat', 'survival'],
            'weaknesses' => ['magic', 'stealth']
        ],
        'mage' => [
            'name' => 'Mage',
            'description' => 'Wise and magical, masters of arcane arts',
            'icon' => '🧙‍♂️',
            'starting_hp' => 80,
            'hp_bonus' => 0,
            'special_ability' => 'magic_insight',
            'strengths' => ['magic', 'knowledge'],
            'weaknesses' => ['combat', 'physical']
        ],
        'rogue' => [
            'name' => 'Rogue',
            'description' => 'Agile and cunning, master of stealth and tricks',
            'icon' => '🗡️',
            'starting_hp' => 100,
            'hp_bonus' => 10,
            'special_ability' => 'stealth_strike',
            'strengths' => ['stealth', 'agility'],
            'weaknesses' => ['magic', 'direct_combat']
        ],
        'ranger' => [
            'name' => 'Ranger',
            'description' => 'One with nature, skilled in survival and tracking',
            'icon' => '🏹',
            'starting_hp' => 110,
            'hp_bonus' => 15,
            'special_ability' => 'nature_bond',
            'strengths' => ['nature', 'survival', 'tracking'],
            'weaknesses' => ['magic', 'social']
        ]
    ];
    
    private $traits = [
        'brave' => [
            'name' => 'Brave',
            'description' => 'Fearless in the face of danger',
            'icon' => '🦁',
            'effect' => 'combat_bonus',
            'value' => 10
        ],
        'wise' => [
            'name' => 'Wise',
            'description' => 'Deep understanding of the world',
            'icon' => '🦉',
            'effect' => 'insight_bonus',
            'value' => 1
        ],
        'lucky' => [
            'name' => 'Lucky',
            'description' => 'Fortune favors you',
            'icon' => '🍀',
            'effect' => 'luck_bonus',
            'value' => 15
        ],
        'charismatic' => [
            'name' => 'Charismatic',
            'description' => 'Natural leader and persuader',
            'icon' => '✨',
            'effect' => 'social_bonus',
            'value' => 20
        ],
        'resilient' => [
            'name' => 'Resilient',
            'description' => 'Quick to recover from setbacks',
            'icon' => '💪',
            'effect' => 'hp_regen',
            'value' => 5
        ]
    ];
    
    public function __construct(int $userId) 
    {
        $this->db = Database::getInstance()->getConnection();
        $this->userId = $userId;
        $this->initializeCharacterTable();
    }
    
    /**
     * Initialize character table
     */
    private function initializeCharacterTable(): void 
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS user_characters (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                character_name VARCHAR(50) NOT NULL,
                character_class VARCHAR(20) NOT NULL,
                traits TEXT DEFAULT '[]',
                level INTEGER DEFAULT 1,
                experience INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");
    }
    
    /**
     * Create a new character
     */
    public function createCharacter(string $name, string $class, array $traits = []): array 
    {
        if (!isset($this->classes[$class])) {
            return ['success' => false, 'error' => 'Invalid character class'];
        }
        
        if (strlen($name) < 2 || strlen($name) > 50) {
            return ['success' => false, 'error' => 'Character name must be 2-50 characters'];
        }
        
        // Validate traits
        foreach ($traits as $trait) {
            if (!isset($this->traits[$trait])) {
                return ['success' => false, 'error' => "Invalid trait: $trait"];
            }
        }
        
        // Limit to 3 traits
        if (count($traits) > 3) {
            return ['success' => false, 'error' => 'Maximum 3 traits allowed'];
        }
        
        try {
            $stmt = $this->db->prepare('
                INSERT INTO user_characters (user_id, character_name, character_class, traits) 
                VALUES (?, ?, ?, ?)
            ');
            $stmt->execute([
                $this->userId,
                $name,
                $class,
                json_encode($traits)
            ]);
            
            return [
                'success' => true,
                'character_id' => $this->db->lastInsertId(),
                'character' => $this->getCharacterData($name, $class, $traits)
            ];
            
        } catch (\PDOException $e) {
            return ['success' => false, 'error' => 'Failed to create character'];
        }
    }
    
    /**
     * Get character data with calculated stats
     */
    private function getCharacterData(string $name, string $class, array $traits, int $level = 1, int $experience = 0): array 
    {
        $classData = $this->classes[$class];
        $character = [
            'name' => $name,
            'class' => $class,
            'class_data' => $classData,
            'traits' => $traits,
            'level' => $level,
            'experience' => $experience,
            'stats' => $this->calculateStats($class, $traits, $level)
        ];
        
        return $character;
    }
    
    /**
     * Calculate character stats based on class, traits, and level
     */
    private function calculateStats(string $class, array $traits, int $level): array 
    {
        $classData = $this->classes[$class];
        $baseHp = $classData['starting_hp'] + ($level - 1) * 10;
        
        $stats = [
            'hp' => $baseHp,
            'max_hp' => $baseHp,
            'combat_bonus' => 0,
            'magic_bonus' => 0,
            'stealth_bonus' => 0,
            'social_bonus' => 0,
            'luck_bonus' => 0,
            'hp_regen' => 0
        ];
        
        // Apply class bonuses
        $stats['hp'] += $classData['hp_bonus'];
        $stats['max_hp'] += $classData['hp_bonus'];
        
        // Apply trait bonuses
        foreach ($traits as $traitId) {
            if (isset($this->traits[$traitId])) {
                $trait = $this->traits[$traitId];
                $effectKey = str_replace('_bonus', '', $trait['effect']) . '_bonus';
                if (isset($stats[$effectKey])) {
                    $stats[$effectKey] += $trait['value'];
                } elseif ($trait['effect'] === 'hp_regen') {
                    $stats['hp_regen'] += $trait['value'];
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Get user's characters
     */
    public function getUserCharacters(): array 
    {
        $stmt = $this->db->prepare('
            SELECT * FROM user_characters 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ');
        $stmt->execute([$this->userId]);
        $characters = $stmt->fetchAll();
        
        $result = [];
        foreach ($characters as $char) {
            $traits = json_decode($char['traits'], true) ?: [];
            $result[] = $this->getCharacterData(
                $char['character_name'],
                $char['character_class'],
                $traits,
                $char['level'],
                $char['experience']
            ) + ['id' => $char['id']];
        }
        
        return $result;
    }
    
    /**
     * Apply character bonuses to game state
     */
    public function applyCharacterBonuses(array $character, array &$gameState): array 
    {
        $bonuses = [];
        $stats = $character['stats'];
        
        // Apply HP bonus
        if ($stats['hp'] > 100) {
            $gameState['hp'] = min($stats['max_hp'], $gameState['hp'] + ($stats['hp'] - 100));
            $bonuses[] = "Character bonus: +" . ($stats['hp'] - 100) . " HP";
        }
        
        // Apply special abilities based on class
        $classData = $character['class_data'];
        switch ($classData['special_ability']) {
            case 'battle_fury':
                $gameState['combat_advantage'] = true;
                $bonuses[] = "Battle Fury: Combat advantage active";
                break;
                
            case 'magic_insight':
                $gameState['magic_insight'] = true;
                $bonuses[] = "Magic Insight: Can sense magical auras";
                break;
                
            case 'stealth_strike':
                $gameState['stealth_bonus'] = true;
                $bonuses[] = "Stealth Strike: Can avoid some dangers";
                break;
                
            case 'nature_bond':
                $gameState['nature_friend'] = true;
                $bonuses[] = "Nature Bond: Animals are friendly";
                break;
        }
        
        return $bonuses;
    }
    
    /**
     * Get available classes
     */
    public function getClasses(): array 
    {
        return $this->classes;
    }
    
    /**
     * Get available traits
     */
    public function getTraits(): array 
    {
        return $this->traits;
    }
    
    /**
     * Award experience and handle level up
     */
    public function awardExperience(int $characterId, int $exp): array 
    {
        $stmt = $this->db->prepare('
            SELECT level, experience FROM user_characters 
            WHERE id = ? AND user_id = ?
        ');
        $stmt->execute([$characterId, $this->userId]);
        $char = $stmt->fetch();
        
        if (!$char) {
            return ['success' => false, 'error' => 'Character not found'];
        }
        
        $newExp = $char['experience'] + $exp;
        $newLevel = $char['level'];
        $leveledUp = false;
        
        // Check for level up (100 exp per level)
        while ($newExp >= $newLevel * 100) {
            $newExp -= $newLevel * 100;
            $newLevel++;
            $leveledUp = true;
        }
        
        // Update database
        $stmt = $this->db->prepare('
            UPDATE user_characters 
            SET level = ?, experience = ? 
            WHERE id = ? AND user_id = ?
        ');
        $stmt->execute([$newLevel, $newExp, $characterId, $this->userId]);
        
        return [
            'success' => true,
            'exp_gained' => $exp,
            'new_level' => $newLevel,
            'new_experience' => $newExp,
            'leveled_up' => $leveledUp
        ];
    }
}
