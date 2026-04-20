<?php
// 測試管理頁面功能
echo "測試管理頁面功能...\n";

// 測試1: 檢查管理頁面是否可訪問
echo "1. 檢查管理頁面是否可訪問...\n";
$admin_page = @file_get_contents('admin.php');
if ($admin_page) {
    echo "   ✓ 管理頁面可訪問\n";
} else {
    echo "   ✗ 管理頁面無法訪問\n";
}

// 測試2: 檢查互補商品推薦功能
echo "2. 檢查互補商品推薦功能...\n";
$recommendation_test = @file_get_contents('get_recommendations.php?product_id=8');
if ($recommendation_test) {
    $data = json_decode($recommendation_test, true);
    if (isset($data['success']) && $data['success']) {
        echo "   ✓ 互補商品推薦功能正常\n";
        if (!empty($data['recommendations'])) {
            echo "      - 找到 " . count($data['recommendations']) . " 個推薦商品\n";
        }
    } else {
        echo "   ✗ 互補商品推薦功能異常: " . ($data['error'] ?? '未知錯誤') . "\n";
    }
} else {
    echo "   ✗ 互補商品推薦功能無法訪問\n";
}

// 測試3: 檢查建議清單功能
echo "3. 檢查建議清單功能...\n";
$suggestion_test = @file_get_contents('get_suggestions.php?member_id=1');
if ($suggestion_test) {
    $data = json_decode($suggestion_test, true);
    if (isset($data['success']) && $data['success']) {
        echo "   ✓ 建議清單功能正常\n";
        if (!empty($data['suggestions'])) {
            echo "      - 找到 " . count($data['suggestions']) . " 個建議商品\n";
        }
    } else {
        echo "   ✗ 建議清單功能異常: " . ($data['error'] ?? '未知錯誤') . "\n";
    }
} else {
    echo "   ✗ 建議清單功能無法訪問\n";
}

// 測試4: 檢查資料庫連線
echo "4. 檢查資料庫連線...\n";
$conn_test = @include 'connect.php';
if ($conn_test) {
    echo "   ✓ 資料庫連線正常\n";
} else {
    echo "   ✗ 資料庫連線異常\n";
}

// 測試5: 檢查Chart.js是否載入成功
echo "5. 檢查Chart.js是否載入成功...\n";
$chart_js_test = @file_get_contents('https://cdn.jsdelivr.net/npm/chart.js');
if ($chart_js_test) {
    echo "   ✓ Chart.js載入成功\n";
} else {
    echo "   ✗ Chart.js載入失敗\n";
}

echo "測試完成！\n";