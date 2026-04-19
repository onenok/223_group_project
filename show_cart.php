<?php
session_start();
require_once('connect.php');

// msg mapping
$msgMap = [
    'invalid_input' => '無效的輸入',
    'product_not_found' => '找不到商品',
    'out_of_stock' => '庫存不足',
    'added_cart' => '已加入購物車',
    'cart_updated' => '購物車已更新',
    'purchase_failed' => '購買失敗，請稍後再試',
    'update_failed' => '更新失敗，請稍後再試',
    'purchase_success' => '購買成功！',
];
$msg_key = $_GET['msg'] ?? '';
$display_msg = $msgMap[$msg_key] ?? '';

// ensure user is logged in
if (empty($_SESSION['login'])||empty($_SESSION['member_id'])) {
    // redirect to login page or show message
    header('Location: login.php?msg=please_login');
    exit;
}
$member = $_SESSION['login'];
$memberID = $_SESSION['member_id'];
$cartItems = [];
// fetch cart items for this user
$sql = 'SELECT c.product_id, c.qty,
       c.snapshot_name AS product_name,
       c.snapshot_price AS price,
       c.snapshot_description AS description,
       c.snapshot_type AS type
FROM cart c
WHERE c.member_id = ?';
$res = safeQuery($sql, 's', [$memberID]);
if ($res->success && $res->result) {
    $cartItems = $res->result->fetch_all(MYSQLI_ASSOC);
}

// load current stock details for cart items
// load current product info for cart items (indexed by product_id)
$stockMap = [];
if (!empty($cartItems)) {
    $ids = array_map(function ($c) {
        return intval($c['product_id']);
    }, $cartItems);
    $ids = array_values(array_unique($ids));
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $resP = safeQuery("SELECT * FROM products WHERE product_id IN ($placeholders)", $types, $ids);
        if ($resP->success && $resP->result) {
            while ($row = $resP->result->fetch_assoc()) {
                $stockMap[intval($row['product_id'])] = $row;
            }
        }
        // ensure keys exist for all ids
        foreach ($ids as $id) {
            if (!isset($stockMap[$id])) $stockMap[$id] = null;
        }
    }
}

// handle save cart (update quantities) POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_cart') {
    // CSRF
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        header('Location: show_cart.php?msg=invalid_input');
        exit;
    }
    $updates = $_POST['qty'] ?? [];
    $status = 0;
    foreach ($updates as $pid => $qty) {
        $pid = intval($pid);
        $qty = intval($qty);
        if ($qty <= 0) { // <= 0
            if ($qty < 0) {
                $status = 1; // invalid input
                break;
            }
            // delete from cart
            $d = safeQuery('DELETE FROM cart WHERE member_id = ? AND product_id = ?', 'si', [$memberID, $pid]);
            if (!$d->success) {
                $status = 3; // db error
                break;
            }
        } else { // >= 1
            // check stock
            $available = isset($stockMap[$pid]['qty']) ? intval($stockMap[$pid]['qty']) : null;
            if ($available !== null && $qty > $available) {
                $status = 2; // out of stock
                break;
            }
            // update cart
            $u = safeQuery('UPDATE cart SET qty = ? WHERE member_id = ? AND product_id = ?', 'isi', [$qty, $memberID, $pid]);
            if (!$u->success) {
                $status = 3; // db error
                break;
            }
        }
    }
    $mmsg = match ($status) {
        0 => 'cart_updated',
        1 => 'invalid_input',
        2 => 'out_of_stock',
        3 => 'update_failed',
        default => '未知錯誤',
    };
    header('Location: show_cart.php?msg=' . $mmsg);
    exit;
}

// handle confirm purchase POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm_purchase') {
    // CSRF
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        header('Location: show_cart.php?msg=invalid_input');
        exit;
    }
    // re-fetch to ensure latest
    $res = safeQuery($sql, 's', [$memberID]);
    $cartItems = [];
    if ($res->success && $res->result) {
        $cartItems = $res->result->fetch_all(MYSQLI_ASSOC);
    }

    // if no items, just redirect back
    if (empty($cartItems)) {
        header('Location: show_cart.php?msg=invalid_input');
        exit;
    }

    // start transaction using mysqli (safeQuery uses prepared statements and cannot run transaction control statements)
    global $conn;
    $conn->begin_transaction();
    $ok = true;
    foreach ($cartItems as $item) {
        $pid = intval($item['product_id']);
        $want = intval($item['qty']);
        // fetch product current stock
        $p = safeQuery('SELECT * FROM products WHERE product_id = ?', 'i', [$pid]);
        if (!$p->success || !$p->result || $p->result->num_rows === 0) {
            $ok = false;
            break;
        }
        $prow = $p->result->fetch_assoc();
        if (isset($prow['qty'])) {
            $available = intval($prow['qty']);
            if ($want > $available) {
                $ok = false;
                break;
            }
        }
    }

    if ($ok) {
        $conn->commit();
        header('Location: payment.php');
        exit;
    } else {
        $conn->rollback();
        header('Location: show_cart.php?msg=out_of_stock');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>購物車</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <style>
        /* page-specific: fixed confirm button */
        .fixed-confirm {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 1000;
        }

        .btn {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            border: 1px solid #007BFF;
            color: #007BFF;
            background: transparent;
            cursor: pointer;
        }

        /* product list (match product_list.php style) */
        .product-list {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .product-list ul {
            list-style-type: none;
            padding: 0;
        }

        .product-list li {
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

        .actions {
            margin-left: 20px;
            text-align: center;
        }

        @media (max-width:700px) {
            .product-list li {
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

        .btn.primary {
            background: #007BFF;
            color: #fff;
            border-color: #007BFF;
        }

        /* inline edit controls (default horizontal, stack on small screens) */
        .inline-edit-controls {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .edit-note {
            color: #666;
            font-size: 0.95em;
            margin-left: 10px;
            align-self: center;
        }

        @media (max-width:480px) {
            .inline-edit-controls {
                flex-direction: column;
                align-items: flex-end;
            }
        }
    </style>
</head>

<body>
    <?php require_once 'nav.php'; ?>
    <div class="content">
        <?php if ($display_msg): ?>
            <div class="msg"><?php echo htmlspecialchars($display_msg); ?></div>
        <?php endif; ?>
        <h1>購物車</h1>
        <div style="display:grid; grid-template-columns: auto fit-content(100%); align-items: center; gap: 10px;">
            <div>
                <a href="index.php">返回主頁</a>
                <a href="product_list.php">繼續購物</a>
            </div>
            <button id="confirmPurchaseBtn"
                class="btn primary"
                <?php if (empty($cartItems)): ?>
                style="visibility: hidden; pointer-events: none;"
                <?php endif; ?>>
                確認購買
            </button>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:8px; margin:8px 0;">
            <?php if (!empty($cartItems)): ?>
                <span class="edit-note">提示：在編輯模式中，將數量設為 0 會從購物車中刪除該商品。</span>
                <div id="editToggleArea" class="inline-edit-controls">
                    <button id="editModeBtn" class="btn">編輯數量</button>
                </div>
            <?php endif; ?>
        </div>

        <form id="editCartForm" method="post" action="show_cart.php">
            <input type="hidden" name="action" value="save_cart">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
            <div class="product-list">
                <?php if (empty($cartItems)): ?>
                    <h2 style="text-align: center;">購物車是空的。</h2>
                <?php else: ?>
                    <ul>
                        <?php foreach ($cartItems as $item): ?>
                            <?php
                            $pid = intval($item['product_id']);
                            $current = $stockMap[$pid] ?? null;
                            $max = null;
                            if ($current !== null && isset($current['qty'])) {
                                $max = intval($current['qty']);
                            }
                            $snapName = $item['product_name'];
                            $curName = $current['product_name'] ?? null;
                            $snapPrice = floatval($item['price']);
                            $curPrice = isset($current['price']) ? floatval($current['price']) : null;
                            $snapType = $item['type'];
                            $curType = $current['type'] ?? null;
                            $snapDesc = trim($item['description'] ?? '');
                            $curDesc = isset($current['description']) ? trim($current['description']) : null;
                            ?>
                            <li>
                                <div class="product-details">
                                    <?php if ($curName !== null && $curName !== $snapName): ?>
                                        <strong><del style="color:red;"><?php echo htmlspecialchars($snapName); ?></del> <span style="color:green;"><?php echo htmlspecialchars($curName); ?></span></strong>
                                    <?php else: ?>
                                        <strong><?php echo htmlspecialchars($snapName); ?></strong>
                                    <?php endif; ?>

                                    <div class="product-info">
                                        <div>
                                            <div class="product-row">
                                                <div class="meta-left">
                                                    <div class="product-meta"><span class="meta-label">類型：</span>
                                                        <?php if ($curType !== null && $curType !== $snapType): ?>
                                                            <span style="color:red;"><del><?php echo htmlspecialchars($snapType); ?></del></span> <span style="color:green;"><?php echo htmlspecialchars($curType); ?></span>
                                                        <?php else: ?>
                                                            <?php echo htmlspecialchars($snapType); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="product-desc">
                                                        <?php if ($curDesc !== null && $curDesc !== $snapDesc): ?>
                                                            <div style="color:red;"><del><?php echo nl2br(htmlspecialchars($snapDesc)); ?></del></div>
                                                            <div style="color:green;"><?php echo nl2br(htmlspecialchars($curDesc)); ?></div>
                                                        <?php else: ?>
                                                            <?php echo nl2br(htmlspecialchars($snapDesc)); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="meta-right">
                                                    <div class="product-price">
                                                        <?php if ($curPrice !== null && $curPrice != $snapPrice): ?>
                                                            $<span style="color:red;"><del><?php echo htmlspecialchars(number_format($snapPrice, 2)); ?></del></span>
                                                            $<span style="color:green;"><?php echo htmlspecialchars(number_format($curPrice, 2)); ?></span>
                                                        <?php else: ?>
                                                            $<?php echo htmlspecialchars(number_format($snapPrice, 2)); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php
                                                    // per-item subtotal based on price * qty; show snapshot vs current if different
                                                    $qtyInt = intval($item['qty']);
                                                    $lineSnap = number_format($snapPrice * $qtyInt, 2);
                                                    $lineCur = $curPrice !== null ? number_format($curPrice * $qtyInt, 2) : null;
                                                    ?>
                                                    <div class="product-meta" style="margin-top:6px;">
                                                        小計：
                                                        <?php if ($lineCur !== null && $lineCur !== $lineSnap): ?>
                                                            $<span style="color:red;"><del><?php echo htmlspecialchars($lineSnap); ?></del></span>
                                                            $<span style="color:green;"><?php echo htmlspecialchars($lineCur); ?></span>
                                                        <?php else: ?>
                                                            $<?php echo htmlspecialchars($lineSnap); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if ($max !== null): ?>
                                                        <?php if ($max <= 0): ?>
                                                            <div class="product-meta sold-out-text">售罄</div>
                                                        <?php else: ?>
                                                            <div class="product-meta">剩餘數量：<?php echo intval($max); ?></div>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="product-desc" style="grid-column:1 / -1;">數量：
                                            <input type="number" name="qty[<?php echo $pid; ?>]" data-productName="<?php echo htmlspecialchars($item['product_name']); ?>" value="<?php echo intval($item['qty']); ?>" min="0" <?php if ($max !== null) echo 'max="' . intval($max) . '"'; ?> disabled style="width:80px; margin-left:6px;">
                                        </div>
                                    </div>
                                </div>
                                <div class="actions">
                                    <!-- actions area left intentionally minimal for cart -->
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <!-- hidden form for confirm purchase -->
    <form id="confirmForm" method="post" action="show_cart.php" style="display:none;">
        <input type="hidden" name="action" value="confirm_purchase">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
    </form>



    <!-- edit mode fixed controls -->
    <div id="editControls" style="display:none; position:fixed; left:20px; bottom:20px; z-index:1000;">
        <button id="cancelEditBtn" class="btn">取消</button>
    </div>
    <div id="saveControls" style="display:none; position:fixed; right:20px; bottom:20px; z-index:1000;">
        <button id="saveEditBtn" class="btn primary">儲存變更</button>
    </div>

    <script>
        (function() {
            const btn = document.getElementById('confirmPurchaseBtn');
            if (btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const ok = confirm('按下確認後會檢查庫存並預留商品，是否繼續？');
                    if (ok) {
                        document.getElementById('confirmForm').submit();
                    }
                });
            }

            // Edit mode logic
            const editBtn = document.getElementById('editModeBtn');
            const inputs = document.querySelectorAll('#editCartForm input[type="number"]');
            const editControls = document.getElementById('editControls');
            const saveControls = document.getElementById('saveControls');

            const editToggleArea = document.getElementById('editToggleArea');

            function setEditing(on) {
                inputs.forEach(i => i.disabled = !on);
                if (on) {
                    editControls.style.display = 'block';
                    saveControls.style.display = 'block';
                } else {
                    editControls.style.display = 'none';
                    saveControls.style.display = 'none';
                    // restore inline area if empty
                    if (editToggleArea && editToggleArea.children.length === 0) {
                        const btn = document.createElement('button');
                        btn.id = 'editModeBtn';
                        btn.className = 'btn';
                        btn.textContent = '編輯數量';
                        editToggleArea.appendChild(btn);
                    }
                }
            }

            function enterEditMode() {
                setEditing(true);
                // replace inline area with Cancel | Save buttons
                if (!editToggleArea) return;
                editToggleArea.innerHTML = '';
                const cancelInline = document.createElement('button');
                cancelInline.className = 'btn';
                cancelInline.id = 'cancelInlineBtn';
                cancelInline.textContent = '取消';
                const saveInline = document.createElement('button');
                saveInline.className = 'btn primary';
                saveInline.id = 'saveInlineBtn';
                saveInline.textContent = '儲存';
                editToggleArea.appendChild(cancelInline);
                editToggleArea.appendChild(saveInline);
                // hide fixed bottom controls when inline shown
                editControls.style.display = 'none';
                saveControls.style.display = 'none';

                cancelInline.addEventListener('click', function(e) {
                    e.preventDefault();
                    location.reload();
                });
                saveInline.addEventListener('click', function(e) {
                    e.preventDefault();
                    let cancelProductSelection = false;
                    let cancelProducts = [];
                    for (const inp of inputs) {
                        const max = inp.getAttribute('max');
                        const pName = inp.getAttribute('data-productName');
                        if (max && Number(inp.value) > Number(max)) {
                            alert('選擇的貨品量超出了庫存,請調整後再儲存。');
                            return;
                        }
                        if (Number(inp.value) < 1) {
                            if (Number(inp.value) < 0) {
                                alert('數量不可為負數');
                                return;
                            }
                            cancelProductSelection = true;
                            cancelProducts.push(pName);
                        }
                    }
                    if (cancelProductSelection) {
                        const productList = cancelProducts.join(', \n');
                        const confirmCancel = confirm('您已將某些商品數量調整為 0，這將從購物車中移除該商品。是否繼續？\n ' + productList);
                        if (!confirmCancel) {
                            return;
                        }
                    }
                    document.getElementById('editCartForm').submit();
                });
            }

            if (editBtn) {
                editBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    enterEditMode();
                });
            }
        })();
    </script>
</body>

</html>