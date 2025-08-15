<?php
declare(strict_types=1);

namespace App\Services;

use Intervention\Image\ImageManagerStatic as Image;

class ImageService {
  public function store(array $file): string {
    if (($file['error'] ?? UPLOAD_ERR_OK)!==UPLOAD_ERR_OK) throw new \RuntimeException('Upload error');
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, ['image/jpeg','image/png','image/webp'])) throw new \RuntimeException('Invalid mime');
    $dir = dirname(__DIR__,2).'/storage/images';
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $base = bin2hex(random_bytes(8));
    $full = "$dir/{$base}.webp";
    $img = Image::make($file['tmp_name']);
    $img->encode('webp', 88)->save($full);
    // превью
    foreach([400,800,1200,1600] as $w){
      $img2 = Image::make($file['tmp_name']); $img2->resize($w, null, function($c){$c->aspectRatio(); $c->upsize();});
      $img2->encode('webp', 84)->save("$dir/{$base}_{$w}.webp");
    }
    return $base.'.webp'; // имя файла (путь хранится как имя, раздаём через контроллер)
  }
}
