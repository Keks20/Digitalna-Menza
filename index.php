<?php
require_once __DIR__ . '/src/Auth.php';
$auth = new Auth();
if ($auth->check()) {
    header('Location: pocetna.php');
    exit;
}
header('Location: login.php');
exit;
