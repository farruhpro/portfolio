<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\{ProjectModel, MediaModel, StatsModel, SettingsModel, UserModel};
use App\Services\ImageService;

class AdminController {
  public function listProjects(Request $req, Response $res){
    $q = $req->getQueryParams();
    $m = new ProjectModel();
    $data = $m->list(1, 500, $q['status']??null, $q['search']??'', $q['tag']??'');
    // добавим медиа, если фильтруем по проекту
    if (!empty($q['project_id'])) {
      $pid = (int)$q['project_id'];
      foreach ($data['items'] as &$it) if ((int)$it['id']===$pid) $it['media']=(new MediaModel())->byProject($pid);
    }
    return \App\json($res, $data);
  }
  public function createProject(Request $req, Response $res){
    $b = (array)$req->getParsedBody();
    $id = (new ProjectModel())->create($b);
    return \App\json($res, ['id'=>$id]);
  }
  public function updateProject(Request $req, Response $res, array $args){
    $id = (int)$args['id']; $b = (array)$req->getParsedBody();
    (new ProjectModel())->update($id, $b);
    return \App\json($res, ['ok'=>true]);
  }
  public function deleteProject(Request $req, Response $res, array $args){
    (new ProjectModel())->delete((int)$args['id']);
    return \App\json($res, ['ok'=>true]);
  }
  public function publishProject(Request $req, Response $res, array $args){
    (new ProjectModel())->setStatus((int)$args['id'], 'published');
    return \App\json($res, ['ok'=>true]);
  }
  public function unpublishProject(Request $req, Response $res, array $args){
    (new ProjectModel())->setStatus((int)$args['id'], 'draft');
    return \App\json($res, ['ok'=>true]);
  }
  public function uploadMedia(Request $req, Response $res, array $args){
    $pid = (int)$args['id'];
    $b = $req->getParsedBody() ?? [];
    $type = $_POST['type'] ?? ($b['type'] ?? 'image');
    $m = new MediaModel();
    if ($type==='video_external'){
      $url = trim($b['external_url'] ?? $_POST['external_url'] ?? '');
      $m->createExternal($pid, $url);
      return \App\json($res, ['ok'=>true]);
    } else if ($type==='video_local'){
      // (опционально) — можно добавить отдельную загрузку локальных видео
      return \App\json($res, ['ok'=>false,'err'=>'video_local_not_implemented'],422);
    } else {
      // images multi-upload
      $imgs = $_FILES['files'] ?? null;
      if (!$imgs) return \App\json($res, ['ok'=>false,'err'=>'no_files'],422);
      $svc = new ImageService();
      $saved = [];
      for ($i=0; $i<count($imgs['name']); $i++){
        $tmp = [
          'name'=>$imgs['name'][$i],
          'type'=>$imgs['type'][$i],
          'tmp_name'=>$imgs['tmp_name'][$i],
          'error'=>$imgs['error'][$i],
          'size'=>$imgs['size'][$i],
        ];
        $path = $svc->store($tmp);
        $saved[] = $m->createImage($pid, $path);
      }
      return \App\json($res, ['ok'=>true,'saved'=>$saved]);
    }
  }
  public function updateMedia(Request $req, Response $res, array $args){
    $id = (int)$args['id']; $b=(array)$req->getParsedBody();
    (new MediaModel())->update($id, $b);
    return \App\json($res, ['ok'=>true]);
  }
  public function deleteMedia(Request $req, Response $res, array $args){
    (new MediaModel())->delete((int)$args['id']);
    return \App\json($res, ['ok'=>true]);
  }
  public function sortProjects(Request $req, Response $res){
    $arr = (array)$req->getParsedBody();
    (new ProjectModel())->sort($arr);
    return \App\json($res, ['ok'=>true]);
  }
  public function sortMedia(Request $req, Response $res){
    $arr = (array)$req->getParsedBody();
    (new MediaModel())->sort($arr);
    return \App\json($res, ['ok'=>true]);
  }
  public function statsSummary(Request $req, Response $res){
    $range = $req->getQueryParams()['range'] ?? '7d';
    $data = (new StatsModel())->summary($range);
    return \App\json($res, $data);
  }
  public function getSettings(Request $req, Response $res){
    return \App\json($res, (new SettingsModel())->get());
  }
  public function saveSettings(Request $req, Response $res){
    $b=(array)$req->getParsedBody(); (new SettingsModel())->save($b);
    return \App\json($res, ['ok'=>true]);
  }
  public function listUsers(Request $req, Response $res){
    return \App\json($res, ['items'=>(new UserModel())->all()]);
  }
  public function updateUser(Request $req, Response $res, array $args){
    $id=(int)$args['id']; $b=(array)$req->getParsedBody();
    if (!empty($b['password'])) (new UserModel())->setPassword($id, $b['password']);
    return \App\json($res, ['ok'=>true]);
  }
}
