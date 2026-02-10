<?php

class Game
{
    public function generateProgression()
    {
        $start = rand(1, 10);
        $step = rand(1, 10);
        $length = 10;
        $hiddenIndex = rand(0, $length - 1);
        
        $progression = [];
        $displayProgression = [];
        
        for ($i = 0; $i < $length; $i++) {
            $value = $start + $i * $step;
            $progression[] = $value;
            $displayProgression[] = ($i === $hiddenIndex) ? '..' : $value;
        }
        
        $hiddenValue = $progression[$hiddenIndex];
        
        return [
            'progression' => $progression,
            'display_progression' => $displayProgression,
            'hidden_index' => $hiddenIndex,
            'hidden_value' => $hiddenValue,
            'start' => $start,
            'step' => $step,
            'length' => $length
        ];
    }
    
    public function checkAnswer($userAnswer, $correctAnswer)
    {
        return (int)$userAnswer === $correctAnswer;
    }
    
    public function getFullProgression($game)
    {
        $full = $game['display_progression'];
        $full[$game['hidden_index']] = $game['hidden_value'];
        return $full;
    }
}