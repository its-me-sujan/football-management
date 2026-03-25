<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    set_flash('error', 'Invalid action ID.');
    header('Location: ' . app_url('/actions/index.php'));
    exit;
}

$pdo = db();
$fetchStmt = $pdo->prepare('SELECT id, created_by FROM actions WHERE id = :id LIMIT 1');
$fetchStmt->execute(['id' => $id]);
$action = $fetchStmt->fetch();

if ($action === false) {
    set_flash('error', 'Action not found.');
    header('Location: ' . app_url('/actions/index.php'));
    exit;
}

if (!can_edit_action($action)) {
    http_response_code(403);
    exit('You do not have permission to delete this action.');
}

$stmt = $pdo->prepare('DELETE FROM actions WHERE id = :id');
$stmt->execute(['id' => $id]);

set_flash('success', 'Action deleted successfully.');
header('Location: ' . app_url('/actions/index.php'));
exit;
