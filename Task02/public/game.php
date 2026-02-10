<?php
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Game.php';

session_start();

$db = new Database();
$game = new Game($db);

// Инициализация сессии для игры
if (!isset($_SESSION['current_game'])) {
    $_SESSION['current_game'] = $game->generateProgression();
}

$currentGame = $_SESSION['current_game'];
$message = '';
$messageType = '';

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $answer = $_POST['answer'] ?? '';
    
    if (empty($name)) {
        $message = 'Пожалуйста, введите ваше имя';
        $messageType = 'error';
    } elseif (!is_numeric($answer)) {
        $message = 'Пожалуйста, введите число';
        $messageType = 'error';
    } else {
        $isCorrect = (int)$answer === $currentGame['hidden_value'];
        
        // Сохранение в базу данных
        $db->saveGame($name, $currentGame['progression'], 
                     $currentGame['hidden_value'], (int)$answer, $isCorrect);
        
        if ($isCorrect) {
            $message = 'Правильно! 🎉';
            $messageType = 'success';
        } else {
            $message = 'Неправильно! Правильный ответ: ' . $currentGame['hidden_value'];
            $messageType = 'error';
        }
        
        // Генерация новой прогрессии
        $_SESSION['current_game'] = $game->generateProgression();
        $currentGame = $_SESSION['current_game'];
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Игра - Арифметическая прогрессия</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>🎯 Игра в прогрессию</h1>
        
        <a href="index.php" class="btn back-btn">← На главную</a>
        
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>
        
        <div class="game-area">
            <div class="progression-display">
                <h3>Прогрессия:</h3>
                <div class="progression">
                    <?php foreach ($currentGame['display_progression'] as $num): ?>
                        <span class="number"><?= $num ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <form method="POST" class="game-form">
                <div class="form-group">
                <label for="player_name">Ваше имя:</label>
                <input type="text" id="player_name" name="name" required 
                autocomplete="name"
                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
    </div>

        <div class="form-group">
            <label for="player_answer">Какое число пропущено?</label>
            <input type="number" id="player_answer" name="answer" required 
            autocomplete="off"
            step="1" placeholder="Введите число">
        </div>
                
                <button type="submit" class="btn submit-btn">Проверить ответ</button>
            </form>
            
            <div class="actions">
                <form method="POST" class="new-game-form">
                    <input type="hidden" name="new_game" value="1">
                    <button type="submit" class="btn new-game-btn">Новая прогрессия</button>
                </form>
                
                <a href="history.php" class="btn history-btn">📊 Посмотреть историю</a>
            </div>
        </div>
    </div>
    
    <script>
    document.getElementById('player_answer')?.focus();
    </script>
</body>
</html>