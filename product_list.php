<?php
session_start();
require_once("connect.php");

// 1. 訊息映射
$messages = [
    'invalid_input' => '輸入資料有誤。',
    'csrf_error' => '驗證錯誤，請重試。',
    'product_not_found' => '找不到該商品。',
    'out_of_stock' => '庫存不足或售罄。',
    'added' => '商品已加入購物車。',
];
$msg_key = $_GET['msg'] ?? '';
$display_msg = $messages[$msg_key] ?? '';

// 2. 檢查用戶是否登入
if (empty($_SESSION['login'])) {
    header('Location: login.php?msg=please_login');
    exit;
}

// 3. 獲取目前購物車內容，以便標記已在購物車的商品
$cartProductIds = [];
$cartRes = safeQuery('SELECT product_id FROM cart WHERE member_id = ?', 's', [$_SESSION['member_id']]);
if ($cartRes->success && $cartRes->result) {
    $rows = $cartRes->result->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as $r) {
        $cartProductIds[intval($r['product_id'])] = true;
    }
}

// 4. 獲取商品類型與篩選參數
$product_types = [];
$res_types = safeQuery("SELECT DISTINCT type FROM products");
if ($res_types->success) {
    $product_types = array_column($res_types->result->fetch_all(MYSQLI_ASSOC), 'type');
}

$showingType = $_GET['type'] ?? '';
$sortType = $_GET['sort'] ?? 'name_asc';

// 5. 構建主要商品清單 SQL
$products = [];
try {
    $params = [];
    $paramTypes = '';

    switch ($sortType) {
        case 'name_desc':
            $sql = "SELECT * FROM products";
            if ($showingType) {
                $sql .= " WHERE type = ?";
                $params[] = $showingType;
                $paramTypes .= 's';
            }
            $sql .= " ORDER BY product_name DESC";
            break;

        case 'hot':
            $sql = "SELECT p.*, COALESCE(SUM(pr.qty), 0) as total_sales
                    FROM products p
                    LEFT JOIN purchase_records pr ON p.product_id = pr.product_id";
            if ($showingType) {
                $sql .= " WHERE p.type = ?";
                $params[] = $showingType;
                $paramTypes .= 's';
            }
            $sql .= " GROUP BY p.product_id ORDER BY total_sales DESC";
            break;

        default: // name_asc
            $sql = "SELECT * FROM products";
            if ($showingType) {
                $sql .= " WHERE type = ?";
                $params[] = $showingType;
                $paramTypes .= 's';
            }
            $sql .= " ORDER BY product_name ASC";
            break;
    }

    $response = safeQuery($sql, $paramTypes, $params);
    if ($response->success) {
        $products = $response->result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    error_log("Product list error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>商品列表 - 購物商城</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <style>
        /* 基礎版面 */
        .content {
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* 專屬推介區塊樣式 */
        #suggestion-section {
            display: none; /* 預設隱藏，由 AJAX 成功載入後開啟 */
            margin-bottom: 30px;
        }
        .suggestion-banner {
            background: linear-gradient(135deg, #fff5f8 0%, #f0f4f8 100%);
            border: 1px dashed #d63384;
            border-radius: 12px;
            padding: 15px;
        }
        .suggestion-header {
            font-size: 1.1em;
            color: #d63384;
            font-weight: bold;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
        }
        .suggestion-header::before {
            content: "✨";
            margin-right: 8px;
        }
        .suggestion-items {
            display: flex;
            gap: 15px;
            overflow-x: auto;
            padding-bottom: 10px;
            scrollbar-width: thin;
        }
        .suggestion-card {
            background: white;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 12px;
            min-width: 170px;
            flex: 0 0 auto;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .suggestion-name {
            font-size: 0.9em;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            height: 2.8em;
            /* 標準與相容性 Line-Clamp 定義 */
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            overflow: hidden;
            text-overflow: ellipsis;
            word-break: break-all;
        }
        .quick-add-btn {
            background: #d63384;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px;
            cursor: pointer;
            font-size: 0.85em;
            transition: background 0.2s;
        }
        .quick-add-btn:hover {
            background: #b02a6b;
        }

        /* 原有商品清單 CSS */
        .filter-container {
            text-align: center;
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .filter-select {
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #ccc;
            min-width: 150px;
        }
        ul.product-list {
            list-style: none;
            padding: 0;
        }
        li.product-item {
            background-color: white;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .product-details { flex: 1; }
        .product-info {
            margin-top: 10px;
            display: grid;
            grid-template-columns: 1fr 180px;
            gap: 12px;
            line-height: 1.4;
        }
        .product-price { color: #e83e8c; font-weight: 700; font-size: 1.1em; }
        .product-meta { color: #666; font-size: 0.9em; }
        .cart-button {
            background-color: #28a745;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }
        .in-cart { background-color: #6c757d; }

        /* Modal 樣式 */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            width: 300px;
            text-align: center;
        }
        .modal-footer {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }
        .confirm-btn { background: #007bff; color: white; padding: 6px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .cancel-btn { background: #dc3545; color: white; padding: 6px 20px; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>

<body>
    <?php require_once 'nav.php'; ?>

    <div class="content">
        <?php if ($display_msg): ?>
            <div style="color: green; text-align: center; margin-bottom: 15px; font-weight: bold;">
                <?php echo htmlspecialchars($display_msg); ?>
            </div>
        <?php endif; ?>

        <h1>商品列表</h1>

        <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
            <a href="index.php">← 返回主頁</a>
            <a href="show_cart.php">查看購物車 🛒</a>
        </div>

        <section id="suggestion-section">
            <div class="suggestion-banner">
                <div class="suggestion-header">專屬您的推介商品</div>
                <div id="suggestion-container" class="suggestion-items"></div>
            </div>
        </section>

        <div class="filter-container">
            <select onchange="location = this.value;" class="filter-select">
                <option value="product_list.php?sort=<?php echo $sortType; ?>" <?php if (!$showingType) echo 'selected'; ?>>全部類型</option>
                <?php foreach ($product_types as $type): ?>
                    <option value="product_list.php?type=<?php echo urlencode($type); ?>&sort=<?php echo $sortType; ?>" <?php echo ($type === $showingType) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($type); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select onchange="location = this.value;" class="filter-select">
                <option value="product_list.php?<?php if($showingType) echo 'type='.$showingType.'&'; ?>sort=name_asc" <?php if($sortType==='name_asc') echo 'selected'; ?>>A-Z 排序</option>
                <option value="product_list.php?<?php if($showingType) echo 'type='.$showingType.'&'; ?>sort=name_desc" <?php if($sortType==='name_desc') echo 'selected'; ?>>Z-A 排序</option>
                <option value="product_list.php?<?php if($showingType) echo 'type='.$showingType.'&'; ?>sort=hot" <?php if($sortType==='hot') echo 'selected'; ?>>熱銷排序</option>
            </select>
        </div>

        <ul class="product-list">
            <?php foreach ($products as $product): ?>
                <li class="product-item">
                    <div class="product-details">
                        <strong style="color: #007bff; font-size: 1.15em;"><?php echo htmlspecialchars($product['product_name']); ?></strong>
                        <div class="product-info">
                            <div class="product-meta">
                                <div><b>類型：</b><?php echo htmlspecialchars($product['type']); ?></div>
                                <div><b>供應商：</b><?php echo htmlspecialchars($product['supplier']); ?></div>
                            </div>
                            <div style="text-align: right;">
                                <div class="product-price">$ <?php echo htmlspecialchars($product['price']); ?></div>
                                <div style="font-size: 0.85em; color: #666;">庫存：<?php echo $product['qty']; ?></div>
                            </div>
                            <div style="grid-column: 1 / -1; color: #444; font-size: 0.95em; border-top: 1px solid #eee; padding-top: 8px; margin-top: 5px;">
                                <?php echo htmlspecialchars($product['description']); ?>
                            </div>
                        </div>
                    </div>
                    <div class="actions" style="margin-left: 20px;">
                        <?php if ($product['qty'] <= 0): ?>
                            <button class="cart-button in-cart" style="background:#ccc;" disabled>售罄</button>
                        <?php elseif (!empty($cartProductIds[intval($product['product_id'])])): ?>
                            <a href="show_cart.php" class="cart-button in-cart">已在購物車</a>
                        <?php else: ?>
                            <button class="add-cart-button cart-button" 
                                    data-id="<?php echo $product['product_id']; ?>" 
                                    data-qty="<?php echo $product['qty']; ?>">加入購物車</button>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <form id="addCartForm" class="modal" method="post" action="add_to_cart.php">
        <div class="modal-content">
            <h3 style="margin-top:0;">選擇數量</h3>
            <input type="hidden" name="product_id" id="formProductId">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            
            <div style="margin: 20px 0;">
                <input type="number" name="qty" id="formqty" min="1" value="1" required 
                       style="width: 80px; padding: 8px; font-size: 1.1em; text-align: center;">
                <div id="maxHint" style="font-size: 0.85em; color: #d63384; margin-top: 8px;"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="cancel-btn" id="modalCancel">取消</button>
                <button type="submit" class="confirm-btn">確認</button>
            </div>
        </div>
    </form>

    <script>
        // 1. AJAX 載入推薦商品
        document.addEventListener('DOMContentLoaded', function() {
            const memberId = "<?php echo $_SESSION['member_id']; ?>";
            const container = document.getElementById('suggestion-container');
            const section = document.getElementById('suggestion-section');

            fetch(`get_suggestions.php?member_id=${memberId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.suggestions && data.suggestions.length > 0) {
                        let html = '';
                        data.suggestions.forEach(item => {
                            html += `
                                <div class="suggestion-card">
                                    <div class="suggestion-name" title="${item.product_name}">${item.product_name}</div>
                                    <button class="add-cart-button quick-add-btn" 
                                            data-id="${item.product_id}" 
                                            data-qty="99">
                                        快速加入 +
                                    </button>
                                </div>
                            `;
                        });
                        container.innerHTML = html;
                        section.style.display = 'block';
                    }
                })
                .catch(err => console.error("推介載入失敗", err));
        });

        // 2. 數量選擇 Modal 邏輯 (使用事件委派)
        const formModal = document.getElementById('addCartForm');
        const qtyInput = document.getElementById('formqty');
        const maxHint = document.getElementById('maxHint');

        document.addEventListener('click', function(e) {
            // 判斷點擊的是否為加入購物車按鈕
            if (e.target && e.target.classList.contains('add-cart-button')) {
                const id = e.target.getAttribute('data-id');
                const max = e.target.getAttribute('data-qty');

                document.getElementById('formProductId').value = id;
                qtyInput.value = 1;
                
                if (max && max != 0) {
                    qtyInput.max = max;
                    maxHint.textContent = max < 99 ? '庫存剩餘 ' + max + ' 件' : '';
                } else {
                    qtyInput.removeAttribute('max');
                    maxHint.textContent = '';
                }

                formModal.style.display = 'flex';
            }
        });

        // 關閉 Modal 邏輯
        document.getElementById('modalCancel').onclick = () => formModal.style.display = 'none';
        window.onclick = (e) => {
            if (e.target === formModal) formModal.style.display = 'none';
        };
    </script>
</body>
</html>