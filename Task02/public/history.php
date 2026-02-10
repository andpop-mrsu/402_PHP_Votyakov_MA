<?php
require_once __DIR__ . '/../src/Database.php';

$db = new Database();
$games = $db->getGameHistory();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>История игр</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>📊 История игр</h1>
        
        <div class="navigation">
            <a href="index.php" class="btn back-btn">← На главную</a>
            <a href="game.php" class="btn game-btn">🎮 Новая игра</a>
        </div>
        
        <?php if (empty($games)): ?>
            <div class="no-history">
                <p>История игр пуста. Сыграйте первую игру!</p>
            </div>
        <?php else: ?>
            <div class="history-table">
                <table>
                    <thead>
                        <tr>
                            <th>Имя</th>
                            <th>Дата</th>
                            <th>Результат</th>
                            <th>Прогрессия</th>
                            <th>Ответ</th>
                            <th>Правильный</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($games as $game): ?>
                        <tr class="<?= $game['is_correct'] ? 'correct' : 'incorrect' ?>">
                            <td><?= htmlspecialchars($game['player_name']) ?></td>
                            <td><?= date('d.m.Y H:i', strtotime($game['created_at'])) ?></td>
                            <td>
                                <?php if ($game['is_correct']): ?>
                                    <span class="result-icon correct-icon">✓</span>
                                <?php else: ?>
                                    <span class="result-icon incorrect-icon">✗</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="progression-small">
                                    <?php 
                                    $progArray = explode(' ', $game['progression']);
                                    foreach ($progArray as $num): 
                                    ?>
                                        <span><?= htmlspecialchars($num) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($game['user_answer']) ?></td>
                            <td><?= htmlspecialchars($game['correct_answer']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="stats">
                    <p>Всего игр: <strong><?= count($games) ?></strong></p>
                    <?php
                    $correctCount = count(array_filter($games, fn($g) => $g['is_correct']));
                    $accuracy = count($games) > 0 ? round(($correctCount / count($games)) * 100, 1) : 0;
                    ?>
                    <p>Правильных ответов: <strong><?= $correctCount ?></strong></p>
                    <p>Точность: <strong><?= $accuracy ?>%</strong></p>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="history-actions">
            <form method="POST" action="clear_history.php" onsubmit="return confirm('Вы уверены, что хотите очистить всю историю?');">
                <button type="submit" class="btn clear-btn">🗑️ Очистить историю</button>
            </form>
        </div>
    </div>
</body>
</html>