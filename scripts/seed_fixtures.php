<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';

$pdo = db();

const PLAYER_COUNT = 50;
const COACH_COUNT = 5;
const FAN_COUNT = 2;
const NEWS_COUNT = 20;
const FIXTURE_EMAIL_DOMAIN = 'example.com';

if (PHP_SAPI !== 'cli') {
    http_response_code(400);
    exit('This script must be run from CLI.' . PHP_EOL);
}

function fixtureEmail(string $kind, int $index): string
{
    return sprintf('fixture.%s.%02d@%s', $kind, $index, FIXTURE_EMAIL_DOMAIN);
}

function randomChoice(array $items)
{
    return $items[array_rand($items)];
}

$passwordHash = password_hash('password123', PASSWORD_DEFAULT);
if ($passwordHash === false) {
    exit('Failed to generate password hash.' . PHP_EOL);
}

$positions = ['Goalkeeper', 'Defender', 'Midfielder', 'Forward'];
$availabilities = ['available', 'injured', 'suspended'];

$adminUserIdStmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
$adminUserId = $adminUserIdStmt->fetchColumn();
$adminUserId = $adminUserId === false ? null : (int) $adminUserId;

try {
    $pdo->beginTransaction();

    // Clear previous fixture rows created by this script only.
    $deletePlayers = $pdo->prepare('DELETE FROM players WHERE email LIKE :pattern');
    $deleteCoaches = $pdo->prepare('DELETE FROM coaches WHERE email LIKE :pattern');
    $deleteUsers = $pdo->prepare('DELETE FROM users WHERE email LIKE :pattern');
    $deleteNews = $pdo->prepare('DELETE FROM news WHERE title LIKE :titlePattern');

    $deletePlayers->execute(['pattern' => 'fixture.player.%@' . FIXTURE_EMAIL_DOMAIN]);
    $deleteCoaches->execute(['pattern' => 'fixture.coach.%@' . FIXTURE_EMAIL_DOMAIN]);
    $deleteUsers->execute(['pattern' => 'fixture.%@' . FIXTURE_EMAIL_DOMAIN]);
    $deleteNews->execute(['titlePattern' => 'Fixture News %']);

    $insertUser = $pdo->prepare(
        'INSERT INTO users (name, email, password_hash, role, created_at)
         VALUES (:name, :email, :password_hash, :role, NOW())'
    );

    $insertCoach = $pdo->prepare(
        'INSERT INTO coaches (name, email, experience_years, created_at)
         VALUES (:name, :email, :experience_years, NOW())'
    );

    $insertPlayer = $pdo->prepare(
        'INSERT INTO players (
            name, email, position, jersey_number, team_id, coach_id,
            approval_status, availability, bio, matches_played, goals, assists, created_at
         ) VALUES (
            :name, :email, :position, :jersey_number, NULL, :coach_id,
            :approval_status, :availability, :bio, :matches_played, :goals, :assists, NOW()
         )'
    );

    $insertNews = $pdo->prepare(
        'INSERT INTO news (title, content, is_published, published_at, created_by, created_at)
         VALUES (:title, :content, 1, :published_at, :created_by, NOW())'
    );

    $coachIds = [];

    for ($i = 1; $i <= COACH_COUNT; $i++) {
        $name = sprintf('Fixture Coach %02d', $i);
        $email = fixtureEmail('coach', $i);

        $insertUser->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => 'coach',
        ]);

        $insertCoach->execute([
            'name' => $name,
            'email' => $email,
            'experience_years' => random_int(3, 15),
        ]);

        $coachIds[] = (int) $pdo->lastInsertId();
    }

    for ($i = 1; $i <= PLAYER_COUNT; $i++) {
        $name = sprintf('Fixture Player %02d', $i);
        $email = fixtureEmail('player', $i);
        $coachId = $coachIds[array_rand($coachIds)];
        $position = randomChoice($positions);

        $insertUser->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => 'player',
        ]);

        $insertPlayer->execute([
            'name' => $name,
            'email' => $email,
            'position' => $position,
            'jersey_number' => null,
            'coach_id' => $coachId,
            'approval_status' => 'approved',
            'availability' => randomChoice($availabilities),
            'bio' => 'Fixture-generated player profile.',
            'matches_played' => random_int(0, 38),
            'goals' => random_int(0, 30),
            'assists' => random_int(0, 20),
        ]);
    }

    for ($i = 1; $i <= FAN_COUNT; $i++) {
        $name = sprintf('Fixture Fan %02d', $i);
        $email = fixtureEmail('fan', $i);

        $insertUser->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => 'public',
        ]);
    }

    for ($i = 1; $i <= NEWS_COUNT; $i++) {
        $title = sprintf('Fixture News %02d', $i);
        $content = sprintf(
            'This is fixture news item %02d for testing list, pagination, and detail views in the admin panel.',
            $i
        );

        $publishedAt = (new DateTimeImmutable('now'))
            ->modify(sprintf('-%d hours', $i * 6))
            ->format('Y-m-d H:i:s');

        $insertNews->execute([
            'title' => $title,
            'content' => $content,
            'published_at' => $publishedAt,
            'created_by' => $adminUserId,
        ]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, 'Fixture seeding failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo 'Fixtures seeded successfully.' . PHP_EOL;
echo '- Players: ' . PLAYER_COUNT . PHP_EOL;
echo '- Coaches: ' . COACH_COUNT . PHP_EOL;
echo '- Fans (public users): ' . FAN_COUNT . PHP_EOL;
echo '- News: ' . NEWS_COUNT . PHP_EOL;
echo 'Default fixture password: password123' . PHP_EOL;
