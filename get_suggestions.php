<?php
session_start();
require_once('connect.php');

header('Content-Type: application/json');

if (empty($_SESSION['login'])) {
  echo json_encode(['success' => false, 'error' => '請先登入']);
  exit;
}

$member_id = $_GET['member_id'] ?? 0;
// 建議直接從 Session 拿 ID 更安全，避免 A 會員查 B 會員的推薦
// $member_id = $_SESSION['member_id']; 

// 1. 獲取會員的購買習慣 (只抓類型統計)
$type_pref_res = safeQuery(
  'SELECT p.type, COUNT(*) as buy_count 
     FROM purchase_records pr 
     JOIN products p ON pr.product_id = p.product_id 
     WHERE pr.Member_id = ? 
     GROUP BY p.type 
     ORDER BY buy_count DESC',
  'i',
  [$member_id]
);

$type_preferences = [];
if ($type_pref_res->success) {
  while ($row = $type_pref_res->result->fetch_assoc()) {
    $type_preferences[$row['type']] = $row['buy_count'];
  }
}

$suggestions = [];

// 2. 核心推薦邏輯
if (!empty($type_preferences)) {
  // 有購買紀錄：推薦偏好類型的熱門商品
  $preferred_types = array_keys($type_preferences);
  $placeholders = implode(',', array_fill(0, count($preferred_types), '?'));

  // 一次性抓取這些類型中，用戶沒買過的商品，並同時關聯銷售總量
  $sql = "SELECT p.*, COALESCE(SUM(pr_all.qty), 0) as total_sales
            FROM products p
            LEFT JOIN purchase_records pr_all ON p.product_id = pr_all.product_id
            WHERE p.type IN ($placeholders)
            AND p.product_id NOT IN (
                SELECT product_id FROM purchase_records WHERE Member_id = ?
            )
            GROUP BY p.product_id";

  // 準備參數：類型列表 + 會員ID
  $params = array_merge($preferred_types, [$member_id]);
  $param_types = str_repeat('s', count($preferred_types)) . 'i';

  $res = safeQuery($sql, $param_types, $params);

  if ($res->success) {
    $candidates = $res->result->fetch_all(MYSQLI_ASSOC);
    $scored_products = [];

    foreach ($candidates as $p) {
      // 計算分數：(類型購買次數 * 10) + (全站銷售量 * 0.5) + 隨機微調
      $type_score = $type_preferences[$p['type']] * 10;
      $popularity_score = $p['total_sales'] * 0.5;
      $random_factor = rand(0, 5);

      $total_score = $type_score + $popularity_score + $random_factor;
      $p['score'] = $total_score;
      $scored_products[] = $p;
    }

    // 按分數排序
    usort($scored_products, function ($a, $b) {
      return $b['score'] <=> $a['score'];
    });

    $suggestions = array_slice($scored_products, 0, 5);
  }
}

// 3. 冷啟動補償：如果推薦不足 5 個 (包含完全沒買過東西的新人)
if (count($suggestions) < 5) {
  $needed = 5 - count($suggestions);
  $exclude_ids = array_column($suggestions, 'product_id');
  // 如果已有商品，則排除它們避免重複
  $exclude_sql = !empty($exclude_ids) ? "AND p.product_id NOT IN (" . implode(',', array_fill(0, count($exclude_ids), '?')) . ")" : "";

  $fallback_sql = "SELECT p.*, COALESCE(SUM(pr.qty), 0) as total_sales 
                     FROM products p 
                     LEFT JOIN purchase_records pr ON p.product_id = pr.product_id 
                     WHERE 1=1 $exclude_sql
                     GROUP BY p.product_id 
                     ORDER BY total_sales DESC 
                     LIMIT $needed";

  $fallback_res = safeQuery($fallback_sql, str_repeat('i', count($exclude_ids)), $exclude_ids);
  if ($fallback_res->success) {
    while ($row = $fallback_res->result->fetch_assoc()) {
      $row['score'] = 0; // 保底商品分數設為 0
      $suggestions[] = $row;
    }
  }
}

echo json_encode([
  'success' => true,
  'suggestions' => $suggestions
]);
