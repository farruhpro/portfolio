<?php
declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface as Response;

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

$errorMiddleware = $app->addErrorMiddleware(($_ENV['APP_DEBUG']??'false')==='true', true, true);

// CORS для одного домена (тот же хост)
$app->add(function(Request $req, Handler $h): Response {
  $res = $h->handle($req);
  return $res
    ->withHeader('Access-Control-Allow-Origin', $req->getHeaderLine('Origin') ?: ($_ENV['APP_URL'] ?? 'http://localhost'))
    ->withHeader('Access-Control-Allow-Credentials', 'true')
    ->withHeader('Access-Control-Allow-Headers', 'Content-Type, X-CSRF-Token')
    ->withHeader('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,OPTIONS');
});
