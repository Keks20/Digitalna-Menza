<?php
/**
 * LOGIKA: prijava zaposlenih SC-a – administrator i kasir.
 * Izgled je u views/admin_login.view.php.
 *
 * Posle prijave: administrator ide na admin_pocetna.php, kasir na kasir_bonovi.php.
 *
 * Prikaz dobija:
 *   $greska         string  poruka o grešci posle neuspešne prijave ('' ako je nema)
 *   $korisnickoIme  string  ime koje je upisano (da ne kuca ponovo)
 *   $csrfToken      string  skriveno polje csrf_token u formi
 */
require_once __DIR__ . '/src/AdminAuth.php';

$adminAuth = new AdminAuth();
if ($adminAuth->check()) {
    header('Location: ' . $adminAuth->pocetnaStrana());
    exit;
}

$greska = '';
$korisnickoIme = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $korisnickoIme = trim($_POST['korisnicko_ime'] ?? '');
    $lozinka = $_POST['lozinka'] ?? '';

    if (!$adminAuth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $greska = 'Sesija je istekla. Osveži stranicu i pokušaj ponovo.';
    } elseif ($korisnickoIme === '' || $lozinka === '') {
        $greska = 'Upiši korisničko ime i lozinku.';
    } elseif ($adminAuth->login($korisnickoIme, $lozinka)) {
        header('Location: ' . $adminAuth->pocetnaStrana());
        exit;
    } else {
        $greska = $adminAuth->getGreska();
    }
}

$csrfToken = $adminAuth->generateCsrfToken();

require __DIR__ . '/views/admin_login.view.php';
