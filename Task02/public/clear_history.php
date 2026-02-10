<?php
require_once __DIR__ . '/../src/Database.php';

session_start();

$db = new Database();
$db->clearHistory();

header('Location: history.php?cleared=1');
exit;