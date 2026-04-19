<?php
// 1. Check if user is logged-in (true/false)
$isLoggedIn = !empty($_SESSION['login']);
// If yes, use their name. If no, they are just a "Visitor"
$username = $isLoggedIn ? $_SESSION['login'] : "訪客";
?>
<nav class="navbar">
    <div class="logo"><a href="index.php">我的網站</a></div>
    <div class="nav-links">
        <?php if ($isLoggedIn): ?>
            <!-- If logged in, show name and Logout -->
            <span>您好，<?php echo htmlspecialchars($username); ?></span>
            <a href="logout.php" class="btn-logout" onclick="return confirm('確定要登出嗎？')">登出</a>
        <?php else: ?>
            <!-- If guest, show Login and Signup -->
            <a href="login.php" class="btn-login">登入</a>
            <a href="member_reg.php" class="btn-signup">註冊</a>
        <?php endif; ?>
    </div>
</nav>
<script src="overlay-scrollbar.js" defer></script>