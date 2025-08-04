<?php
session_start();
require_once '../src/Database.php';
require_once '../src/Auth.php';
require_once '../src/GameEngine.php';

use Game\Auth;
use Game\GameEngine;

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Handle logout request
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    $auth->logout();
    header('Location: login.php');
    exit;
}

$gameEngine = new GameEngine($auth->getCurrentUserId());
$message = '';
$messageType = '';

// Handle new game request
if (isset($_GET['new']) && $_GET['new'] == '1') {
    $gameEngine->startNewGame();
    $message = 'New adventure started!';
    $messageType = 'success';
}

// Handle choice submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['choice'])) {
    try {
        $result = $gameEngine->makeChoice($_POST['choice']);
        
        if ($result['hp_change'] > 0) {
            $message = "You gained {$result['hp_change']} HP!";
            $messageType = 'success';
        } elseif ($result['hp_change'] < 0) {
            $message = "You lost " . abs($result['hp_change']) . " HP!";
            $messageType = 'error';
        }
        
        if ($result['item_gained']) {
            $message .= " You found: {$result['item_gained']}!";
        }
        
    } catch (Exception $e) {
        $message = 'Invalid choice!';
        $messageType = 'error';
    }
}

// Load game state
if (!isset($_SESSION['game'])) {
    if (!$gameEngine->loadGame()) {
        $gameEngine->startNewGame();
    }
}

$gameState = $gameEngine->getGameState();

if (empty($gameState)) {
    header('Location: index.php');
    exit;
}

// Check if game is over
if ($gameEngine->isGameOver()) {
    $sceneData = $gameEngine->loadScene($gameState['current_scene']);
    if (isset($sceneData['ending_type'])) {
        $gameEngine->endGame($sceneData['ending_type']);
        header('Location: end.php?ending=' . urlencode($sceneData['ending_type']));
        exit;
    }
}

// Load current scene
try {
    $sceneData = $gameEngine->loadScene($gameState['current_scene']);
} catch (Exception $e) {
    $message = 'Scene not found! Starting new game.';
    $messageType = 'error';
    $gameEngine->startNewGame();
    $gameState = $gameEngine->getGameState();
    $sceneData = $gameEngine->loadScene($gameState['current_scene']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($sceneData['title']) ?> - Choose Your Path</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Stats Toggle -->
    <input type="checkbox" id="stats-toggle" class="stats-toggle">
    <label for="stats-toggle" class="stats-label" aria-label="Toggle Stats Sidebar">📊 Stats</label>
    
    <!-- Stats Sidebar -->
    <div class="stats-sidebar" role="complementary" aria-label="Player Statistics">
        <div class="stats-content">
            <h3>Player Stats</h3>
            
            <div class="stat-item">
                <span>Health:</span>
                <span><?= $gameState['hp'] ?>/100</span>
            </div>
            
            <div class="hp-bar" role="progressbar" aria-valuenow="<?= $gameState['hp'] ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="hp-fill" style="width: <?= $gameState['hp'] ?>%"></div>
            </div>
            
            <div class="stat-item">
                <span>Choices Made:</span>
                <span><?= count($gameState['choices_made']) ?></span>
            </div>
            
            <?php if (!empty($gameState['inventory'])): ?>
                <h4>Inventory</h4>
                <ul class="inventory-list">
                    <?php foreach ($gameState['inventory'] as $item): ?>
                        <li><?= htmlspecialchars($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="container">
        <nav class="game-nav">
            <h1>Choose Your Path</h1>
            <div class="nav-links">
                <span>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="index.php" class="btn btn-secondary">Home</a>
                <a href="?logout=1" class="link-subtle">Logout</a>
            </div>
        </nav>
        
        <main class="story-content">
            <?php if ($message): ?>
                <div class="message <?= $messageType ?>" role="alert" aria-live="polite">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <div class="story-card">
                <h1 class="scene-title"><?= htmlspecialchars($sceneData['title']) ?></h1>
                
                <div class="scene-text" role="main">
                    <?= nl2br(htmlspecialchars($sceneData['text'])) ?>
                </div>
                
                <?php if (!empty($sceneData['choices'])): ?>
                    <div class="choices-container">
                        <h3>What do you choose?</h3>
                        
                        <form method="POST" action="">
                            <?php foreach ($sceneData['choices'] as $choiceKey => $choice): ?>
                                <button type="submit" name="choice" value="<?= htmlspecialchars($choiceKey) ?>" 
                                        class="btn choice-btn">
                                    <?= htmlspecialchars($choice['text']) ?>
                                    <?php if (isset($choice['hp_change']) && $choice['hp_change'] != 0): ?>
                                        <small>(<?= $choice['hp_change'] > 0 ? '+' : '' ?><?= $choice['hp_change'] ?> HP)</small>
                                    <?php endif; ?>
                                    <?php if (isset($choice['add_item'])): ?>
                                        <small>(Gain: <?= htmlspecialchars($choice['add_item']) ?>)</small>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </form>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($sceneData['is_ending']) && $sceneData['is_ending']): ?>
                    <div class="ending-actions">
                        <a href="index.php" class="btn btn-primary">Return Home</a>
                        <a href="leaderboard.php" class="btn btn-secondary">View Leaderboard</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>