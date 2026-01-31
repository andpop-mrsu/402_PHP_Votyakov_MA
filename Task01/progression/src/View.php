<?php

namespace Mih_gif\Progression;

use cli\Streams;

class View
{
    public static function showWelcome()
    {
        Streams::line("=== Арифметическая прогрессия ===");
        Streams::line("Угадайте пропущенное число в прогрессии!");
    }
    
    public static function showProgression(array $progression)
    {
        $display = implode(' ', $progression);
        Streams::line("Прогрессия: $display");
    }
    
    public static function showFullProgression(array $progression, $hiddenIndex, $hiddenValue)
    {
        $progression[$hiddenIndex] = $hiddenValue;
        $display = implode(' ', $progression);
        Streams::line("Полная прогрессия: $display");
    }
    
    public static function showSuccess($message)
    {
        Streams::line("\033[32m✓ $message\033[0m");
    }
    
    public static function showError($message)
    {
        Streams::line("\033[31m✗ $message\033[0m");
    }
    
    public static function showGoodbye()
    {
        Streams::line("Спасибо за игру!");
    }
    
    public static function prompt($message)
    {
        return Streams::prompt($message);
    }
    
    public static function showGameHistory(array $games)
    {
        Streams::line("\n=== История игр ===");
        foreach ($games as $game) {
            $result = $game['is_correct'] ? '✓' : '✗';
            Streams::line("{$game['name']} | {$game['date']} | $result | Прогрессия: {$game['progression']}");
        }
    }
}