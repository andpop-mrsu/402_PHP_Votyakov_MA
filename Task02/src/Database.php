<?php

class Database
{
    private $pdo;
    
    public function __construct()
    {
        $dbPath = __DIR__ . '/../db/game.db';
        $dir = dirname($dbPath);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $this->pdo = new PDO('sqlite:' . $dbPath);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->createTable();
    }
    
    private function createTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS games (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            player_name TEXT NOT NULL,
            progression TEXT NOT NULL,
            correct_answer INTEGER NOT NULL,
            user_answer INTEGER NOT NULL,
            is_correct BOOLEAN NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->pdo->exec($sql);
    }
    
    public function saveGame($playerName, $progression, $correctAnswer, $userAnswer, $isCorrect)
    {
        $sql = "INSERT INTO games (player_name, progression, correct_answer, user_answer, is_correct) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $playerName,
            implode(' ', $progression),
            $correctAnswer,
            $userAnswer,
            $isCorrect ? 1 : 0
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    public function getGameHistory($limit = 100)
    {
        $sql = "SELECT * FROM games ORDER BY created_at DESC LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getStats()
    {
        $sql = "SELECT 
                COUNT(*) as total_games,
                SUM(is_correct) as correct_games,
                AVG(is_correct) * 100 as accuracy_percent
                FROM games";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function clearHistory()
    {
    $this->pdo->exec("DELETE FROM games");
    $this->pdo->exec("VACUUM");
    }
}