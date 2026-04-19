<?php
session_start();

// 1. check login, if no login -> kick to index
if (empty($_SESSION['login'])) {
    header("Location: index.php?msg=not_logged_in");
    exit;
}

$currentName = $_SESSION['login']; 

// 2. map message (Added mismatch messages)
$msgs = [
    'old_pwd_empty' => '請輸入舊密碼以驗證身分',
    'empty_fields' => '新用戶名或新密碼不能同時為空',
    'no_changes' => '資料沒有任何變動',
    'invalid_user_or_password' => '舊密碼錯誤',
    'update_failed' => '更新失敗，請稍後再試',
    'try_to_access_directly' => '請透過正常管道提交資料',
    'error_checking_user' => '檢查用戶資料時發生錯誤',
    'username_already_used' => '新用戶名已被使用',
    'name_mismatch' => '兩次輸入的新用戶名不一致',
    'pwd_mismatch' => '兩次輸入的新密碼不一致'
];
$msg_key = $_GET['msg'] ?? '';
$display_msg = $msgs[$msg_key] ?? '';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit My Account</title>
    <style>
        body { font-family: Arial; background: #f0f2f5; display: flex; justify-content: center; padding-top: 40px; }
        .box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 380px; }
        h2 { color: #1a73e8; margin-top: 0; }
        .msg-box { color: #d32f2f; background: #ffebee; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; border: 1px solid #ffcdd2; }
        label { display: block; margin-top: 10px; font-weight: bold; font-size: 14px; color: #555; }
        input { width: 100%; padding: 10px; margin: 5px 0 15px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .hint { font-size: 12px; color: #888; margin-bottom: 15px; }
        .btn-update { width: 100%; background: #1a73e8; color: white; border: none; padding: 12px; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn-update:hover { background: #1557b0; }
        .footer-links { margin-top: 20px; text-align: center; font-size: 13px; }
        .footer-links a { color: #666; text-decoration: none; margin: 0 10px; }
        .footer-links a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="box">
    <h2>修改帳戶資料</h2>
    
    <?php if ($display_msg): ?>
        <div class="msg-box"><?php echo $display_msg; ?></div>
    <?php endif; ?>

    <form action="update.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
        <p style="font-size: 14px;">目前帳號: <strong><?php echo htmlspecialchars($currentName); ?></strong></p>

        <hr>

        <!-- New Name & Confirm -->
        <label>新的用戶名:</label>
        <input type="text" name="new_uname" placeholder="Leave empty if no change">
        <label>確認新用戶名:</label>
        <input type="text" name="confirm_new_uname" placeholder="Repeat new username">

        <hr>

        <!-- New Pwd & Confirm -->
        <label>新的密碼:</label>
        <input type="password" name="new_pwd" placeholder="Leave empty if no change">
        <label>確認新密碼:</label>
        <input type="password" name="confirm_new_pwd" placeholder="Repeat new password">

        <p class="hint">* 兩項請至少填寫一項並確認一致</p>

        <label style="color: #d32f2f;">請輸入舊密碼 (Required):</label>
        <input type="password" name="old_pwd" placeholder="確認您的身分" required>

        <button type="submit" class="btn-update">更新資料並重新登入</button>
    </form>

    <div class="footer-links">
        <a href="index.php">返回主頁</a>
        <a href="cancellation.php" style="color: #d32f2f;">註銷帳號</a>
    </div>
</div>

<script>
    // Check match for Name and Pwd
    const nName = document.getElementsByName("new_uname")[0];
    const cName = document.getElementsByName("confirm_new_uname")[0];
    const nPwd = document.getElementsByName("new_pwd")[0];
    const cPwd = document.getElementsByName("confirm_new_pwd")[0];

    function validate() {
        // check name
        if (nName.value != cName.value) {
            cName.setCustomValidity("用戶名不相符");
        } else {
            cName.setCustomValidity('');
        }
        // check pwd
        if (nPwd.value != cPwd.value) {
            cPwd.setCustomValidity("密碼不相符");
        } else {
            cPwd.setCustomValidity('');
        }
    }

    nName.onchange = cName.onkeyup = validate;
    nPwd.onchange = cPwd.onkeyup = validate;
</script>

</body>
</html>
