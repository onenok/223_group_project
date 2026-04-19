<?php
session_start();

// 1. Kick out if not POST (Safety)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: cancellation.php?msg=try_to_access_directly");
  exit;
}

require_once("connect.php");

// 2. Must be logged-in to delete account
if (empty($_SESSION['login'])) {
  header('Location: index.php?msg=not_logged_in');
  exit;
}

$loginname = $_SESSION['login'];
$name = $_POST["uname"] ?? "";
$pwd = $_POST["pwd"] ?? "";

// CSRF validation
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
  header('Location: cancellation.php?msg=try_to_access_directly');
  exit;
}

// 3. Check if input name matches current user (Anti-wrong delete)
if ($name !== $loginname) {
  header("Location: cancellation.php?msg=invalid_user");
  exit;
}

// 4. Verify password before delete
$sql = "SELECT * FROM member WHERE loginname = ?";
$checkQuery = safeQuery($sql, "s", [$name]);
if (!$checkQuery->success || $checkQuery->result->num_rows == 0) {
    header("Location: cancellation.php?msg=invalid_user_or_password");
    exit;
}
$row = $checkQuery->result->fetch_assoc();
if (!password_verify($pwd, $row['pwd'])) {
    header("Location: cancellation.php?msg=invalid_user_or_password");
    exit;
}

// 5. All good! Do the final delete
$sql = "DELETE FROM member WHERE loginname = ?";
$result = safeQuery($sql, "s", [$name]);

// 6. Success: Clear session and go to index
if ($result->affected_rows > 0) {
  unset($_SESSION['login']); 
  header("Location: index.php?msg=deleted");
  exit;
} else {
  // DB error
  header("Location: cancellation.php?msg=error");
  exit;
}

$conn->close();
?>
