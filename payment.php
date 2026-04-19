<?php
session_start();
require_once('connect.php');

if (empty($_SESSION['login'])||empty($_SESSION['member_id'])) {
    header('Location: login.php?msg=please_login');
    exit;
}
$member = $_SESSION['login'];
$memberID = $_SESSION['member_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm_purchase') {
    // handle purchase logic here (e.g. save order, reduce stock, clear cart)
    // CSRF check
    if (!empty($_POST['csrf_token'])) {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            header('Location: show_cart.php?msg=purchase_failed');
            exit;
        }
    }
    // fetch cart items and verify stock before committing
    $cartRes = safeQuery('SELECT product_id, qty FROM cart WHERE member_id = ?', 's', [$memberID]);
    if (!$cartRes->success || !$cartRes->result) {
        header('Location: show_cart.php?msg=purchase_failed');
        exit;
    }
    $cartItems = $cartRes->result->fetch_all(MYSQLI_ASSOC);
    if (empty($cartItems)) {
        header('Location: show_cart.php?msg=purchase_failed');
        exit;
    }

    // use mysqli transaction for atomic update
    global $conn;
    $conn->begin_transaction();
    $ok = true;

    // fetch all products used in cart in one query
    $pids = array_map(function($c){ return intval($c['product_id']); }, $cartItems);
    $pids = array_values(array_unique($pids));
    $productsMap = [];
    if (!empty($pids)) {
        $placeholders = implode(',', array_fill(0, count($pids), '?'));
        $types = str_repeat('i', count($pids));
        $prodRes = safeQuery("SELECT * FROM products WHERE product_id IN ($placeholders)", $types, $pids);
        if ($prodRes->success && $prodRes->result) {
            while ($r = $prodRes->result->fetch_assoc()) {
                $productsMap[intval($r['product_id'])] = $r;
            }
        }
    }

    foreach ($cartItems as $ci) {
        $pid = intval($ci['product_id']);
        $want = intval($ci['qty']);
        $prow = $productsMap[$pid] ?? null;
        if (!$prow) { $ok = false; break; }
        $avail = isset($prow['qty']) ? intval($prow['qty']) : null;
        if ($avail !== null && $want > $avail) { $ok = false; break; }

        // decrement with safety check
        $upd = safeQuery('UPDATE products SET qty = qty - ? WHERE product_id = ? AND qty >= ?', 'iii', [$want, $pid, $want]);
        if (!$upd->success) { $ok = false; break; }
        if (isset($upd->affected_rows) && $upd->affected_rows == 0) { $ok = false; break; }
    }

    if (!$ok) {
        $conn->rollback();
        header('Location: show_cart.php?msg=out_of_stock');
        exit;
    }

    // clear cart and commit
    $del = safeQuery('DELETE FROM cart WHERE member_id = ?', 's', [$memberID]);
    if ($del->success) {
        $conn->commit();
        header('Location: show_cart.php?msg=purchase_success');
        exit;
    } else {
        $conn->rollback();
        header('Location: show_cart.php?msg=purchase_failed');
        exit;
    }
} else {
    // just show the page with cart items and prefill info if available

    // fetch cart items for this user
    $sql = 'SELECT c.product_id, c.qty,
        p.product_name AS product_name,
        p.price AS price,
        p.description AS description,
        p.type AS type
        FROM cart c jOIN products p ON c.product_id = p.product_id
        WHERE c.member_id = ?';
    $res = safeQuery($sql, 's', [$memberID]);
    $cartItems = [];
    if ($res->success && $res->result) {
        $cartItems = $res->result->fetch_all(MYSQLI_ASSOC);
    }

    // try to load member info for prefill
    $memberInfo = null;
    $mres = safeQuery('SELECT * FROM member WHERE member_id = ?', 's', [$memberID]);
    if ($mres->success && $mres->result && $mrow = $mres->result->fetch_assoc()) {
        $memberInfo = $mrow;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>確認付款</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <style>
        .payment-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
        }

        .payment-grid {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 20px;
            align-items: start;
        }

        .card {
            padding: 16px;
            border: 1px solid #eee;
            border-radius: 8px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table th,
        .items-table td {
            padding: 8px;
            border-bottom: 1px solid #f0f0f0;
            text-align: left;
        }

        .summary {
            background: #f7f7f7;
            padding: 16px;
            border-radius: 8px;
        }

        .total {
            font-size: 1.2em;
            font-weight: bold;
            margin-top: 10px;
        }

        .btn {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            border: 1px solid #007BFF;
            color: #007BFF;
            cursor: pointer;
        }

        .btn.primary {
            background: #007BFF;
            color: #fff;
            border-color: #007BFF;
        }

        .msg-success {
            color: #155724;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <?php require_once 'nav.php'; ?>
    <div class="content payment-container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
            <a href="show_cart.php">返回購物車</a>
            <h1>結帳</h1>
            <a href="product_list.php">繼續購物</a>
        </div>

        <?php if (empty($cartItems)): ?>
            <div class="card">您的購物車目前沒有商品。</div>
        <?php else: ?>
            <div class="payment-grid">
                <div class="card">
                    <h2>商品明細</h2>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>商品</th>
                                <th>單價</th>
                                <th>數量</th>
                                <th>小計</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $total = 0;
                            foreach ($cartItems as $item):
                                $line = floatval($item['price']) * intval($item['qty']);
                                $total += $line;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($item['price'], 2)); ?></td>
                                    <td><?php echo htmlspecialchars($item['qty']); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($line, 2)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="summary card">
                    <h3>付款資訊</h3>
                    <form method="post" action="payment.php">
                        <input type="hidden" name="action" value="confirm_purchase">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                        <div style="margin-bottom:8px;">
                            <label>收件人姓名</label><br>
                            <input type="text" name="name" required value="<?php echo htmlspecialchars($memberInfo['member_name'] ?? ''); ?>" style="width:100%; padding:8px; margin-top:4px;" required>
                        </div>
                        <div style="margin-bottom:8px;">
                            <label>電話</label><br>
                            <input type="text" name="tel" required value="<?php echo htmlspecialchars($memberInfo['member_telno'] ?? ''); ?>" style="width:100%; padding:8px; margin-top:4px;">
                        </div>
                        <div style="margin-bottom:8px;">
                            <label>地址</label><br>
                            <textarea name="addr" required style="width:100%; padding:8px; margin-top:4px;" rows="3"><?php echo htmlspecialchars($memberInfo['member_addr'] ?? ''); ?></textarea>
                        </div>
                        <div class="total">合計：<?php echo htmlspecialchars(number_format($total, 2)); ?></div>
                        <div style="margin-top:12px; display:flex; gap:8px;">
                            <button type="submit" class="btn primary">確認下單</button>
                            <a href="show_cart.php" class="btn">返回購物車</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>