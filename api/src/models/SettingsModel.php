<?php
declare(strict_types=1);

namespace App\Models;

class SettingsModel {
  public function get(): array {
    $row = \App\pdo()->query("SELECT * FROM site_settings WHERE id=1")->fetch() ?: [];
    $row['socials'] = $row['socials'] ? json_decode($row['socials'], true) : [];
    return $row;
  }
  public function save(array $b): void {
    $st = \App\pdo()->prepare("UPDATE site_settings SET hero_video_path=?, hero_poster_path=?, header_title=?, footer_text=?, email=?, phone=?, socials=? WHERE id=1");
    $st->execute([
      $b['hero_video_path'] ?? null,
      $b['hero_poster_path'] ?? null,
      $b['header_title'] ?? null,
      $b['footer_text'] ?? null,
      $b['email'] ?? null,
      $b['phone'] ?? null,
      json_encode($b['socials'] ?? [], JSON_UNESCAPED_UNICODE)
    ]);
  }
}
