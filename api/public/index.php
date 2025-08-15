<?php
declare(strict_types=1);

use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

/**
 * ВАЖНО: Slim должен знать базовый путь, так как приложение лежит в подпапке /portfolio/api/public.
 * Иначе маршруты типа "/auth/login" не сопоставятся с полным URL "/portfolio/api/public/auth/login".
 */
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';            // например: /portfolio/api/public/index.php
$basePath   = rtrim(str_replace('\\', '/', dirname($scriptName)), '/'); // /portfolio/api/public
$app->setBasePath($basePath);

// Загрузка ядра/маршрутов
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/middleware.php';
require __DIR__ . '/../src/routes/public.php';
require __DIR__ . '/../src/routes/admin.php';

$app->run();
