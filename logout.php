<?php
session_start(); // start session to kill it

// clear the login data
$_SESSION["login"] = "";
unset($_SESSION["login"]); // ensure $_SESSION["login"] is cleaned

// check if clear success
if (!empty($_SESSION["login"])) 
{
    // logic: if still has data, logout failed
    header("Location:index.php?msg=logout_failed");
    exit;
}
else 
{
    // logic: clean success, go home
    header("Location:index.php?msg=logged_out");
    exit;
}
?>
