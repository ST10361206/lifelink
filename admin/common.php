<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once dirname(__DIR__) . '/includes/db.php';
function adminEscape($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function adminGuard() {
    if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
}
function adminToken() {
    if (empty($_SESSION['admin_csrf'])) $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['admin_csrf'];
}
