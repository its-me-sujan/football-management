<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    http_response_code(400);
    exit('Invalid action ID.');
}

$pdo = db();
$fetchStmt = $pdo->prepare('SELECT id, title, description, action_date, visibility, created_by FROM actions WHERE id = :id LIMIT 1');
$fetchStmt->execute(['id' => $id]);
$action = $fetchStmt->fetch();

if ($action === false) {
    http_response_code(404);
    exit('Action not found.');
}

if (!can_edit_action($action)) {
    http_response_code(403);
    exit('You do not have permission to edit this action.');
}

$errors = [];
$title = $action['title'];
$description = $action['description'] ?? '';
$actionDate = $action['action_date'];
$visibility = $action['visibility'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $actionDate = trim($_POST['action_date'] ?? '');
    $visibility = trim($_POST['visibility'] ?? 'public');

    if ($title === '') {
        $errors[] = 'Title is required.';
    }

    $dateObject = DateTime::createFromFormat('Y-m-d', $actionDate);
    if ($actionDate === '' || $dateObject === false || $dateObject->format('Y-m-d') !== $actionDate) {
        $errors[] = 'Please provide a valid action date.';
    }

    if (!can_assign_visibility($visibility)) {
        $errors[] = 'You are not allowed to set this visibility level.';
    }

    if ($errors === []) {
        $updateStmt = $pdo->prepare(
            'UPDATE actions
             SET title = :title, description = :description, action_date = :action_date, visibility = :visibility
             WHERE id = :id'
        );
        $updateStmt->execute([
            'title' => $title,
            'description' => $description,
            'action_date' => $actionDate,
            'visibility' => $visibility,
            'id' => $id,
        ]);

        set_flash('success', 'Action updated successfully.');
        header('Location: ' . app_url('/actions/index.php'));
        exit;
    }
}

$pageTitle = 'Edit Action';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <h1 class="h3 mb-3">Edit Football Action</h1>

        <?php if ($errors !== []): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" id="title" name="title" class="form-control" value="<?= e($title) ?>" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" class="form-control" rows="4"><?= e($description) ?></textarea>
            </div>
            <div class="mb-3">
                <label for="action_date" class="form-label">Action Date</label>
                <input type="date" id="action_date" name="action_date" class="form-control" value="<?= e($actionDate) ?>" required>
            </div>
            <div class="mb-3">
                <label for="visibility" class="form-label">Visibility</label>
                <select id="visibility" name="visibility" class="form-select" required>
                    <option value="public" <?= $visibility === 'public' ? 'selected' : '' ?>>Public</option>
                    <option value="player" <?= $visibility === 'player' ? 'selected' : '' ?>>Player</option>
                    <option value="coach" <?= $visibility === 'coach' ? 'selected' : '' ?>>Coach</option>
                    <?php if (current_role() === 'admin'): ?>
                        <option value="admin" <?= $visibility === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <?php endif; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Update Action</button>
            <a href="<?= e(app_url('/actions/index.php')) ?>" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
