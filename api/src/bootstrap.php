<?php
declare(strict_types=1);

namespace App;

use Dotenv\Dotenv;
use PDO;

$root = dirname(__DIR__, 1);
$dotenv = Dotenv::createImmutable(dirname(__DIR__,1));
$dotenv->safeLoad();

session_name('PORT_SESS');
session_set_cookie_params([
  'lifetime'=>0,'path'=>'/','httponly'=>true,'samesite'=>'Lax','secure'=>false
]);
if (session_status() === PHP_SESSION_NONE) session_start();

$container = \Slim\Factory\AppFactory::determineResponseFactory(); // no-op

// PDO (глобально через $pdo)
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
  $_ENV['DB_HOST']??'127.0.0.1', $_ENV['DB_PORT']??'3306', $_ENV['DB_DATABASE']??'portfolio');
$pdo = new PDO($dsn, $_ENV['DB_USERNAME']??'root', $_ENV['DB_PASSWORD']??'', [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// Если нет пользователей — создаём admin/admin
$exists = $pdo->query("SELECT COUNT(*) c FROM users")->fetch()['c'] ?? 0;
if ((int)$exists === 0) {
  $hash = password_hash('admin', PASSWORD_BCRYPT);
  $stmt = $pdo->prepare("INSERT INTO users(username,password_hash,role,created_at,updated_at) VALUES(?,?,?,?,?)");
  $now = date('Y-m-d H:i:s');
  $stmt->execute(['admin', $hash, 'admin', $now, $now]);
}

// вспомогательные функции доступные в require'ах
function pdo(): PDO { global $pdo; return $pdo; }
function json($res, $data, int $code=200){ $res->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE)); return $res->withHeader('Content-Type','application/json')->withStatus($code);}
function require_admin(){ if (!isset($_SESSION['user'])) { http_response_code(401); echo 'Unauthorized'; exit; } }
function csrf_token(): string {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = hash_hmac('sha256', bin2hex(random_bytes(16)), $_ENV['CSRF_SECRET']??'secret');
  }
  return $_SESSION['csrf'];
}
function verify_csrf($req): void {
  if (!in_array($req->getMethod(), ['POST','PUT','DELETE'])) return;
  $hdr = $req->getHeaderLine('X-CSRF-Token');
  if (!$hdr || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $hdr)) {
    http_response_code(419); echo 'CSRF token mismatch'; exit;
  }
}
