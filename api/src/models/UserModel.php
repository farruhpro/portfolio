<?php
declare(strict_types=1);

namespace App\Models;

class UserModel {
  public function all(): array {
    $st = \App\pdo()->query("SELECT id,username,role,created_at FROM users ORDER BY id");
    return $st->fetchAll();
  }
  public function setPassword(int $id, string $password): void {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $st = \App\pdo()->prepare("UPDATE users SET password_hash=?,updated_at=? WHERE id=?");
    $st->execute([$hash, date('Y-m-d H:i:s'), $id]);
  }
}
