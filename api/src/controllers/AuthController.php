<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController {
public function login(Request $req, Response $res){
  $b = (array)$req->getParsedBody();
  $u = trim($b['username'] ?? '');
  $p = (string)($b['password'] ?? '');

  // достаём пользователя
  $stmt = \App\pdo()->prepare("SELECT id,username,password_hash,role,created_at,updated_at FROM users WHERE username=? LIMIT 1");
  $stmt->execute([$u]);
  $user = $stmt->fetch();

  $ok = false;
  if ($user) {
    $stored = (string)($user['password_hash'] ?? '');
    // если в БД хеш bcrypt ($2y$...), сверяем через password_verify
    if ($stored !== '' && substr($stored, 0, 4) === '$2y$') {
      $ok = password_verify($p, $stored);
    } else {
      // иначе считаем, что в колонке лежит простой пароль (DEV-режим)
      $ok = hash_equals($stored, $p);
    }
  }

  if (!$ok) {
    // временно отключаем блокировку по попыткам, чтобы не мешала диагностике
    return \App\json($res, ['ok'=>false,'error'=>'Invalid credentials'], 401);
  }

  $_SESSION['user'] = [
    'id' => $user['id'],
    'username' => $user['username'],
    'role' => $user['role']
  ];
  $csrf = \App\csrf_token();
  return \App\json($res, ['ok'=>true, 'csrf'=>$csrf, 'user'=>$_SESSION['user']]);
}
  public function logout(Request $req, Response $res){
    session_destroy();
    return \App\json($res, ['ok'=>true]);
  }
}
