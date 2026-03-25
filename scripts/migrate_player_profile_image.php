<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';

$pdo = db();

$columnExistsStmt = $pdo->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'players'
       AND COLUMN_NAME = 'profile_image'"
);
$columnExists = (int) $columnExistsStmt->fetchColumn();

if ($columnExists === 0) {
    $pdo->exec('ALTER TABLE players ADD COLUMN profile_image VARCHAR(255) NULL AFTER bio');
    echo 'Added players.profile_image column.' . PHP_EOL;
    exit(0);
}

echo 'players.profile_image already exists.' . PHP_EOL;
