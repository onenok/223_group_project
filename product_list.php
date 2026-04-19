<?php
session_start();
require_once("connect.php");

// 1. Message mapping (optional errors/success from adding cart)
$messages = [
    'invalid_input' => '輸入資料有誤。',
    'csrf_error' => '驗證錯誤，請重試。',
    'product_not_found' => '找不到該商品。',
    'out_of_stock' => '庫存不足或售罄。',
    'added' => '商品已加入購物車。',
];
$msg_key = $_GET['msg'] ?? '';
$display_msg = $messages[$msg_key] ?? '';
// ensure user is logged in
if (empty($_SESSION['login'])) {
    // redirect to login page or show message
    header('Location: login.php?msg=please_login');
    exit;
}
// 2. Check if user is logged-in (true/false)
// load current user's cart product ids to mark items already in cart
$cartProductIds = [];
$cartRes = safeQuery('SELECT product_id FROM cart WHERE member_id = ?', 's', [$_SESSION['member_id']]);
if ($cartRes->success && $cartRes->result) {
    $rows = $cartRes->result->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as $r) {
        $cartProductIds[intval($r['product_id'])] = true;
    }
}
// get types of products for filter options
$product_types = [];
$sql_types = "SELECT DISTINCT type FROM products";
$res_types = safeQuery($sql_types);
if ($res_types->success) {
    $product_types = array_column($res_types->result->fetch_all(MYSQLI_ASSOC), 'type');
} else {
    error_log("Failed to fetch product types: " . $res_types->error);
}
$showingType = $_GET['type'] ?? '';
// products get from database
$products = [];
try {
    $sql = "SELECT * FROM products";
    $response = null;
    if ($showingType) {
        $sql = "SELECT * FROM products WHERE type = ?";
    }
    if ($showingType) {
        $response = safeQuery($sql, 's', [$showingType]);
    } else {
        $response = safeQuery($sql);
    }
    if ($response->success) {
        $products = $response->result->fetch_all(MYSQLI_ASSOC);
    } else {
        throw new Exception("Failed to fetch products: " . $response->error);
    }
} catch (Exception $e) {
    $products = []; // fallback to empty list on error
    error_log($e->getMessage()); // log the error for debugging
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>商品列表</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <style>
        /* product_list.php */
        .content {
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* container for filter selector */
        .filter-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .filter-select {
            padding: 8px 12px;
            font-size: 1em;
            border-radius: 4px;
            border: 1px solid #ccc;
            min-width: 160px;
        }

        .product-list {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 15px;
        }

        ul {
            list-style-type: none;
            padding: 0;
        }

        /* make each product line use full width and distribute content */
        li {
            background-color: white;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .product-details {
            flex: 1;
        }

        .product-details strong {
            font-size: 1.2em;
            color: #007bff;
        }

        .product-info {
            margin-top: 10px;
            display: grid;
            grid-template-columns: 1fr 180px;
            gap: 12px 20px;
            align-items: start;
            line-height: 1.4;
        }

        .product-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
        }

        .meta-left {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .meta-right {
            text-align: right;
            min-width: 140px;
        }

        .product-meta {
            color: #555;
            font-size: 0.95em;
        }

        .meta-label {
            color: #666;
            margin-right: 6px;
            font-weight: 600;
        }

        .product-price {
            color: #e83e8c;
            font-weight: 700;
            font-size: 1.05em;
        }

        .product-desc {
            grid-column: 1 / -1;
            color: #333;
            margin-top: 8px;
        }

        @media (max-width: 700px) {
            li {
                flex-direction: column;
                align-items: stretch;
            }

            .product-info {
                grid-template-columns: 1fr;
            }

            .meta-right {
                text-align: left;
            }

            .actions {
                margin-left: 0;
                margin-top: 10px;
            }
        }

        .qty-label {
            font-weight: bold;
        }

        .sold-out-text {
            color: #dc3545;
            font-weight: bold;
        }

        .actions {
            margin-left: 20px;
            text-align: center;
        }

        .cart-button,
        .sold-out-btn {
            padding: 8px 14px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.95em;
        }

        .cart-button {
            background-color: #28a745;
            color: white;
        }

        .sold-out-btn {
            background-color: #6c757d;
            color: white;
            cursor: default;
        }

        /* modal overlay */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            width: 300px;
            max-width: 90%;
            text-align: center;
            position: relative;
        }

        .modal-footer {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }

        .modal-footer button {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 0.9em;
        }

        .confirm-btn {
            background-color: #007bff;
            color: #fff;
        }

        .cancel-btn {
            background-color: #dc3545;
            color: #fff;
        }
    </style>
</head>

<body>
    <?php require_once 'nav.php'; ?>
    <div class="content">
        <?php if ($display_msg): ?>
            <div class="msg" style="color: green; text-align: center; margin-bottom: 10px;">
                <?php echo htmlspecialchars($display_msg); ?>
            </div>
        <?php endif; ?>
        <h1>商品列表</h1>
        <div style="display:grid; grid-template-columns: fit-content(100%) auto fit-content(100%); align-items: center; gap: 10px;">
            <a href="index.php">返回主頁</a>
            <div></div>
            <a href="show_cart.php">查看購物車</a>
        </div>

        <div class="filter-container">
            <select onchange="location = this.value;" class="filter-select">
                <option value="product_list.php" <?php if (!$showingType) echo 'selected'; ?>>全部類型</option>
                <?php
                // Get distinct types from products for filter options
                foreach ($product_types as $type) {
                    $selected = ($type === $showingType) ? 'selected' : '';
                    echo "<option value='product_list.php?type=" . urlencode($type) . "' $selected>" . htmlspecialchars($type) . "</option>";
                }
                ?>
            </select>
        </div>
        <?php if ($showingType): ?>
            <p>正在顯示類型: <strong><?php echo htmlspecialchars($showingType); ?></strong></p>
        <?php endif; ?>
        <ul class="product-list">
            <?php foreach ($products as $product): ?>
                <li>
                    <div class="product-details">
                        <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                        <div class="product-info">
                            <?php
                            $remaining = intval($product['qty']);
                            ?>
                            <div class="product-row">
                                <div class="meta-left">
                                    <div class="product-meta"><span class="meta-label">類型：</span><span><?php echo htmlspecialchars($product['type']); ?></span></div>
                                    <div class="product-meta"><span class="meta-label">供應商：</span><span><?php echo htmlspecialchars($product['supplier']); ?></span></div>
                                </div>
                                <div class="meta-right">
                                    <div class="product-price">$ <?php echo htmlspecialchars($product['price']); ?></div>
                                    <?php if ($remaining !== null): ?>
                                        <?php if ($remaining <= 0): ?>
                                            <div class="sold-out-text">售罄</div>
                                        <?php else: ?>
                                            <div class="product-meta">剩餘數量：<?php echo $remaining; ?></div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="product-desc">描述： <?php echo htmlspecialchars($product['description']); ?></div>
                        </div>
                    </div>
                    <div class="actions">
                        <?php if ($remaining !== null && $remaining <= 0): ?>
                            <button class="sold-out-btn" disabled>售罄</button>
                        <?php elseif (!empty($cartProductIds[intval($product['product_id'])])): ?>
                            <a href="show_cart.php" class="cart-button in-cart">已在購物車</a>
                        <?php else: ?>
                            <button class="add-cart-button cart-button" data-selected-count="<?php echo $product['product_id']; ?>" data-id="<?php echo $product['product_id']; ?>" <?php if ($remaining !== null) echo ' data-qty="' . htmlspecialchars($remaining) . '"'; ?>>加入購物車</button>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- modal for quantity selection -->
    <form id="addCartForm" class="modal" method="post" action="add_to_cart.php">
        <div class="modal-content">
            <h2>選擇數量</h2>
            <input type="hidden" name="product_id" id="formProductId" value="">
            <input type="hidden" name="csrf_token" id="formCsrf" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            <label for="formqty" style="display:block; margin:10px 0;">數量
                <input type="number" name="qty" id="formqty" min="1" value="1" required style="width:60px; padding:4px;">
            </label>
            <div id="maxHint" style="font-size:0.85em; color:#666;"></div>
            <div class="modal-footer">
                <button type="button" class="cancel-btn" id="modalCancel">取消</button>
                <input type="submit" class="btn-input confirm-btn" value="確認">
            </div>
        </div>
    </form>

    <script>
        (function() {
            let currentProductId = null;
            const formModal = document.getElementById('addCartForm');
            const qtyInput = document.getElementById('formqty');
            const cancelBtn = document.getElementById('modalCancel');

            // open modal form when add-cart button clicked
            document.querySelectorAll('.add-cart-button').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    currentProductId = this.getAttribute('data-id');
                    document.getElementById('formProductId').value = currentProductId;
                    qtyInput.value = 1;
                    const max = this.getAttribute('data-qty');
                    const maxHint = document.getElementById('maxHint');
                    if (max) {
                        qtyInput.max = max;
                        maxHint.textContent = '最多可選 ' + max + ' 件';
                    } else {
                        qtyInput.removeAttribute('max');
                        maxHint.textContent = '';
                    }
                    formModal.style.display = 'flex';
                    formModal.querySelector('input[type="submit"]').focus();
                });
            });
            cancelBtn.addEventListener('click', function() {
                formModal.style.display = 'none';
                currentProductId = null;
                document.getElementById('maxHint').textContent = '';
            });

            cancelBtn.addEventListener('click', function() {
                formModal.style.display = 'none';
                currentProductId = null;
            });

            // clicking outside modal-content closes it
            formModal.addEventListener('click', function(e) {
                if (e.target === formModal) {
                    formModal.style.display = 'none';
                    currentProductId = null;
                }
            });
        })();
    </script>
</body>

</html>