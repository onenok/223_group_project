<?php
session_start();
require_once('connect.php');

header('Content-Type: application/json');

// 檢查用戶是否登入
if (empty($_SESSION['login']) || empty($_SESSION['member_id'])) {
    echo json_encode(['success' => false, 'error' => '請先登入']);
    exit;
}

$memberID = $_SESSION['member_id'];
$product_id = $_GET['product_id'] ?? 0;

if (!$product_id) {
    echo json_encode(['success' => false, 'error' => '缺少產品ID']);
    exit;
}

/**
 * 推薦邏輯更新：
 * 1. 透過 JOIN 找出買過 $product_id 的會員買過的其他商品。
 * 2. 排除商品 A 本身。
 * 3. 使用子查詢 (NOT IN) 排除該會員目前購物車中已有的商品。
 */
$recommendations = [];

$sql = "SELECT p.product_id, p.product_name, p.price, COUNT(*) as association_score
        FROM purchase_records pr1
        JOIN purchase_records pr2 ON pr1.Member_id = pr2.Member_id
        JOIN products p ON pr2.product_id = p.product_id
        WHERE pr1.product_id = ?        /* 1. 目標商品 */
        AND pr2.product_id <> ?         /* 2. 排除目標商品本身 */
        AND p.product_id NOT IN (       /* 3. 排除已在購物車的商品 */
            SELECT product_id 
            FROM cart 
            WHERE member_id = ?
        )
        GROUP BY p.product_id
        ORDER BY association_score DESC
        LIMIT 5";

// 注意：這裡參數變成了三個 i (product_id, product_id, memberID)
$res = safeQuery($sql, 'iii', [$product_id, $product_id, $memberID]);

if ($res->success && $res->result) {
    while ($row = $res->result->fetch_assoc()) {
        $recommendations[] = [
            'product_id'   => $row['product_id'],
            'product_name' => $row['product_name'],
            'price'        => $row['price'],
            'score'        => $row['association_score']
        ];
    }
}

echo json_encode([
    'success' => true,
    'recommendations' => $recommendations
]);