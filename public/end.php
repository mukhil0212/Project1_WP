<?php
session_start();
require_once '../src/Database.php';
require_once '../src/Auth.php';

use Game\Auth;

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$ending = $_GET['ending'] ?? 'unknown';
$validEndings = ['victory', 'defeat', 'death'];

if (!in_array($ending, $validEndings)) {
    header('Location: index.php');
    exit;
}

$endingData = [
    'victory' => [
        'title' => 'Victory Achieved!',
        'emoji' => '🏆',
        'message' => 'Congratulations! You have successfully completed your adventure and emerged victorious. Your courage and wisdom have been rewarded.',
        'color' => 'success'
    ],
    'defeat' => [
        'title' => 'Adventure Ended',
        'emoji' => '🔴',
        'message' => 'Your journey has come to an end, but every adventure teaches us something valuable. Consider this experience and try again!',
        'color' => 'info'
    ],
    'death' => [
        'title' => 'Game Over',
        'emoji' => '☠️',
        'message' => 'Your health reached zero and your adventure has ended. But heroes never truly die - they respawn to fight another day!',
        'color' => 'error'
    ]
];

$currentEnding = $endingData[$ending];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($currentEnding['title']) ?> - Choose Your Path</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header class="game-header">
            <h1 class="game-title"><?= $currentEnding['emoji'] ?> <?= htmlspecialchars($currentEnding['title']) ?></h1>
        </header>
        
        <main class="story-content">
            <div class="story-card">
                <div class="message <?= $currentEnding['color'] ?>" role="alert">
                    <h2>Adventure Complete</h2>
                    <p><?= htmlspecialchars($currentEnding['message']) ?></p>
                </div>
                
                <div class="ending-stats">
                    <h3>Your Journey Summary</h3>
                    <div class="features">
                        <div class="feature">
                            <h4>🎯 Ending Reached</h4>
                            <p><?= ucfirst(htmlspecialchars($ending)) ?></p>
                        </div>
                        <div class="feature">
                            <h4>👥 Player</h4>
                            <p><?= htmlspecialchars($_SESSION['username']) ?></p>
                        </div>
                        <div class="feature">
                            <h4>📅 Completed</h4>
                            <p><?= date('M j, Y g:i A') ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="ending-actions">
                    <h3>What's Next?</h3>
                    <div class="game-actions">
                        <div class="action-card">
                            <h4>Play Again</h4>
                            <p>Start a new adventure and try different choices.</p>
                            <a href="story.php?new=1" class="btn btn-primary">New Game</a>
                        </div>
                        
                        <div class="action-card">
                            <h4>View Rankings</h4>
                            <p>See how you compare to other adventurers.</p>
                            <a href="leaderboard.php" class="btn btn-secondary">Leaderboard</a>
                        </div>
                        
                        <div class="action-card">
                            <h4>Home</h4>
                            <p>Return to the main menu.</p>
                            <a href="index.php" class="btn btn-tertiary">Home</a>
                        </div>
                    </div>
                </div>
                
                <div class="encouragement">
                    <?php if ($ending === 'victory'): ?>
                        <p><em>"True heroes are made not by the absence of fear, but by the conquest of it."</em></p>
                    <?php elseif ($ending === 'defeat'): ?>
                        <p><em>"Every master was once a beginner. Every pro was once an amateur."</em></p>
                    <?php else: ?>
                        <p><em>"It is not the end, but a new beginning. Rise again, brave adventurer."</em></p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        
        <footer class="game-footer">
            <p>&copy; 2024 Choose Your Path RPG | Built with PHP & SQLite</p>
        </footer>
    </div>
</body>
</html>