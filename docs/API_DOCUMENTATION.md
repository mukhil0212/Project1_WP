# Choose Your Path RPG - API Documentation

## Overview

This document provides comprehensive documentation for the Choose Your Path RPG application, including class structures, methods, and usage examples.

## Architecture

The application follows a modular architecture with clear separation of concerns:

- **Database Layer**: Singleton pattern for database connections
- **Authentication**: User registration, login, and session management
- **Game Engine**: Core game logic and state management
- **Achievement System**: Player accomplishment tracking
- **Inventory System**: Advanced item management with combinations
- **Character System**: Character customization and progression

## Core Classes

### Database Class

**Location**: `src/Database.php`

The Database class implements the Singleton pattern to ensure a single database connection throughout the application.

#### Methods

```php
public static function getInstance(): Database
```
Returns the singleton instance of the Database class.

```php
public function getConnection(): \PDO
```
Returns the PDO database connection object.

```php
public function execute(string $query, array $params = []): \PDOStatement
```
Executes a prepared statement with parameters and returns the statement object.

```php
public function beginTransaction(): bool
public function commit(): bool
public function rollback(): bool
```
Transaction management methods for database operations.

#### Usage Example

```php
$db = Database::getInstance();
$stmt = $db->execute('SELECT * FROM users WHERE id = ?', [$userId]);
$user = $stmt->fetch();
```

### Auth Class

**Location**: `src/Auth.php`

Handles user authentication, registration, and session management.

#### Methods

```php
public function register(string $username, string $password): array
```
Registers a new user account.

**Parameters**:
- `$username`: Username (3-50 characters)
- `$password`: Password (minimum 6 characters)

**Returns**: Array with success status and user data or error message.

```php
public function login(string $username, string $password, bool $remember = false): array
```
Authenticates a user and creates a session.

```php
public function logout(): void
```
Destroys the current user session.

```php
public function isLoggedIn(): bool
```
Checks if a user is currently logged in.

```php
public function getCurrentUserId(): ?int
```
Returns the current user's ID or null if not logged in.

#### Usage Example

```php
$auth = new Auth();
$result = $auth->register('player1', 'password123');

if ($result['success']) {
    $loginResult = $auth->login('player1', 'password123');
}
```

### GameEngine Class

**Location**: `src/GameEngine.php`

Core game logic including scene management, choice processing, and game state.

#### Methods

```php
public function startNewGame(): void
```
Initializes a new game session with default values.

```php
public function makeChoice(string $choice): array
```
Processes a player's choice and updates game state.

```php
public function loadScene(string $sceneName): array
```
Loads scene data from the scenes directory.

```php
public function getCurrentScene(): array
```
Returns the current scene data.

```php
public function getGameStats(): array
```
Returns current game statistics (HP, inventory, choices, etc.).

```php
public function isGameOver(): bool
```
Checks if the game has ended (HP <= 0 or ending scene reached).

```php
public function saveGame(): void
public function loadGame(): bool
```
Save and load game state to/from database.

#### Usage Example

```php
$gameEngine = new GameEngine($userId);
$gameEngine->startNewGame();

$result = $gameEngine->makeChoice('left');
if ($result['success']) {
    $currentScene = $gameEngine->getCurrentScene();
}
```

### AchievementSystem Class

**Location**: `src/AchievementSystem.php`

Tracks and manages player achievements.

#### Methods

```php
public function checkAchievements(array $gameState, string $action = '', array $context = []): array
```
Checks for newly unlocked achievements based on game state and actions.

```php
public function getUnlockedAchievements(): array
```
Returns all achievements unlocked by the current user.

```php
public function getAllAchievements(): array
```
Returns all available achievements with unlock status.

```php
public function getAchievementStats(): array
```
Returns achievement statistics (total, unlocked, percentage).

#### Achievement Types

- **first_steps**: Start your first adventure
- **explorer**: Make 10 choices in a single game
- **survivor**: Complete a game with full health
- **collector**: Collect 5 items in a single game
- **speed_runner**: Complete a game in under 5 minutes
- **wise_one**: Find the sage and accept his wisdom
- **nature_friend**: Help the wounded fox
- **crystal_master**: Discover the power of the crystals
- **completionist**: Experience all possible endings
- **persistent**: Play 10 games

#### Usage Example

```php
$achievementSystem = new AchievementSystem($userId);
$newAchievements = $achievementSystem->checkAchievements($gameState, 'choice_made', [
    'scene' => 'dark_woods',
    'choice' => 'help'
]);
```

### InventorySystem Class

**Location**: `src/InventorySystem.php`

Advanced inventory management with item effects and combinations.

#### Methods

```php
public function getItem(string $itemName): ?array
```
Returns item information by name.

```php
public function applyItemEffects(array $inventory, array &$gameState): array
```
Applies item effects to the game state.

```php
public function checkCombinations(array $inventory): array
```
Checks for possible item combinations in the inventory.

```php
public function combineItems(array &$inventory, array $ingredients): ?array
```
Combines items to create new items.

```php
public function useItem(array &$inventory, string $itemName, array &$gameState): ?array
```
Uses a consumable item and applies its effects.

#### Item Types

- **protection**: Items that provide HP bonuses
- **blessing**: Magical blessings with special effects
- **consumable**: Items that can be used once
- **knowledge**: Items that reveal secrets or paths
- **power**: Items that increase character strength
- **tool**: Utility items for navigation or discovery

#### Usage Example

```php
$inventorySystem = new InventorySystem();
$item = $inventorySystem->getItem('Crystal Water');
$effects = $inventorySystem->applyItemEffects($inventory, $gameState);
```

### CharacterSystem Class

**Location**: `src/CharacterSystem.php`

Character customization and progression system.

#### Methods

```php
public function createCharacter(string $name, string $class, array $traits = []): array
```
Creates a new character with specified class and traits.

```php
public function getUserCharacters(): array
```
Returns all characters belonging to the current user.

```php
public function applyCharacterBonuses(array $character, array &$gameState): array
```
Applies character bonuses to the game state.

```php
public function awardExperience(int $characterId, int $exp): array
```
Awards experience points and handles level progression.

#### Character Classes

- **Warrior**: Strong and brave, excels in combat (120 HP, battle fury ability)
- **Mage**: Wise and magical, masters of arcane arts (80 HP, magic insight ability)
- **Rogue**: Agile and cunning, master of stealth (100 HP, stealth strike ability)
- **Ranger**: One with nature, skilled in survival (110 HP, nature bond ability)

#### Character Traits

- **Brave**: Fearless in danger (+10 combat bonus)
- **Wise**: Deep understanding (+1 insight bonus)
- **Lucky**: Fortune favors you (+15 luck bonus)
- **Charismatic**: Natural leader (+20 social bonus)
- **Resilient**: Quick recovery (+5 HP regeneration)

#### Usage Example

```php
$characterSystem = new CharacterSystem($userId);
$result = $characterSystem->createCharacter('Aragorn', 'ranger', ['brave', 'wise']);
```

## Database Schema

### Tables

#### users
- `id`: Primary key
- `username`: Unique username (3-50 characters)
- `password_hash`: Hashed password
- `created_at`: Account creation timestamp

#### game_sessions
- `id`: Primary key
- `user_id`: Foreign key to users table
- `current_scene`: Current scene identifier
- `hp`: Player health points
- `inventory`: JSON array of items
- `choices_made`: JSON array of choice history
- `created_at`: Session creation timestamp
- `updated_at`: Last update timestamp

#### leaderboard
- `id`: Primary key
- `username`: Player username
- `ending_reached`: Game ending achieved
- `playtime_minutes`: Total play time
- `final_hp`: Final health points
- `choices_count`: Number of choices made
- `completed_at`: Completion timestamp

#### remember_tokens
- `id`: Primary key
- `user_id`: Foreign key to users table
- `token`: Remember me token
- `expires_at`: Token expiration time
- `created_at`: Token creation timestamp

#### user_achievements
- `id`: Primary key
- `user_id`: Foreign key to users table
- `achievement_id`: Achievement identifier
- `unlocked_at`: Achievement unlock timestamp

#### user_characters
- `id`: Primary key
- `user_id`: Foreign key to users table
- `character_name`: Character name
- `character_class`: Character class
- `traits`: JSON array of character traits
- `level`: Character level
- `experience`: Experience points
- `created_at`: Character creation timestamp

## Error Handling

All classes implement comprehensive error handling:

- **Database errors**: Logged and wrapped in RuntimeException
- **Validation errors**: Returned as structured arrays with error messages
- **Authentication errors**: Clear error messages for invalid credentials
- **Game logic errors**: Exceptions for invalid states or choices

## Security Features

- **Password hashing**: Uses PHP's `password_hash()` and `password_verify()`
- **SQL injection prevention**: Prepared statements throughout
- **XSS protection**: All output properly escaped
- **Session security**: Secure session handling
- **CSRF protection**: Token-based protection for forms
- **Input validation**: Comprehensive validation for all user inputs

## Performance Optimizations

- **Database connection pooling**: Singleton pattern for connections
- **Prepared statement caching**: Reuse of prepared statements
- **Transaction management**: Batch operations for better performance
- **WAL mode**: SQLite Write-Ahead Logging for concurrent access
- **Foreign key constraints**: Database-level data integrity

## Testing

The application includes comprehensive test coverage:

- **Unit tests**: Individual class and method testing
- **Integration tests**: Full workflow testing
- **Feature tests**: Web interface and user experience testing
- **Edge case testing**: Boundary conditions and error scenarios

Run tests with:
```bash
./vendor/bin/phpunit
```

## Deployment

### Requirements
- PHP 8.0 or higher
- SQLite support
- Web server (Apache/Nginx) or PHP built-in server

### Setup
1. Run `php setup.php` to initialize the database
2. Configure web server to serve from `public/` directory
3. Set appropriate file permissions for `data/` directory
4. Configure environment variables if needed

### Production Considerations
- Enable error logging
- Set secure session configuration
- Use HTTPS for production deployment
- Regular database backups
- Monitor performance and logs
