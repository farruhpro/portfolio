<?php
declare(strict_types=1);

namespace App\Models;

class StatsModel {
  public function summary(string $range): array {
    $days = match($range){ '90d'=>90, '30d'=>30, default=>7 };
    $st = \App\pdo()->prepare("SELECT DATE(created_at) d, COUNT(*) c FROM stats_pageviews WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY) GROUP BY DATE(created_at) ORDER BY d");
    $st->execute([$days]);
    $rows = $st->fetchAll();
    $total = array_sum(array_column($rows,'c'));
    return ['total'=>$total,'by_day'=>array_map(fn($r)=>['day'=>$r['d'],'count'=>(int)$r['c']], $rows)];
  }
}
