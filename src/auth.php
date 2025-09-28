<?php
require_once __DIR__ . '/config.php';
function admin_logged_in(){ return !empty($_SESSION['admin']); }
function admin_require(){ if (!admin_logged_in()) { header('Location: /?route=admin/login'); exit; } }
function admin_login($user,$pass){ global $ADMIN_USER,$ADMIN_PASS_HASH; if($user===$ADMIN_USER && password_verify($pass,$ADMIN_PASS_HASH)){ $_SESSION['admin']=$user; return true;} return false; }
function admin_logout(){ unset($_SESSION['admin']); }
