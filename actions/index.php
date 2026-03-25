<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';

$user = current_user();
$pdo = db();
$stmt = $pdo->query(
    'SELECT a.id, a.title, a.description, a.action_date, a.visibility, a.created_by, u.name AS author_name
     FROM actions a
     INNER JOIN users u ON u.id = a.created_by
     ORDER BY a.action_date DESC, a.id DESC'
);
$allActions = $stmt->fetchAll();

$actions = array_values(array_filter($allActions, static function (array $action): bool {
    return can_view_visibility($action['visibility']);
}));

$pageTitle = 'Football Actions';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Football Actions</h1>
    <?php if (can_manage_actions()): ?>
        <a class="btn btn-primary" href="<?= e(app_url('/actions/create.php')) ?>">Add Action</a>
    <?php endif; ?>
</div>

<p class="text-muted">
    Current role: <strong><?= e(current_role()) ?></strong>
</p>

<?php if ($actions === []): ?>
    <div class="alert alert-info">No actions are visible for your current role.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th scope="col">Date</th>
                    <th scope="col">Title</th>
                    <th scope="col">Description</th>
                    <th scope="col">Visibility</th>
                    <th scope="col">Created By</th>
                    <th scope="col" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($actions as $action): ?>
                    <tr>
                        <td><?= e($action['action_date']) ?></td>
                        <td><?= e($action['title']) ?></td>
                        <td><?= e($action['description'] ?? '') ?></td>
                        <td><span class="badge text-bg-secondary"><?= e($action['visibility']) ?></span></td>
                        <td><?= e($action['author_name']) ?></td>
                        <td class="text-end">
                            <?php if (can_edit_action($action)): ?>
                                <a class="btn btn-sm btn-outline-secondary" href="<?= e(app_url('/actions/edit.php?id=' . (int) $action['id'])) ?>">Edit</a>
                                <form method="post" action="<?= e(app_url('/actions/delete.php')) ?>" class="d-inline" onsubmit="return confirm('Delete this action?');">
                                    <input type="hidden" name="id" value="<?= e((string) $action['id']) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted small">No manage access</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
