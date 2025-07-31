<?php
session_start();
require_once '../src/Database.php';
require_once '../src/Auth.php';
require_once '../src/AchievementSystem.php';

use Game\Auth;
use Game\AchievementSystem;

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$achievementSystem = new AchievementSystem($auth->getCurrentUserId());
$achievements = $achievementSystem->getAllAchievements();
$stats = $achievementSystem->getAchievementStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Achievements - Choose Your Path RPG</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .achievements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .achievement-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255,255,255,0.3);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .achievement-card.unlocked {
            border-color: rgba(243, 156, 18, 0.5);
            background: linear-gradient(135deg, rgba(243,156,18,0.1), rgba(255,255,255,0.95));
        }
        
        .achievement-card.locked {
            opacity: 0.6;
            filter: grayscale(50%);
        }
        
        .achievement-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }
        
        .achievement-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .achievement-icon {
            font-size: 2.5rem;
            margin-right: 1rem;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }
        
        .achievement-info h3 {
            margin: 0 0 0.5rem 0;
            color: #2c3e50;
            font-size: 1.2rem;
        }
        
        .achievement-description {
            color: #7f8c8d;
            font-size: 0.9rem;
            line-height: 1.4;
            margin-bottom: 1rem;
        }
        
        .achievement-status {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: #95a5a6;
        }
        
        .achievement-date {
            font-style: italic;
        }
        
        .achievement-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #f39c12;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .progress-ring {
            width: 120px;
            height: 120px;
            margin: 0 auto 1rem;
        }
        
        .progress-ring-circle {
            stroke: #e9ecef;
            stroke-width: 8;
            fill: transparent;
            r: 52;
            cx: 60;
            cy: 60;
        }
        
        .progress-ring-progress {
            stroke: #f39c12;
            stroke-width: 8;
            stroke-linecap: round;
            fill: transparent;
            r: 52;
            cx: 60;
            cy: 60;
            stroke-dasharray: 326.73;
            stroke-dashoffset: 326.73;
            transform: rotate(-90deg);
            transform-origin: 60px 60px;
            transition: stroke-dashoffset 1s ease-in-out;
        }
        
        .filter-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.7);
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .filter-tab.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: transparent;
        }
        
        .filter-tab:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="game-header">
            <h1 class="game-title">Achievements</h1>
            <p class="game-subtitle">Track Your Legendary Accomplishments</p>
        </header>
        
        <nav class="game-nav">
            <a href="index.php" class="btn btn-secondary">← Back to Game</a>
            <a href="leaderboard.php" class="btn btn-tertiary">Leaderboard</a>
            <a href="?logout=1" class="btn btn-danger">Logout</a>
        </nav>
        
        <main>
            <div class="stats-overview">
                <div class="stat-card">
                    <div class="progress-ring">
                        <svg class="progress-ring" width="120" height="120">
                            <circle class="progress-ring-circle"></circle>
                            <circle class="progress-ring-progress" 
                                    style="stroke-dashoffset: <?= 326.73 - (326.73 * $stats['percentage'] / 100) ?>"></circle>
                        </svg>
                    </div>
                    <div class="stat-number"><?= $stats['percentage'] ?>%</div>
                    <div class="stat-label">Completion</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['unlocked'] ?></div>
                    <div class="stat-label">Unlocked</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total'] ?></div>
                    <div class="stat-label">Total Available</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total'] - $stats['unlocked'] ?></div>
                    <div class="stat-label">Remaining</div>
                </div>
            </div>
            
            <div class="filter-tabs">
                <div class="filter-tab active" onclick="filterAchievements('all')">All</div>
                <div class="filter-tab" onclick="filterAchievements('unlocked')">Unlocked</div>
                <div class="filter-tab" onclick="filterAchievements('locked')">Locked</div>
            </div>
            
            <div class="achievements-grid" id="achievements-grid">
                <?php foreach ($achievements as $achievement): ?>
                    <div class="achievement-card <?= $achievement['unlocked'] ? 'unlocked' : 'locked' ?>" 
                         data-status="<?= $achievement['unlocked'] ? 'unlocked' : 'locked' ?>">
                        <?php if ($achievement['unlocked']): ?>
                            <div class="achievement-badge">Unlocked</div>
                        <?php endif; ?>
                        
                        <div class="achievement-header">
                            <div class="achievement-icon"><?= htmlspecialchars($achievement['icon']) ?></div>
                            <div class="achievement-info">
                                <h3><?= htmlspecialchars($achievement['name']) ?></h3>
                            </div>
                        </div>
                        
                        <div class="achievement-description">
                            <?= htmlspecialchars($achievement['description']) ?>
                        </div>
                        
                        <div class="achievement-status">
                            <span class="achievement-type">
                                <?= $achievement['unlocked'] ? 'Completed' : 'Locked' ?>
                            </span>
                            <?php if ($achievement['unlocked'] && $achievement['unlocked_at']): ?>
                                <span class="achievement-date">
                                    <?= date('M j, Y', strtotime($achievement['unlocked_at'])) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
        
        <footer class="game-footer">
            <p>&copy; 2024 Choose Your Path RPG | Built with PHP & SQLite</p>
        </footer>
    </div>
    
    <script>
        function filterAchievements(filter) {
            const cards = document.querySelectorAll('.achievement-card');
            const tabs = document.querySelectorAll('.filter-tab');
            
            // Update active tab
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
            
            // Filter cards
            cards.forEach(card => {
                const status = card.dataset.status;
                let show = false;
                
                switch(filter) {
                    case 'all':
                        show = true;
                        break;
                    case 'unlocked':
                        show = status === 'unlocked';
                        break;
                    case 'locked':
                        show = status === 'locked';
                        break;
                }
                
                if (show) {
                    card.style.display = 'block';
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'scale(1)';
                    }, 10);
                } else {
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.8)';
                    setTimeout(() => {
                        card.style.display = 'none';
                    }, 300);
                }
            });
        }
        
        // Add entrance animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.achievement-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
