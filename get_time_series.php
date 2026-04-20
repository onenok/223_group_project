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
$time_range = $_GET['time_range'] ?? 'all'; // 預設顯示所有資料
$filter_type = $_GET['filter_type'] ?? 'all'; // 預設顯示所有類型
$product_ids = $_GET['product_ids'] ?? []; // 預設顯示所有產品

// 處理時間範圍
$end_date = new DateTime();
$start_date = clone $end_date;

switch ($time_range) {
    case '1d':
        $start_date->modify('-1 day');
        break;
    case '1w':
        $start_date->modify('-1 week');
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
        $start_date = null; // 不設置開始時間表示所有時間
        break;
}

// 構建查詢條件
$where_conditions = [];
$params = [];
$types = '';

// 時間條件
if ($start_date) {
    $where_conditions[] = "pr.created_at BETWEEN ? AND ?";
    $params[] = $start_date->format('Y-m-d H:i:s');
    $params[] = $end_date->format('Y-m-d H:i:s');
    $types .= 'ss';
}

// 類型條件
if ($filter_type !== 'all') {
    $where_conditions[] = "p.type = ?";
    $params[] = $filter_type;
    $types .= 's';
}

// 產品條件
if (!empty($product_ids)) {
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $where_conditions[] = "p.product_id IN ($placeholders)";
    $params = array_merge($params, $product_ids);
    $types .= str_repeat('i', count($product_ids));
}

// 構建完整的 SQL 查詢
$sql = "SELECT 
            p.product_id,
            p.product_name,
            p.type,
            DATE(pr.created_at) as purchase_date,
            SUM(pr.qty) as total_qty,
            COUNT(pr.records_id) as transaction_count
        FROM products p
        LEFT JOIN purchase_records pr ON p.product_id = pr.product_id";

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(' AND ', $where_conditions);
}

$sql .= " GROUP BY p.product_id, p.product_name, p.type, DATE(pr.created_at)
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
    // 建立時間序列框架
    $current_date = $start_date ? clone $start_date : null;
    $end_date_obj = $end_date;
    
    while ($current_date === null || $current_date <= $end_date_obj) {
        $date_str = $current_date ? $current_date->format('Y-m-d') : '總計';
        
        foreach ($records as $record) {
            $product_id = $record['product_id'];
            $purchase_date = $record['purchase_date'] ?? '總計';
            
            if ($date_str === $purchase_date || $date_str === '總計') {
                if (!isset($time_series_data[$product_id])) {
                    $time_series_data[$product_id] = [
                        'product_id' => $product_id,
                        'product_name' => $record['product_name'],
                        'type' => $record['type'],
                        'data' => []
                    ];
                }
                
                if (!isset($time_series_data[$product_id]['data'][$date_str])) {
                    $time_series_data[$product_id]['data'][$date_str] = [
                        'date' => $date_str,
                        'total_qty' => 0,
                        'transaction_count' => 0
                    ];
                }
                
                $time_series_data[$product_id]['data'][$date_str]['total_qty'] += $record['total_qty'];
                $time_series_data[$product_id]['data'][$date_str]['transaction_count'] += $record['transaction_count'];
            }
        }
        
        if ($current_date !== null) {
            $current_date->modify('+1 day');
            if ($current_date > $end_date_obj) {
                break;
            }
        } else {
            break;
        }
    }
    
    // 準備產品資訊
    foreach ($time_series_data as $product_id => $product_data) {
        $product_info[$product_id] = [
            'product_id' => $product_id,
            'product_name' => $product_data['product_name'],
            'type' => $product_data['type']
        ];
    }
}

echo json_encode([
    'success' => true,
    'time_range' => $time_range,
    'filter_type' => $filter_type,
    'product_ids' => $product_ids,
    'time_series_data' => array_values($time_series_data),
    'product_info' => $product_info
]);