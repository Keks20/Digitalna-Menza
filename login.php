<?php
/**
 * LOGIKA: prijava studenta brojem kartice i lozinkom.
 * Izgled je u views/login.view.php.
 *
 * Posle 5 pogrešnih pokušaja za istu karticu prijava se zaustavlja na 10 minuta (src/PokusajiPrijave.php).
 * login.php?istekla=1 – student je odjavljen jer 20 minuta nije bio aktivan (includes/provera_prijave.php).
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

// Student je odjavljen zbog neaktivnosti (na ovoj ili na prethodnoj stranici)
if (isset($_GET['istekla']) || $auth->sesijaIstekla()) {
    $greska = 'Odjavljen si jer ' . BaseAuth::NEAKTIVNOST_MIN . ' minuta nije bilo aktivnosti. Prijavi se ponovo.';
}

// Student je odjavljen jer mu je kartica blokirana dok je bio prijavljen (includes/provera_prijave.php)
if (isset($_GET['blokirana'])) {
    $greska = 'Kartica je blokirana. Javi se na šalter Studentskog centra.';
}

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
