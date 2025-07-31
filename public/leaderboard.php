<?php
session_start();
require_once '../src/Database.php';
require_once '../src/Auth.php';

use Game\Auth;
use Game\Database;

$auth = new Auth();
$db = Database::getInstance()->getConnection();

// Fetch leaderboard data
$stmt = $db->prepare('
    SELECT username, ending_reached, playtime_minutes, final_hp, choices_count, completed_at,
           ROW_NUMBER() OVER (ORDER BY final_hp DESC, playtime_minutes ASC, choices_count DESC) as rank
    FROM leaderboard 
    ORDER BY final_hp DESC, playtime_minutes ASC, choices_count DESC
    LIMIT 50
');
$stmt->execute();
$leaderboard = $stmt->fetchAll();

// Get stats
$stmt = $db->prepare('SELECT COUNT(*) as total_games FROM leaderboard');
$stmt->execute();
$totalGames = $stmt->fetch()['total_games'] ?? 0;

$stmt = $db->prepare('SELECT COUNT(DISTINCT username) as unique_players FROM leaderboard');
$stmt->execute();
$uniquePlayers = $stmt->fetch()['unique_players'] ?? 0;

$stmt = $db->prepare('SELECT AVG(playtime_minutes) as avg_playtime FROM leaderboard');
$stmt->execute();
$avgPlaytime = round($stmt->fetch()['avg_playtime'] ?? 0, 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - Choose Your Path</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header class="game-header">
            <h1 class="game-title">Leaderboard</h1>
            <p class="game-subtitle">Hall of Heroes & Legends</p>
        </header>
        
        <main>
            <!-- Game Statistics -->
            <div class="action-card">
                <h2>Game Statistics</h2>
                <div class="features">
                    <div class="feature">
                        <h4>🎮 <?= $totalGames ?></h4>
                        <p>Total Games Played</p>
                    </div>
                    <div class="feature">
                        <h4>👥 <?= $uniquePlayers ?></h4>
                        <p>Unique Players</p>
                    </div>
                    <div class="feature">
                        <h4>⏱️ <?= $avgPlaytime ?> min</h4>
                        <p>Average Playtime</p>
                    </div>
                </div>
            </div>
            
            <!-- Leaderboard Table -->
            <div class="action-card">
                <h2>Top Players</h2>
                
                <?php if (empty($leaderboard)): ?>
                    <div class="message info">
                        <p>No completed games yet. Be the first to finish an adventure!</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="leaderboard-table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Player</th>
                                    <th>Ending</th>
                                    <th>Final HP</th>
                                    <th>Playtime</th>
                                    <th>Choices</th>
                                    <th>Completed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leaderboard as $entry): ?>
                                    <tr class="<?= $entry['rank'] <= 3 ? 'rank-' . $entry['rank'] : '' ?>">
                                        <td>
                                            <?php if ($entry['rank'] == 1): ?>
                                                🥇 #<?= $entry['rank'] ?>
                                            <?php elseif ($entry['rank'] == 2): ?>
                                                🥈 #<?= $entry['rank'] ?>
                                            <?php elseif ($entry['rank'] == 3): ?>
                                                🥉 #<?= $entry['rank'] ?>
                                            <?php else: ?>
                                                #<?= $entry['rank'] ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($entry['username']) ?></td>
                                        <td>
                                            <?php 
                                            $endingEmojis = [
                                                'victory' => '🏆',
                                                'defeat' => '🔴', 
                                                'death' => '☠️'
                                            ];
                                            $emoji = $endingEmojis[$entry['ending_reached']] ?? '🎯';
                                            echo $emoji . ' ' . ucfirst(htmlspecialchars($entry['ending_reached']));
                                            ?>
                                        </td>
                                        <td>
                                            <span class="hp-display"><?= $entry['final_hp'] ?>/100</span>
                                        </td>
                                        <td><?= $entry['playtime_minutes'] ?> min</td>
                                        <td><?= $entry['choices_count'] ?></td>
                                        <td>
                                            <time datetime="<?= $entry['completed_at'] ?>">
                                                <?= date('M j, Y', strtotime($entry['completed_at'])) ?>
                                            </time>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Navigation -->
            <div class="action-card">
                <div class="game-actions">
                    <?php if ($auth->isLoggedIn()): ?>
                        <a href="index.php" class="btn btn-primary">Back to Game</a>
                        <a href="story.php?new=1" class="btn btn-secondary">Start New Adventure</a>
                    <?php else: ?>
                        <a href="index.php" class="btn btn-primary">Home</a>
                        <a href="register.php" class="btn btn-secondary">Join the Adventure</a>
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