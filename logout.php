<?php
require_once "auth.php";

//hapus semua data session dan cookie session-nya
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
}
session_destroy();
header("location:login.php");
exit;
