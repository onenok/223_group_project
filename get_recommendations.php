<?php
session_start();
require_once('connect.php');

header('Content-Type: application/json');

// 檢查用戶是否登入
if (empty($_SESSION['login'])) {
    echo json_encode(['success' => false, 'error' => '請先登入']);
    exit;
}

// 獲取產品ID
$product_id = $_GET['product_id'] ?? 0;
if (!$product_id) {
    echo json_encode(['success' => false, 'error' => '缺少產品ID']);
    exit;
}

// 獲取目標產品資訊
$product_res = safeQuery('SELECT * FROM products WHERE product_id = ?', 'i', [$product_id]);
if (!$product_res->success || !$product_res->result || $product_res->result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => '產品不存在']);
    exit;
}
$product = $product_res->result->fetch_assoc();

// 獲取購買記錄來分析關聯規則
$purchase_records = [];
$purchase_res = safeQuery('SELECT pr.*, p.product_id, p.product_name 
                          FROM purchase_records pr 
                          JOIN products p ON pr.product_id = p.product_id 
                          ORDER BY pr.created_at DESC');
if ($purchase_res->success) {
    $purchase_records = $purchase_res->result->fetch_all(MYSQLI_ASSOC);
}

// 分析關聯規則（簡化版）
$recommendations = [];
if (!empty($purchase_records)) {
    // 建立交易資料集
    $transactions = [];
    foreach ($purchase_records as $record) {
        $transactions[$record['Member_id']][] = $record['product_id'];
    }

    // 找出包含目標產品的交易
    $target_transactions = [];
    foreach ($transactions as $member_id => $products) {
        if (in_array($product_id, $products)) {
            $target_transactions[] = $products;
        }
    }

    // 計算推薦分數
    $product_scores = [];
    foreach ($target_transactions as $transaction) {
        foreach ($transaction as $pid) {
            if ($pid != $product_id) {
                if (!isset($product_scores[$pid])) {
                    $product_scores[$pid] = 0;
                }
                $product_scores[$pid] += 1;
            }
        }
    }

    // 排序並取得前5個推薦
    arsort($product_scores);
    $top_products = array_slice($product_scores, 0, 5, true);

    // 獲取產品詳細資訊
    if (!empty($top_products)) {
        $placeholders = implode(',', array_fill(0, count($top_products), '?'));
        $types = str_repeat('i', count($top_products));
        $product_ids = array_keys($top_products);
        $rec_res = safeQuery("SELECT * FROM products WHERE product_id IN ($placeholders)", $types, $product_ids);
        if ($rec_res->success) {
            $rec_products = $rec_res->result->fetch_all(MYSQLI_ASSOC);
            foreach ($rec_products as $rec) {
                $recommendations[] = [
                    'product_id' => $rec['product_id'],
                    'product_name' => $rec['product_name'],
                    'score' => $top_products[$rec['product_id']]
                ];
            }
        }
    }
}

echo json_encode([
    'success' => true,
    'recommendations' => $recommendations
]);