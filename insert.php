<?php
session_start();

// 1. Safety check: Kick out if not POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: member_reg.php?msg=try_to_access_directly");
  exit;
}
require_once("connect.php");

// CSRF validation
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
  header("Location: member_reg.php?msg=try_to_access_directly");
  exit;
}

// 2. If user already logged-in, don't let them signup again
if (!empty($_SESSION["login"])) {
  header("Location:index.php?msg=signup_already_logged_in");
  exit; // Added exit for safety
}

// 3. Get data from signup form
$name = $_POST["uname"] ?? "";
$dName= $_POST["display_name"] ?? $name;
$tel = $_POST["tel"] ?? "";
$addr = $_POST["addr"] ?? "";
$pwd = $_POST["pwd"] ?? "";
$confirm_pwd = $_POST["confirm_pwd"] ?? "";

// 4. Basic check: fields cannot be empty
if (empty($name) || empty($pwd) || empty($confirm_pwd)) {
  header("Location: member_reg.php?msg=empty_fields");
  exit;
}

// 5. Check if passwords match
if ($pwd !== $confirm_pwd) {
  header("Location: member_reg.php?msg=passwords_dont_match");
  exit;
}

// 6. Check DB: Is this username already taken?
$sql_check = "SELECT * FROM member WHERE loginname = ?";
$res = safeQuery($sql_check, "s", [$name]);

// If found 1 or more rows, means name exists
if ($res->result && $res->result->num_rows > 0) {
  header("Location: member_reg.php?msg=name_exists");
  exit;
}

// 6. insert new user
$hashed = password_hash($pwd, PASSWORD_DEFAULT);
$sql_insert = "INSERT INTO member(loginname, pwd, member_name, tel, addr) VALUES (?, ?, ?, ?, ?)";
$res = safeQuery($sql_insert, "sssss", [$name, $hashed, $dName, $tel, $addr]);

// 7. Check if 1 row was added successfully
if ($res->affected_rows > 0) {
  // logic: Signup success -> go to login page
  header("Location: login.php?msg=registered");
  exit;
} else {
  // DB error or something wrong
  header("Location: member_reg.php?msg=failed");
  exit;
}

$conn->close();
?>
