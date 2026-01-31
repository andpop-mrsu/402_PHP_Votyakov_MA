<?php

namespace Mih_gif\Progression;

class Controller
{
    public static function startGame()
    {
        View::showWelcome();
        
        $name = View::prompt("Введите ваше имя: ");
        
        while (true) {
            // Генерация прогрессии
            $start = rand(1, 10);
            $step = rand(1, 10);
            $length = 10;
            $hiddenIndex = rand(0, $length - 1);
            
            $progression = [];
            for ($i = 0; $i < $length; $i++) {
                $progression[] = $start + $i * $step;
            }
            
            $hiddenValue = $progression[$hiddenIndex];
            $progression[$hiddenIndex] = '..';
            
            // Показ прогрессии
            View::showProgression($progression);
            
            // Запрос ответа
            $answer = View::prompt("Какое число пропущено? ");
            
            // Проверка
            if ((int) $answer === $hiddenValue) {
                View::showSuccess("Правильно!");
            } else {
                View::showError("Неправильно! Правильный ответ: $hiddenValue");
                View::showFullProgression($progression, $hiddenIndex, $hiddenValue);
            }
            
            // Продолжить?
            $continue = View::prompt("Продолжить? (y/n): ");
            if (strtolower($continue) !== 'y') {
                break;
            }
        }
        
        View::showGoodbye();
    }
}