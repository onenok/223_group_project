<?php
// 測試時間軸圖表功能
echo "測試時間軸圖表功能...\n";

// 測試1: 檢查時間軸資料處理檔案是否可訪問
echo "1. 檢查時間軸資料處理檔案是否可訪問...\n";
$time_series_test = @file_get_contents('get_time_series.php?time_range=1d&filter_type=all&product_ids=all');
if ($time_series_test) {
    $data = json_decode($time_series_test, true);
    if (isset($data['success']) && $data['success']) {
        echo "   ✓ 時間軸資料處理檔案可訪問\n";
        if (!empty($data['time_series_data'])) {
            echo "      - 找到 " . count($data['time_series_data']) . " 個產品的時間序列資料\n";
        }
    } else {
        echo "   ✗ 時間軸資料處理檔案異常: " . ($data['error'] ?? '未知錯誤') . "\n";
    }
} else {
    echo "   ✗ 時間軸資料處理檔案無法訪問\n";
}

// 測試2: 檢查不同時間範圍的資料
echo "2. 檢查不同時間範圍的資料...\n";
$time_ranges = ['1d', '1w', '1m', '1y', '5y', 'all'];
foreach ($time_ranges as $range) {
    $test = @file_get_contents('get_time_series.php?time_range=' . $range . '&filter_type=all&product_ids=all');
    if ($test) {
        $data = json_decode($test, true);
        if (isset($data['success']) && $data['success']) {
            echo "   ✓ {$range} 時間範圍資料正常\n";
        } else {
            echo "   ✗ {$range} 時間範圍資料異常\n";
        }
    } else {
        echo "   ✗ {$range} 時間範圍資料無法訪問\n";
    }
}

// 測試3: 檢查篩選功能
echo "3. 檢查篩選功能...\n";
$filter_types = ['all', 'drinks', 'food', 'toy', 'e-things'];
foreach ($filter_types as $type) {
    $test = @file_get_contents('get_time_series.php?time_range=1m&filter_type=' . $type . '&product_ids=all');
    if ($test) {
        $data = json_decode($test, true);
        if (isset($data['success']) && $data['success']) {
            echo "   ✓ {$type} 類型篩選正常\n";
        } else {
            echo "   ✗ {$type} 類型篩選異常\n";
        }
    } else {
        echo "   ✗ {$type} 類型篩選無法訪問\n";
    }
}

// 測試4: 檢查產品篩選功能
echo "4. 檢查產品篩選功能...\n";
$product_test = @file_get_contents('get_time_series.php?time_range=1m&filter_type=all&product_ids=8,9,10');
if ($product_test) {
    $data = json_decode($product_test, true);
    if (isset($data['success']) && $data['success']) {
        echo "   ✓ 產品篩選功能正常\n";
        if (!empty($data['time_series_data'])) {
            echo "      - 找到 " . count($data['time_series_data']) . " 個產品的時間序列資料\n";
        }
    } else {
        echo "   ✗ 產品篩選功能異常\n";
    }
} else {
    echo "   ✗ 產品篩選功能無法訪問\n";
}

echo "測試完成！\n";