<?php
declare(strict_types=1);

use Slim\Routing\RouteCollectorProxy;
use App\Controllers\AdminController;

// Группа /admin
$admin = $app->group('/admin', function (RouteCollectorProxy $g) {
  $g->get('/projects', [AdminController::class, 'listProjects']);
  $g->post('/projects', [AdminController::class, 'createProject']);
  $g->put('/projects/{id}', [AdminController::class, 'updateProject']);
  $g->delete('/projects/{id}', [AdminController::class, 'deleteProject']);
  $g->put('/projects/{id}/publish', [AdminController::class, 'publishProject']);
  $g->put('/projects/{id}/unpublish', [AdminController::class, 'unpublishProject']);

  $g->post('/projects/{id}/media', [AdminController::class, 'uploadMedia']);
  $g->put('/media/{id}', [AdminController::class, 'updateMedia']);
  $g->delete('/media/{id}', [AdminController::class, 'deleteMedia']);

  $g->put('/sort/projects', [AdminController::class, 'sortProjects']);
  $g->put('/sort/media', [AdminController::class, 'sortMedia']);

  $g->get('/stats/summary', [AdminController::class, 'statsSummary']);

  $g->get('/settings', [AdminController::class, 'getSettings']);
  $g->put('/settings', [AdminController::class, 'saveSettings']);

  $g->get('/users', [AdminController::class, 'listUsers']);
  $g->put('/users/{id}', [AdminController::class, 'updateUser']);
});

// Навешиваем проверку сессии и CSRF на всю группу /admin
$admin->add(function($req, $handler){
  \App\require_admin();
  \App\verify_csrf($req);
  return $handler->handle($req);
});
