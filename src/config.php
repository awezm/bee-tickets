<?php
define('APP_NAME', 'Bee Tickets');
define('DB_PATH', __DIR__ . '/../data/app.db');

$ADMIN_USER = getenv('ADMIN_USER') ?: 'admin';
$ADMIN_PASS_HASH = getenv('ADMIN_PASS_HASH') ?: '$2y$10$wZAAQ2bW3y3n1j0oQU1R5uQ2m2G3qk8a4Gx3mKf8r2l2g2rFjz0ZC'; // "changeme_admin"

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
function csrf_field(){ echo '<input type="hidden" name="csrf" value="'.htmlspecialchars($_SESSION['csrf']).'">'; }
function csrf_check(){ if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_SESSION['csrf'])) { http_response_code(400); die('Bad CSRF'); } }
