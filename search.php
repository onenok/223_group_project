<?php 
session_start();
require_once("connect.php");

// 1. Get input data from login form
$name = $_POST["uname"] ?? "";
$pwd = $_POST["pwd"] ?? "";

// CSRF validation (login)
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    header("Location: login.php?msg=try_to_access_directly");
    exit;
}

// 2. Search user in DB (Safe way)
$sql = "SELECT * FROM member WHERE loginname = ?";
$response = safeQuery($sql, "s", [$name]);

// 3. If SQL crash, show error and stop
if (!$response->success) {
    error_log("failed to select: " . $response->error);
    die;
}

// 4. If found a row, verify password and save username to Session
if ($response->result && $data = $response->result->fetch_assoc()) {
    if (password_verify($pwd, $data['pwd'])) {
        $_SESSION["login"] = $data['loginname'];
        $_SESSION["member_id"] = $data['Member_id']; // store member_id in session for later use
    }
}

// 5. Final check: if Session has name -> Login Success!
if (!empty($_SESSION["login"])) {
    header("Location: index.php"); // go home
    exit;
} else {
    // No match found -> Go back to login page
    header("Location: login.php?msg=login_failed");
    exit;
}
?>
