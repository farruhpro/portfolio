<?php
declare(strict_types=1);

use Slim\Routing\RouteCollectorProxy;
use App\Controllers\PublicController;
use App\Controllers\AuthController;

// Регистрируем маршруты напрямую (без /api/public — это уже basePath)
$app->get('/settings', [PublicController::class, 'settings']);
$app->get('/projects', [PublicController::class, 'projects']);
$app->get('/projects/{slug}', [PublicController::class, 'projectBySlug']);
$app->post('/contact', [PublicController::class, 'contact']);
$app->post('/stats/track', [PublicController::class, 'track']);
$app->get('/media/{name}', [PublicController::class, 'serveMedia']);

// Аутентификация
$app->post('/auth/login', [AuthController::class, 'login']);
$app->post('/auth/logout', [AuthController::class, 'logout']);
