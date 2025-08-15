<?php
declare(strict_types=1);

namespace App\Models;

class MediaModel {
  public function byProject(int $pid): array {
    $st = \App\pdo()->prepare("SELECT * FROM project_media WHERE project_id=? ORDER BY sort_order,id");
    $st->execute([$pid]); $rows=$st->fetchAll();
    foreach($rows as &$r){
      if ($r['type']==='image'){ $r['url'] = '/portfolio/api/public/media/'.basename($r['path']); }
      elseif ($r['type']==='video_local'){ $r['url'] = $r['path']; }
    }
    return $rows;
  }
  public function createImage(int $pid, string $path): int {
    $st = \App\pdo()->prepare("INSERT INTO project_media(project_id,type,path,caption,sort_order,created_at) VALUES(?,?,?,?,?,?)");
    $st->execute([$pid,'image',$path,null,9999,date('Y-m-d H:i:s')]);
    return (int)\App\pdo()->lastInsertId();
  }
  public function createExternal(int $pid, string $url): int {
    $st = \App\pdo()->prepare("INSERT INTO project_media(project_id,type,external_url,caption,sort_order,created_at) VALUES(?,?,?,?,?,?)");
    $st->execute([$pid,'video_external',$url,null,9999,date('Y-m-d H:i:s')]);
    return (int)\App\pdo()->lastInsertId();
  }
  public function update(int $id, array $b): void {
    $fields=[]; $p=[];
    foreach(['caption','sort_order','path','external_url'] as $k){
      if (array_key_exists($k,$b)){ $fields[]="$k=?"; $p[]=$b[$k]; }
    }
    if (!$fields) return;
    $p[]=$id;
    \App\pdo()->prepare("UPDATE project_media SET ".implode(',',$fields)." WHERE id=?")->execute($p);
  }
  public function delete(int $id): void {
    \App\pdo()->prepare("DELETE FROM project_media WHERE id=?")->execute([$id]);
  }
  public function sort(array $arr): void {
    $st = \App\pdo()->prepare("UPDATE project_media SET sort_order=? WHERE id=?");
    foreach($arr as $r){ $st->execute([(int)$r['sort_order'], (int)$r['id']]); }
  }
}
