<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';
require_player();

$pdo = db();
$user = current_user();

$stmt = $pdo->prepare('SELECT id, name, email, position, bio FROM players WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $user['email']]);
$player = $stmt->fetch();

if ($player === false) {
    http_response_code(404);
    exit('Player profile not found. Ask admin to add your player record with the same email as your account.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    if ($name === '' || $position === '') {
        set_flash('error', 'Name and position are required.');
    } else {
        $update = $pdo->prepare('UPDATE players SET name = :name, position = :position, bio = :bio WHERE id = :id');
        $update->execute([
            'name' => $name,
            'position' => $position,
            'bio' => $bio === '' ? null : $bio,
            'id' => $player['id'],
        ]);
        set_flash('success', 'Profile updated.');
        header('Location: ' . app_url('/player/profile.php'));
        exit;
    }

    $player['name'] = $name;
    $player['position'] = $position;
    $player['bio'] = $bio;
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<h1 class="h3 mb-3">Update Personal Profile</h1>

<form method="post" class="card card-body">
    <div class="mb-3">
        <label class="form-label" for="name">Name</label>
        <input class="form-control" id="name" name="name" value="<?= e($player['name']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" id="email" value="<?= e($player['email']) ?>" disabled>
    </div>
    <div class="mb-3">
        <label class="form-label" for="position">Position</label>
        <input class="form-control" id="position" name="position" value="<?= e($player['position']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label" for="bio">Bio</label>
        <textarea class="form-control" id="bio" name="bio" rows="3"><?= e((string) ($player['bio'] ?? '')) ?></textarea>
    </div>
    <button class="btn btn-primary" type="submit">Save Profile</button>
</form>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
