<?php
namespace Game;

/**
 * Advanced inventory system with item effects and combinations
 */
class InventorySystem 
{
    private $items = [
        'River Stone' => [
            'name' => 'River Stone',
            'description' => 'A smooth stone from the mystical river. Provides protection.',
            'type' => 'protection',
            'effect' => 'hp_boost',
            'value' => 5,
            'icon' => '🪨',
            'rarity' => 'common'
        ],
        'Fox\'s Blessing' => [
            'name' => 'Fox\'s Blessing',
            'description' => 'The grateful fox\'s magical blessing. Enhances intuition.',
            'type' => 'blessing',
            'effect' => 'choice_insight',
            'value' => 1,
            'icon' => '🦊',
            'rarity' => 'rare'
        ],
        'Crystal Water' => [
            'name' => 'Crystal Water',
            'description' => 'Magical water from the fountain. Restores health and energy.',
            'type' => 'consumable',
            'effect' => 'heal',
            'value' => 20,
            'icon' => '💧',
            'rarity' => 'uncommon'
        ],
        'Ancient Knowledge' => [
            'name' => 'Ancient Knowledge',
            'description' => 'Wisdom from the old sage. Reveals hidden paths.',
            'type' => 'knowledge',
            'effect' => 'reveal_secrets',
            'value' => 1,
            'icon' => '📜',
            'rarity' => 'legendary'
        ],
        'Sage\'s Blessing' => [
            'name' => 'Sage\'s Blessing',
            'description' => 'The sage\'s powerful blessing. Guarantees safe passage.',
            'type' => 'blessing',
            'effect' => 'safe_passage',
            'value' => 1,
            'icon' => '🧙‍♂️',
            'rarity' => 'legendary'
        ],
        'Crystal Power' => [
            'name' => 'Crystal Power',
            'description' => 'Raw crystal energy. Dramatically increases strength.',
            'type' => 'power',
            'effect' => 'power_boost',
            'value' => 25,
            'icon' => '💎',
            'rarity' => 'epic'
        ],
        'Water\'s Blessing' => [
            'name' => 'Water\'s Blessing',
            'description' => 'Blessing from the river spirit. Provides healing over time.',
            'type' => 'blessing',
            'effect' => 'regeneration',
            'value' => 3,
            'icon' => '🌊',
            'rarity' => 'rare'
        ],
        'Ancient Map' => [
            'name' => 'Ancient Map',
            'description' => 'A weathered map showing secret locations.',
            'type' => 'tool',
            'effect' => 'reveal_paths',
            'value' => 1,
            'icon' => '🗺️',
            'rarity' => 'uncommon'
        ]
    ];
    
    private $combinations = [
        'River Stone + Crystal Water' => [
            'ingredients' => ['River Stone', 'Crystal Water'],
            'result' => 'Enchanted Stone',
            'description' => 'A stone infused with crystal water magic.',
            'effect' => 'hp_boost',
            'value' => 15,
            'icon' => '✨',
            'rarity' => 'rare'
        ],
        'Ancient Knowledge + Ancient Map' => [
            'ingredients' => ['Ancient Knowledge', 'Ancient Map'],
            'result' => 'Complete Wisdom',
            'description' => 'Perfect understanding of the mystical realm.',
            'effect' => 'ultimate_insight',
            'value' => 1,
            'icon' => '🔮',
            'rarity' => 'legendary'
        ]
    ];
    
    /**
     * Get item information
     */
    public function getItem(string $itemName): ?array 
    {
        return $this->items[$itemName] ?? null;
    }
    
    /**
     * Get all available items
     */
    public function getAllItems(): array 
    {
        return $this->items;
    }
    
    /**
     * Apply item effects to game state
     */
    public function applyItemEffects(array $inventory, array &$gameState): array 
    {
        $effects = [];
        
        foreach ($inventory as $itemName) {
            $item = $this->getItem($itemName);
            if (!$item) continue;
            
            switch ($item['effect']) {
                case 'hp_boost':
                    $gameState['hp'] = min(100, $gameState['hp'] + $item['value']);
                    $effects[] = "Used {$item['name']}: +{$item['value']} HP";
                    break;
                    
                case 'heal':
                    $gameState['hp'] = min(100, $gameState['hp'] + $item['value']);
                    $effects[] = "Used {$item['name']}: Restored {$item['value']} HP";
                    break;
                    
                case 'power_boost':
                    $gameState['power_level'] = ($gameState['power_level'] ?? 0) + $item['value'];
                    $effects[] = "Used {$item['name']}: +{$item['value']} Power";
                    break;
                    
                case 'regeneration':
                    $gameState['regeneration'] = ($gameState['regeneration'] ?? 0) + $item['value'];
                    $effects[] = "Used {$item['name']}: Regeneration active";
                    break;
            }
        }
        
        return $effects;
    }
    
    /**
     * Check for possible item combinations
     */
    public function checkCombinations(array $inventory): array
    {
        $possibleCombinations = [];

        foreach ($this->combinations as $name => $combination) {
            $ingredients = $combination['ingredients'];
            $hasAllIngredients = true;

            foreach ($ingredients as $ingredient) {
                if (!in_array($ingredient, $inventory)) {
                    $hasAllIngredients = false;
                    break;
                }
            }

            if ($hasAllIngredients) {
                $possibleCombinations[] = $combination;
            }
        }

        return $possibleCombinations;
    }
    
    /**
     * Combine items in inventory
     */
    public function combineItems(array &$inventory, array $ingredients): ?array
    {
        // Check if combination exists
        $combination = null;
        foreach ($this->combinations as $name => $combData) {
            $combIngredients = $combData['ingredients'];
            if (array_diff($ingredients, $combIngredients) === [] &&
                array_diff($combIngredients, $ingredients) === []) {
                $combination = $combData;
                break;
            }
        }

        if (!$combination) {
            return null;
        }

        // Remove ingredients from inventory
        foreach ($ingredients as $ingredient) {
            $key = array_search($ingredient, $inventory);
            if ($key !== false) {
                unset($inventory[$key]);
            }
        }

        // Add result to inventory
        $inventory[] = $combination['result'];
        $inventory = array_values($inventory); // Reindex array

        // Add new item to items list if not exists
        if (!isset($this->items[$combination['result']])) {
            $this->items[$combination['result']] = $combination;
        }

        return $combination;
    }
    
    /**
     * Get inventory statistics
     */
    public function getInventoryStats(array $inventory): array 
    {
        $stats = [
            'total_items' => count($inventory),
            'by_rarity' => [],
            'by_type' => [],
            'total_value' => 0
        ];
        
        foreach ($inventory as $itemName) {
            $item = $this->getItem($itemName);
            if (!$item) continue;
            
            // Count by rarity
            $rarity = $item['rarity'];
            $stats['by_rarity'][$rarity] = ($stats['by_rarity'][$rarity] ?? 0) + 1;
            
            // Count by type
            $type = $item['type'];
            $stats['by_type'][$type] = ($stats['by_type'][$type] ?? 0) + 1;
            
            // Add to total value
            $stats['total_value'] += $item['value'];
        }
        
        return $stats;
    }
    
    /**
     * Get item rarity color for display
     */
    public function getRarityColor(string $rarity): string 
    {
        $colors = [
            'common' => '#9e9e9e',
            'uncommon' => '#4caf50',
            'rare' => '#2196f3',
            'epic' => '#9c27b0',
            'legendary' => '#ff9800'
        ];
        
        return $colors[$rarity] ?? '#9e9e9e';
    }
    
    /**
     * Use a consumable item
     */
    public function useItem(array &$inventory, string $itemName, array &$gameState): ?array 
    {
        $item = $this->getItem($itemName);
        if (!$item || $item['type'] !== 'consumable') {
            return null;
        }
        
        // Remove item from inventory
        $key = array_search($itemName, $inventory);
        if ($key === false) {
            return null;
        }
        
        unset($inventory[$key]);
        $inventory = array_values($inventory);
        
        // Apply effect
        $effects = $this->applyItemEffects([$itemName], $gameState);
        
        return [
            'item' => $item,
            'effects' => $effects
        ];
    }
    
    /**
     * Get items that provide specific effects
     */
    public function getItemsByEffect(string $effect): array 
    {
        $result = [];
        foreach ($this->items as $name => $item) {
            if ($item['effect'] === $effect) {
                $result[] = $name;
            }
        }
        return $result;
    }
}
