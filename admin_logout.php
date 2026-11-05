<?php
/**
 * Odjava zaposlenog (administrator ili kasir) i povratak na administratorsku prijavu.
 */
require_once __DIR__ . '/src/AdminAuth.php';

$adminAuth = new AdminAuth();
$adminAuth->logout();
header('Location: admin_login.php');
exit;
