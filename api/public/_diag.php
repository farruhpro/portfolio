<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$out = [];
try {
  $pdo = \App\pdo();
  $db = $pdo->query("SELECT DATABASE() AS db")->fetch()['db'] ?? null;
  $cnt = (int)($pdo->query("SELECT COUNT(*) c FROM users")->fetch()['c'] ?? 0);
  $row = $pdo->query("SELECT id,username,password_hash,role FROM users ORDER BY id LIMIT 1")->fetch();

  $out = [
    'connected_db' => $db,
    'users_count'  => $cnt,
    'first_user'   => $row,
  ];
} catch (\Throwable $e) {
  $out = ['error'=>$e->getMessage()];
}
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
