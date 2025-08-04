<?php
session_start();
require_once '../src/Database.php';
require_once '../src/Auth.php';
require_once '../src/GameEngine.php';

use Game\Auth;
use Game\GameEngine;

$auth = new Auth();

// Handle logout
if (isset($_GET['logout'])) {
    $auth->logout();
    header('Location: index.php');
    exit;
}

$isLoggedIn = $auth->isLoggedIn();
$gameEngine = null;
$hasExistingGame = false;

if ($isLoggedIn) {
    $gameEngine = new GameEngine($auth->getCurrentUserId());
    $hasExistingGame = isset($_SESSION['game']) || $gameEngine->loadGame();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Your Path - Text RPG Adventure</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="home-page">
    <div class="container">
        <header class="game-header">
            <h1 class="game-title">Choose Your Path</h1>
            <p class="game-subtitle">A Text-Based RPG Adventure</p>
        </header>
        
        <main class="home-content">
            <?php if ($isLoggedIn): ?>
                <div class="welcome-section">
                    <h2>Welcome back, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
                    
                    <div class="game-actions">
                        <?php if ($hasExistingGame): ?>
                            <div class="action-card">
                                <h3>Continue Adventure</h3>
                                <p>Resume your journey through the enchanted forest.</p>
                                <a href="story.php" class="btn btn-primary">Continue Game</a>
                            </div>
                            
                            <div class="action-card">
                                <h3>New Adventure</h3>
                                <p>Start fresh with a new character and story.</p>
                                <a href="story.php?new=1" class="btn btn-secondary">New Game</a>
                            </div>
                        <?php else: ?>
                            <div class="action-card featured">
                                <h3>Begin Your Adventure</h3>
                                <p>Enter the mystical world and forge your destiny.</p>
                                <a href="story.php?new=1" class="btn btn-primary">Start Game</a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="action-card">
                            <h3>Leaderboard</h3>
                            <p>See how other adventurers have fared.</p>
                            <a href="leaderboard.php" class="btn btn-tertiary">View Rankings</a>
                        </div>
                        
                        <div class="action-card">
                            <h3>Scrum Methodology</h3>
                            <p>Learn about our agile development process.</p>
                            <a href="scrum.php" class="btn btn-tertiary">View Process</a>
                        </div>
                    </div>
                    
                    <div class="user-menu">
                        <a href="?logout=1" class="link-subtle">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="guest-section">
                    <div class="hero-content">
                        <h2>Enter a World of Adventure</h2>
                        <p>Navigate through mystical forests, make crucial decisions, and discover your destiny in this immersive text-based RPG.</p>
                        
                        <div class="features">
                            <div class="feature">
                                <h4>🌟 Branching Storylines</h4>
                                <p>Your choices shape the narrative</p>
                            </div>
                            <div class="feature">
                                <h4>⚔️ Character Progression</h4>
                                <p>Manage health and collect items</p>
                            </div>
                            <div class="feature">
                                <h4>🏆 Leaderboard</h4>
                                <p>Compete with other players</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="auth-actions">
                        <a href="login.php" class="btn btn-primary">Login</a>
                        <a href="register.php" class="btn btn-secondary">Create Account</a>
                        <a href="leaderboard.php" class="btn btn-tertiary">View Leaderboard</a>
                        <a href="scrum.php" class="btn btn-tertiary">Scrum Process</a>
                    </div>
                </div>
            <?php endif; ?>
        </main>
        
        <footer class="game-footer">
            <p>&copy; 2024 Choose Your Path RPG | Built with PHP & SQLite</p>
        </footer>
    </div>
</body>
</html>