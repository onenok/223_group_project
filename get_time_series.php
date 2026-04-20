<?php
session_start();
require_once('connect.php');

header('Content-Type: application/json');

// 檢查用戶是否登入
if (empty($_SESSION['login'])) {
  echo json_encode(['success' => false, 'error' => '請先登入']);
  exit;
}

// 獲取參數
$time_range = $_GET['time_range'] ?? 'all';
$filter_type = $_GET['filter_type'] ?? 'all';

// 處理時間範圍
$end_date = new DateTime();
$start_date = clone $end_date;

switch ($time_range) {
  case '1d':
    $start_date->modify('-1 day');
    break;
  case '5d':
    $start_date->modify('-5 day');
    break;
  case '1m':
    $start_date->modify('-1 month');
    break;
  case '1y':
    $start_date->modify('-1 year');
    break;
  case '5y':
    $start_date->modify('-5 years');
    break;
  case 'all':
  default:
    $start_date = null;
    break;
}

if ($time_range === 'all') {
  $min_date_res = $conn->query("SELECT MIN(created_at) FROM purchase_records");
  $min_date_val = $min_date_res->fetch_row()[0];
  if ($min_date_val) {
    $start_date = new DateTime($min_date_val);
    $start_date->modify('-1 day');
  } else {
    $start_date = clone $end_date; // 如果沒資料就用現在
  }
}

// 構建查詢條件
$where_conditions = [];
$params = [];
$types = '';

if ($time_range !== 'all' && $start_date) {
  $where_conditions[] = "pr.created_at BETWEEN ? AND ?";
  $params[] = $start_date->format('Y-m-d H:i:s');
  $params[] = $end_date->format('Y-m-d H:i:s');
  $types .= 'ss';
}

if ($filter_type !== 'all') {
  $where_conditions[] = "p.type = ?";
  $params[] = $filter_type;
  $types .= 's';
}

// 根據時間範圍決定格式
$isHourly = ($time_range == "1d" || $time_range == "5d");
$dateFormat = $isHourly ? "DATE_FORMAT(pr.created_at,'%Y-%m-%d %H')" : "DATE(pr.created_at)";

$sql = "SELECT 
          p.product_id,
          p.product_name,
          p.type,
          $dateFormat as purchase_date,
          SUM(pr.qty) as total_qty,
          COUNT(pr.records_id) as transaction_count
          FROM products p
          JOIN purchase_records pr ON p.product_id = pr.product_id";

if (!empty($where_conditions)) {
  $sql .= " WHERE " . implode(' AND ', $where_conditions);
}

$sql .= " GROUP BY p.product_id, p.product_name, p.type, purchase_date
          ORDER BY purchase_date ASC, p.product_name ASC";

// 執行查詢
$response = safeQuery($sql, $types, $params);
if (!$response->success) {
  echo json_encode(['success' => false, 'error' => '資料查詢失敗: ' . $response->error]);
  exit;
}

// 處理查詢結果
$records = $response->result->fetch_all(MYSQLI_ASSOC);

// 組織時間序列資料
$time_series_data = [];
$product_info = [];

if (!empty($records)) {
  // 1. 先將 SQL 數據按產品 ID 歸類
  foreach ($records as $record) {
    $p_id = $record['product_id'];
    $p_date = $record['purchase_date'];

    if (!isset($time_series_data[$p_id])) {
      $time_series_data[$p_id] = [
        'product_id' => $p_id,
        'product_name' => $record['product_name'],
        'type' => $record['type'],
        'data' => []
      ];
      $product_info[$p_id] = [
        'product_id' => $p_id,
        'product_name' => $record['product_name'],
        'type' => $record['type']
      ];
    }

    // 直接使用 SQL 的日期作為 Key，避免 PHP 格式不對的問題
    $time_series_data[$p_id]['data'][$p_date] = [
      'date' => $p_date,
      'total_qty' => (int)$record['total_qty'],
      'transaction_count' => (int)$record['transaction_count']
    ];
  }

  // 2. 補齊缺失的時間點 (避免圖表斷掉)
  $current = clone $start_date;
  $format = $isHourly ? 'Y-m-d H' : 'Y-m-d';
  $modifyStr = $isHourly ? '+1 hour' : '+1 day';

  while ($current <= $end_date) {
    $date_key = $current->format($format);
    foreach ($time_series_data as &$p_data) {
      if (!isset($p_data['data'][$date_key])) {
        $p_data['data'][$date_key] = [
          'date' => $date_key,
          'total_qty' => 0,
          'transaction_count' => 0
        ];
      }
    }
    $current->modify($modifyStr);
  }
}

// 3. 排序每個產品的數據，確保時間軸由舊到新
foreach ($time_series_data as &$p_data) {
  ksort($p_data['data']);
}
$count_res = $conn->query("SELECT COUNT(*) FROM purchase_records");
$row = $count_res->fetch_row();
echo json_encode([
  'end_date' => $end_date,
  'success' => true,
  'time_range' => $time_range,
  'time_series_data' => array_values($time_series_data),
  'product_info' => $product_info,
  'sql' => $response->executed_sql,
  'aa' => $row[0] // 加入 JSON
]);
