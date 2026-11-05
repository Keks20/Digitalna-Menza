<?php
/**
 * LOGIKA: prijava studenta brojem kartice i lozinkom.
 * Izgled je u views/login.view.php.
 *
 * Prikaz dobija:
 *   $greska, $brojKartice, $csrfToken
 */
require_once __DIR__ . '/src/Auth.php';

$auth = new Auth();
if ($auth->check()) {
    header('Location: pocetna.php');
    exit;
}

$greska = '';
$brojKartice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brojKartice = trim($_POST['broj_kartice'] ?? '');
    $lozinka = $_POST['lozinka'] ?? '';

    if (!$auth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $greska = 'Sesija je istekla. Osveži stranicu i pokušaj ponovo.';
    } elseif ($auth->login($brojKartice, $lozinka)) {
        header('Location: pocetna.php');
        exit;
    } else {
        $greska = $auth->getGreska();
    }
}

$csrfToken = $auth->generateCsrfToken();

require __DIR__ . '/views/login.view.php';
