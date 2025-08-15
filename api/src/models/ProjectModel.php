<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class ProjectModel {
  public function list(int $page, int $limit, ?string $status, string $search, string $tag): array {
    $pdo = \App\pdo();
    $off = ($page-1)*$limit;
    $w=[]; $p=[];
    if ($status) { $w[]="p.status=?"; $p[]=$status; }
    if ($search!==''){ $w[]="p.title LIKE ?"; $p[]='%'.$search.'%'; }
    if ($tag!==''){ $w[]="p.id IN (SELECT pt.project_id FROM project_tags pt JOIN tags t ON t.id=pt.tag_id WHERE t.slug=?)"; $p[]=$tag; }
    $where = $w?('WHERE '.implode(' AND ',$w)):'';
    $sql = "SELECT p.* FROM projects p $where ORDER BY p.sort_order ASC, p.id DESC LIMIT $limit OFFSET $off";
    $items = $pdo->prepare($sql); $items->execute($p); $items = $items->fetchAll();
    $cnt = $pdo->prepare("SELECT COUNT(*) c FROM projects p $where"); $cnt->execute($p); $total = (int)$cnt->fetch()['c'];
    return ['items'=>$items,'total'=>$total,'page'=>$page,'limit'=>$limit];
  }
  public function create(array $b): int {
    $st = \App\pdo()->prepare("INSERT INTO projects(title,slug,description,client,art_director,designer,cover_image,status,sort_order,published_at,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)");
    $now = date('Y-m-d H:i:s');
    $st->execute([
      $b['title'] ?? 'Без названия',
      $b['slug'] ?? uniqid('prj-'),
      $b['description'] ?? '',
      $b['client'] ?? null,
      $b['art_director'] ?? null,
      $b['designer'] ?? null,
      $b['cover_image'] ?? null,
      $b['status'] ?? 'draft',
      $b['sort_order'] ?? 9999,
      null, $now, $now
    ]);
    return (int)\App\pdo()->lastInsertId();
  }
  public function update(int $id, array $b): void {
    $fields=[]; $p=[];
    foreach(['title','slug','description','client','art_director','designer','cover_image','status','sort_order'] as $k){
      if (array_key_exists($k,$b)){ $fields[]="$k=?"; $p[]=$b[$k]; }
    }
    if (!$fields) return;
    $p[] = date('Y-m-d H:i:s'); $p[]=$id;
    $sql = "UPDATE projects SET ".implode(',',$fields).", updated_at=? WHERE id=?";
    \App\pdo()->prepare($sql)->execute($p);
  }
  public function delete(int $id): void {
    \App\pdo()->prepare("DELETE FROM project_media WHERE project_id=?")->execute([$id]);
    \App\pdo()->prepare("DELETE FROM project_tags WHERE project_id=?")->execute([$id]);
    \App\pdo()->prepare("DELETE FROM projects WHERE id=?")->execute([$id]);
  }
  public function setStatus(int $id, string $status): void {
    $pub = $status==='published' ? date('Y-m-d H:i:s') : null;
    \App\pdo()->prepare("UPDATE projects SET status=?, published_at=?, updated_at=? WHERE id=?")
      ->execute([$status, $pub, date('Y-m-d H:i:s'), $id]);
  }
  public function bySlug(string $slug): ?array {
    $st = \App\pdo()->prepare("SELECT * FROM projects WHERE slug=? LIMIT 1");
    $st->execute([$slug]); $r=$st->fetch(); return $r?:null;
  }
  public function adjacentSlugs(int $id): array {
    $pdo=\App\pdo();
    $prev = $pdo->prepare("SELECT slug FROM projects WHERE id < ? ORDER BY id DESC LIMIT 1"); $prev->execute([$id]); $prev = $prev->fetch()['slug'] ?? null;
    $next = $pdo->prepare("SELECT slug FROM projects WHERE id > ? ORDER BY id ASC LIMIT 1"); $next->execute([$id]); $next = $next->fetch()['slug'] ?? null;
    return ['prev'=>$prev,'next'=>$next];
  }
  public function sort(array $arr): void {
    $pdo = \App\pdo(); $pdo->beginTransaction();
    $st = $pdo->prepare("UPDATE projects SET sort_order=? WHERE id=?");
    foreach($arr as $row){ $st->execute([(int)$row['sort_order'], (int)$row['id']]); }
    $pdo->commit();
  }
}
