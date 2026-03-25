<?php

declare(strict_types=1);

function app_url(string $path = ''): string
{
    $base = rtrim($_ENV['APP_URL'] ?? '', '/');

    if ($base === '') {
        return $path;
    }

    if ($path === '') {
        return $base;
    }

    return $base . '/' . ltrim($path, '/');
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
