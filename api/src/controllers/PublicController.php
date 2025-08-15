<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as v;
use App\Models\{ProjectModel, SettingsModel, StatsModel, MediaModel};

class PublicController {
  public function settings(Request $req, Response $res){
    $s = (new SettingsModel())->get();
    return \App\json($res, $s);
  }
  public function projects(Request $req, Response $res){
    $p = $req->getQueryParams();
    $page = max(1, (int)($p['page'] ?? 1));
    $limit = min(50, max(1, (int)($p['limit'] ?? 12)));
    $status = ($p['status'] ?? 'published') === 'published' ? 'published' : 'draft';
    $search = trim($p['search'] ?? '');
    $tag = trim($p['tag'] ?? '');
    $m = new ProjectModel();
    $data = $m->list($page,$limit,$status,$search,$tag);
    // добавить cover_url
    foreach($data['items'] as &$it){ $it['cover_url'] = $it['cover_image'] ?: null; }
    return \App\json($res, $data);
  }
  public function projectBySlug(Request $req, Response $res, array $args){
    $slug = $args['slug'];
    $m = new ProjectModel();
    $p = $m->bySlug($slug);
    if (!$p) return \App\json($res, ['error'=>'Not found'], 404);
    $p['cover_url'] = $p['cover_image'] ?: null;
    $p['media'] = (new MediaModel())->byProject((int)$p['id']);
    $adj = $m->adjacentSlugs((int)$p['id']);
    $p['prev_slug'] = $adj['prev'] ?? null; $p['next_slug'] = $adj['next'] ?? null;
    $p['description_html'] = $p['description']; // доверяем только своему контенту
    return \App\json($res, $p);
  }
  public function contact(Request $req, Response $res){
    $b = (array)$req->getParsedBody();
    $name = trim($b['name'] ?? ''); $email = trim($b['email'] ?? ''); $msg = trim($b['message'] ?? '');
    $hp = trim($b['website'] ?? '');
    if ($hp!=='') return \App\json($res, ['ok'=>true]); // honeypot
    if (!v::stringType()->length(2, 200)->validate($name)) return \App\json($res, ['ok'=>false,'err'=>'name'],422);
    if (!v::email()->validate($email)) return \App\json($res, ['ok'=>false,'err'=>'email'],422);
    if (!v::stringType()->length(5, 4000)->validate($msg)) return \App\json($res, ['ok'=>false,'err'=>'message'],422);
    $pdo = \App\pdo();
    $stmt = $pdo->prepare("INSERT INTO contact_messages(name,email,message,created_at) VALUES(?,?,?,?)");
    $stmt->execute([$name,$email,$msg,date('Y-m-d H:i:s')]);
    return \App\json($res, ['ok'=>true]);
  }
  public function track(Request $req, Response $res){
    $b = (array)$req->getParsedBody();
    $path = substr(trim($b['path'] ?? '/'),0,512);
    $projSlug = $b['project_slug'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $salt = $_ENV['CSRF_SECRET'] ?? 'salt';
    $ipHash = hash('sha256', $salt.$ip);
    $uaHash = hash('sha256', $salt.$ua);
    $pid = null;
    if ($projSlug) {
      $row = \App\pdo()->prepare("SELECT id FROM projects WHERE slug=?")->execute([$projSlug]);
    }
    $pdo = \App\pdo();
    $stmt = $pdo->prepare("INSERT INTO stats_pageviews(path,project_id,ip_hash,ua_hash,referrer,created_at) VALUES(?,?,?,?,?,?)");
    $stmt->execute([$path,$pid,$ipHash,$uaHash, ($_SERVER['HTTP_REFERER'] ?? null), date('Y-m-d H:i:s')]);
    return \App\json($res, ['ok'=>true]);
  }
  public function serveMedia(Request $req, Response $res, array $args){
    $name = basename($args['name']);
    $file = dirname(__DIR__,2)."/storage/images/".$name;
    if (!is_file($file)) { $res->getBody()->write('Not found'); return $res->withStatus(404); }
    $mime = mime_content_type($file) ?: 'application/octet-stream';
    $res = $res->withHeader('Content-Type', $mime);
    $res->getBody()->write(file_get_contents($file));
    return $res;
  }
}
