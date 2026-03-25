<?php

declare(strict_types=1);

require_once __DIR__ . '/app/config/bootstrap.php';

$pdo = db();
$newsItems = [];
try {
    $newsStmt = $pdo->query(
        'SELECT id, title, content, published_at, created_at
         FROM news
         WHERE is_published = 1
         ORDER BY COALESCE(published_at, created_at) DESC, id DESC
         LIMIT 8'
    );
    $newsItems = $newsStmt->fetchAll();
} catch (PDOException $e) {
    $newsItems = [];
}

$pageTitle = 'Home';
require_once __DIR__ . '/app/views/partials/header.php';
?>

<div class="p-5 mb-4 bg-light rounded-3 border">
    <div class="container-fluid py-5">
        <h1 class="display-6 fw-bold">Football Management System</h1>
        <p class="col-md-9 fs-5 mb-4">
            Public users can browse players, teams, and coaches. Players can view stats, coaches can assign jersey numbers and teams, and admins can manage players, coaches, and teams.
        </p>
        <a class="btn btn-primary btn-lg me-2" href="<?= e(app_url('/players/index.php')) ?>">Browse Players</a>
        <a class="btn btn-outline-secondary btn-lg" href="<?= e(app_url('/auth/register.php')) ?>">Create Account</a>
    </div>
</div>

<section class="mt-4" id="weather-section" data-endpoint="<?= e(app_url('/weather/forecast.php')) ?>">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0">Weather Forecast (5 Day / 3 Hour)</h2>
    </div>
    <div id="weather-forecast" class="border rounded p-3 bg-white">
        <p class="mb-0 text-muted">Loading weather forecast...</p>
    </div>
</section>

<section class="mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0">Latest News</h2>
        <?php if (is_admin()): ?>
            <a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('/admin/news.php')) ?>">Manage News</a>
        <?php endif; ?>
    </div>

    <?php if ($newsItems === []): ?>
        <div class="alert alert-info">No news published yet.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($newsItems as $news): ?>
                <div class="col-12">
                    <article class="border rounded p-3 bg-white">
                        <h3 class="h5 mb-2"><?= e($news['title']) ?></h3>
                        <p class="mb-2"><?= nl2br(e($news['content'])) ?></p>
                        <small class="text-muted">Published: <?= e((string) ($news['published_at'] ?? $news['created_at'])) ?></small>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/app/views/partials/footer.php'; ?>
