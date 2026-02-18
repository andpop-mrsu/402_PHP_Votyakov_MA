<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

// Подключаемся к базе данных
$dbPath = __DIR__ . '/../db/game.db';
try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Создаем таблицы, если их нет
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS games (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            player_name TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            is_finished BOOLEAN DEFAULT 0,
            result TEXT
        );
        
        CREATE TABLE IF NOT EXISTS progressions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            game_id INTEGER NOT NULL,
            first_number INTEGER NOT NULL,
            step INTEGER NOT NULL,
            missing_position INTEGER NOT NULL,
            correct_number INTEGER NOT NULL,
            user_answer INTEGER,
            FOREIGN KEY (game_id) REFERENCES games(id)
        );
        
        CREATE TABLE IF NOT EXISTS progression_numbers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            progression_id INTEGER NOT NULL,
            position INTEGER NOT NULL,
            number INTEGER NOT NULL,
            is_missing BOOLEAN DEFAULT 0,
            FOREIGN KEY (progression_id) REFERENCES progressions(id)
        );
    ");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);
$app->addBodyParsingMiddleware();

// CORS middleware для разработки (разрешаем запросы с любого источника)
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// OPTIONS preflight для CORS
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

// Главная страница - отдаем SPA
$app->get('/', function (Request $request, Response $response) {
    $html = file_get_contents(__DIR__ . '/index.html');
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

// GET /games - список всех игр
$app->get('/games', function (Request $request, Response $response) use ($pdo) {
    $stmt = $pdo->query('
        SELECT g.*, p.first_number, p.step, p.missing_position, p.correct_number, p.user_answer 
        FROM games g 
        LEFT JOIN progressions p ON g.id = p.game_id 
        ORDER BY g.created_at DESC
    ');
    $games = $stmt->fetchAll();
    
    $response->getBody()->write(json_encode($games));
    return $response->withHeader('Content-Type', 'application/json');
});

// GET /games/{id} - детали игры (вся прогрессия)
$app->get('/games/{id}', function (Request $request, Response $response, array $args) use ($pdo) {
    $gameId = $args['id'];
    
    // Получаем информацию об игре
    $stmt = $pdo->prepare('SELECT * FROM games WHERE id = ?');
    $stmt->execute([$gameId]);
    $game = $stmt->fetch();
    
    if (!$game) {
        throw new HttpNotFoundException($request, 'Game not found');
    }
    
    // Получаем прогрессию
    $stmt = $pdo->prepare('SELECT * FROM progressions WHERE game_id = ?');
    $stmt->execute([$gameId]);
    $progression = $stmt->fetch();
    
    // Получаем все числа прогрессии
    $stmt = $pdo->prepare('
        SELECT position, number, is_missing 
        FROM progression_numbers 
        WHERE progression_id = ? 
        ORDER BY position ASC
    ');
    $stmt->execute([$progression['id']]);
    $numbers = $stmt->fetchAll();
    
    $result = [
        'game' => $game,
        'progression' => $progression,
        'numbers' => $numbers
    ];
    
    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json');
});

// POST /games - создание новой игры
$app->post('/games', function (Request $request, Response $response) use ($pdo) {
    $data = $request->getParsedBody();
    $playerName = $data['player_name'] ?? 'Anonymous';
    
    // Генерируем случайную арифметическую прогрессию
    $firstNumber = rand(1, 20);
    $step = rand(2, 10);
    $missingPosition = rand(0, 9); // позиция пропущенного числа (0-9)
    
    // Вычисляем все числа прогрессии
    $numbers = [];
    for ($i = 0; $i < 10; $i++) {
        $numbers[$i] = $firstNumber + $i * $step;
    }
    $correctNumber = $numbers[$missingPosition];
    
    $pdo->beginTransaction();
    try {
        // 1. Создаем запись в таблице games
        $stmt = $pdo->prepare('INSERT INTO games (player_name) VALUES (?)');
        $stmt->execute([$playerName]);
        $gameId = $pdo->lastInsertId();
        
        // 2. Создаем запись в таблице progressions
        $stmt = $pdo->prepare('
            INSERT INTO progressions (game_id, first_number, step, missing_position, correct_number) 
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([$gameId, $firstNumber, $step, $missingPosition, $correctNumber]);
        $progressionId = $pdo->lastInsertId();
        
        // 3. Сохраняем все числа прогрессии
        $stmt = $pdo->prepare('
            INSERT INTO progression_numbers (progression_id, position, number, is_missing) 
            VALUES (?, ?, ?, ?)
        ');
        
        foreach ($numbers as $pos => $num) {
            $isMissing = ($pos == $missingPosition) ? 1 : 0;
            $stmt->execute([$progressionId, $pos, $num, $isMissing]);
        }
        
        $pdo->commit();
        
        // Возвращаем данные для отображения игроку
        $displayNumbers = $numbers;
        $displayNumbers[$missingPosition] = '...'; // заменяем пропущенное число на точки
        
        $result = [
            'id' => $gameId,
            'progression_id' => $progressionId,
            'numbers' => $displayNumbers,
            'missing_position' => $missingPosition
        ];
        
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
});

// POST /step/{id} - сделать ход (ответить на вопрос)
$app->post('/step/{id}', function (Request $request, Response $response, array $args) use ($pdo) {
    $gameId = $args['id'];
    $data = $request->getParsedBody();
    $userAnswer = $data['answer'] ?? null;
    
    // Проверяем, существует ли игра и не завершена ли она
    $stmt = $pdo->prepare('SELECT is_finished, result FROM games WHERE id = ?');
    $stmt->execute([$gameId]);
    $game = $stmt->fetch();
    
    if (!$game) {
        throw new HttpNotFoundException($request, 'Game not found');
    }
    
    if ($game['is_finished']) {
        $error = ['error' => 'Game is already finished'];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
    
    // Получаем информацию о прогрессии
    $stmt = $pdo->prepare('SELECT * FROM progressions WHERE game_id = ?');
    $stmt->execute([$gameId]);
    $progression = $stmt->fetch();
    
    // Получаем все числа прогрессии для отображения
    $stmt = $pdo->prepare('
        SELECT number, position, is_missing 
        FROM progression_numbers 
        WHERE progression_id = ? 
        ORDER BY position ASC
    ');
    $stmt->execute([$progression['id']]);
    $numbersData = $stmt->fetchAll();
    
    $numbers = array_column($numbersData, 'number');
    $correctNumber = $progression['correct_number'];
    
    $pdo->beginTransaction();
    try {
        // Проверяем ответ
        $isCorrect = ($userAnswer == $correctNumber);
        $result = $isCorrect ? 'win' : 'lose';
        
        // Обновляем игру
        $stmt = $pdo->prepare('UPDATE games SET is_finished = 1, result = ? WHERE id = ?');
        $stmt->execute([$result, $gameId]);
        
        // Сохраняем ответ пользователя
        $stmt = $pdo->prepare('UPDATE progressions SET user_answer = ? WHERE game_id = ?');
        $stmt->execute([$userAnswer, $gameId]);
        
        $pdo->commit();
        
        // Формируем ответ
        $displayNumbers = $numbers;
        if (!$isCorrect) {
            // В случае ошибки показываем правильное число
            $displayNumbers[$progression['missing_position']] = $correctNumber;
        }
        
        $responseData = [
            'game_id' => $gameId,
            'is_correct' => $isCorrect,
            'correct_number' => $correctNumber,
            'user_answer' => $userAnswer,
            'message' => $isCorrect ? 'Правильно! Молодец!' : 'Неправильно. Правильный ответ: ' . $correctNumber,
            'numbers' => $displayNumbers,
            'missing_position' => $progression['missing_position']
        ];
        
        $response->getBody()->write(json_encode($responseData));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
});

$app->run();