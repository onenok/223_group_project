<?php
session_start();
require_once('connect.php');

// simple handler for add-to-cart requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: product_list.php');
    exit;
}

// ensure user is logged in
if (empty($_SESSION['login'])||empty($_SESSION['member_id'])) {
    // redirect to login page or show message
    header('Location: login.php?msg=please_login');
    exit;
}

$memberLoginName = $_SESSION['login'];
$memberID = $_SESSION['member_id'];
// product_id and qty from POST
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$qty = isset($_POST['qty']) ? intval($_POST['qty']) : 0;

// basic validation
if ($product_id <= 0 || $qty < 1) {
    header('Location: product_list.php?msg=invalid_input');
    exit;
}

// CSRF check
if (!empty($_POST['csrf_token'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        header('Location: product_list.php?msg=csrf_error');
        exit;
    }
}

// fetch product to verify exists and optional stock
$res = safeQuery('SELECT * FROM products WHERE product_id = ?', 'i', [$product_id]);
if (!$res->success || !$res->result || $res->result->num_rows === 0) {
    header('Location: product_list.php?msg=product_not_found');
    exit;
}
$row = $res->result->fetch_assoc();
// check stock if qty column exists
$available = null;
if (isset($row['qty'])) {
    $available = intval($row['qty']);
}
if ($available !== null && $qty > $available) {
    header('Location: product_list.php?msg=out_of_stock');
    exit;
}

// insert/update cart
// check existing entry
$check = safeQuery('SELECT qty FROM cart WHERE member_id = ? AND product_id = ?', 'si', [$memberID, $product_id]);
if ($check->success && $check->result && $check->result->num_rows > 0) {
    $existing = $check->result->fetch_assoc();
    $newqty = $qty;
    // optionally enforce available limit
    if ($available !== null && $newqty > $available) {
        $newqty = $available;
    }
    safeQuery('UPDATE cart SET qty = ? WHERE member_id = ? AND product_id = ?', 'isi', [$newqty, $memberID, $product_id]);
} else {
    // snapshot details
    safeQuery(
        'INSERT INTO cart(member_id,product_id,snapshot_name,snapshot_price,snapshot_description,snapshot_type,qty) VALUES(?,?,?,?,?,?,?)',
        'sdsdssi',
        [
            $memberID,
            $product_id,
            $row['product_name'],
            $row['price'],
            $row['description'],
            $row['type'],
            $qty
        ]
    );
}

header('Location: product_list.php?msg=added');
exit;
