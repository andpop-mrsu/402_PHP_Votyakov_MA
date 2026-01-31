# Игра "Арифметическая прогрессия"

Консольная игра на PHP. Угадай пропущенное число в прогрессии.

## Пример
```
Прогрессия: 5 8 11 .. 17 20 23 26 29 32
Какое число пропущено? 14
✓ Правильно!
```

## Структура
- `bin/progression` - запускной файл
- `src/Controller.php` - логика игры
- `src/View.php` - вывод/ввод

## Требования
- PHP 7.4+
- Composer
- PSR-4, PSR-12

## Локальная установка
composer require honor/progression:1.0.0

## Глобальная установка
composer global require honor/progression -W

## Packagist
https://packagist.org/packages/honor/progression