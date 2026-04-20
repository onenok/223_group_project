<?php
session_start();
require_once('connect.php');

header('Content-Type: application/json');

// 檢查用戶是否登入
if (empty($_SESSION['login'])) {
    echo json_encode(['success' => false, 'error' => '請先登入']);
    exit;
}

// 獲取會員ID
$member_id = $_GET['member_id'] ?? 0;
if (!$member_id) {
    echo json_encode(['success' => false, 'error' => '缺少會員ID']);
    exit;
}

// 獲取會員資訊
$member_res = safeQuery('SELECT * FROM member WHERE Member_id = ?', 'i', [$member_id]);
if (!$member_res->success || !$member_res->result || $member_res->result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => '會員不存在']);
    exit;
}
$member = $member_res->result->fetch_assoc();

// 獲取會員的購買記錄
$purchase_records = [];
$purchase_res = safeQuery('SELECT pr.*, p.product_id, p.product_name, p.type 
                          FROM purchase_records pr 
                          JOIN products p ON pr.product_id = p.product_id 
                          WHERE pr.Member_id = ?
                          ORDER BY pr.created_at DESC', 'i', [$member_id]);
if ($purchase_res->success) {
    $purchase_records = $purchase_res->result->fetch_all(MYSQLI_ASSOC);
}

// 分析會員的購物習慣
$suggestions = [];
if (!empty($purchase_records)) {
    // 統計會員購買的類型偏好
    $type_preferences = [];
    foreach ($purchase_records as $record) {
        if (!isset($type_preferences[$record['type']])) {
            $type_preferences[$record['type']] = 0;
        }
        $type_preferences[$record['type']] += 1;
    }

    // 找出最常購買的類型
    arsort($type_preferences);
    $preferred_types = array_keys($type_preferences);

    // 獲取該類型中會員還沒買過的熱門商品
    $suggested_products = [];
    foreach ($preferred_types as $type) {
        // 獲取該類型中會員沒買過的商品
        $sql = "SELECT p.* FROM products p
                WHERE p.type = ? AND p.product_id NOT IN (
                    SELECT product_id FROM purchase_records WHERE Member_id = ?
                )
                ORDER BY (SELECT COALESCE(SUM(qty), 0) FROM purchase_records pr 
                         WHERE pr.product_id = p.product_id) DESC
                LIMIT 5";
        $type_res = safeQuery($sql, 'si', [$type, $member_id]);
        if ($type_res->success && $type_res->result) {
            $type_products = $type_res->result->fetch_all(MYSQLI_ASSOC);
            foreach ($type_products as $product) {
                $suggested_products[] = $product;
            }
        }
    }

    // 計算推薦分數（基於類型偏好和熱門程度）
    $product_scores = [];
    foreach ($suggested_products as $product) {
        $type = $product['type'];
        $score = $type_preferences[$type] * 1.5; // 類型偏好加權
        // 獲取該產品的總銷售量
        $sales_res = safeQuery('SELECT COALESCE(SUM(qty), 0) as total_sales 
                               FROM purchase_records 
                               WHERE product_id = ?', 'i', [$product['product_id']]);
        $total_sales = 0;
        if ($sales_res->success && $sales_res->result && $sales_res->result->num_rows > 0) {
            $row = $sales_res->result->fetch_assoc();
            $total_sales = $row['total_sales'];
        }
        $score += $total_sales * 0.5; // 熱門程度加權
        $product_scores[$product['product_id']] = $score;
    }

    // 排序並取得前5個推薦
    arsort($product_scores);
    $top_products = array_slice($product_scores, 0, 5, true);

    // 獲取產品詳細資訊
    if (!empty($top_products)) {
        $placeholders = implode(',', array_fill(0, count($top_products), '?'));
        $types = str_repeat('i', count($top_products));
        $product_ids = array_keys($top_products);
        $sug_res = safeQuery("SELECT * FROM products WHERE product_id IN ($placeholders)", $types, $product_ids);
        if ($sug_res->success) {
            $sug_products = $sug_res->result->fetch_all(MYSQLI_ASSOC);
            foreach ($sug_products as $sug) {
                $suggestions[] = [
                    'product_id' => $sug['product_id'],
                    'product_name' => $sug['product_name'],
                    'score' => $top_products[$sug['product_id']]
                ];
            }
        }
    }
}

echo json_encode([
    'success' => true,
    'suggestions' => $suggestions
]);