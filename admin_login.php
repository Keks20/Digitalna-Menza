<?php
/**
 * LOGIKA: prijava zaposlenih SC-a – administrator i kasir.
 * Izgled je u views/admin_login.view.php.
 *
 * Posle prijave: administrator ide na admin_pocetna.php, kasir na kasir_bonovi.php.
 * Uspešna prijava se upisuje u dnevnik akcija (admin_dnevnik.php).
 * Posle 5 pogrešnih pokušaja za isto ime prijava se zaustavlja na 10 minuta (src/PokusajiPrijave.php).
 * admin_login.php?istekla=1 – zaposleni je odjavljen jer 20 minuta nije bio aktivan (includes/provera_admina.php).
 *
 * Prikaz dobija:
 *   $greska         string  poruka o grešci posle neuspešne prijave ('' ako je nema)
 *   $korisnickoIme  string  ime koje je upisano (da ne kuca ponovo)
 *   $csrfToken      string  skriveno polje csrf_token u formi
 */
require_once __DIR__ . '/src/AdminAuth.php';
require_once __DIR__ . '/src/AdminRepository.php';
require_once __DIR__ . '/src/AdminDnevnikRepository.php';

$adminAuth = new AdminAuth();
if ($adminAuth->check()) {
    header('Location: ' . $adminAuth->pocetnaStrana());
    exit;
}

$greska = '';
$korisnickoIme = '';

// Zaposleni je odjavljen zbog neaktivnosti (na ovoj ili na prethodnoj stranici)
if (isset($_GET['istekla']) || $adminAuth->sesijaIstekla()) {
    $greska = 'Odjavljen si jer ' . BaseAuth::NEAKTIVNOST_MIN . ' minuta nije bilo aktivnosti. Prijavi se ponovo.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $korisnickoIme = trim($_POST['korisnicko_ime'] ?? '');
    $lozinka = $_POST['lozinka'] ?? '';

    if (!$adminAuth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $greska = 'Sesija je istekla. Osveži stranicu i pokušaj ponovo.';
    } elseif ($korisnickoIme === '' || $lozinka === '') {
        $greska = 'Upiši korisničko ime i lozinku.';
    } elseif ($adminAuth->login($korisnickoIme, $lozinka)) {
        $prijavljeni = (new AdminRepository())->read($adminAuth->adminId());
        (new AdminDnevnikRepository())->upisi($prijavljeni, 'prijava', 'Prijava (' . $prijavljeni->nazivUloge() . ').');
        header('Location: ' . $adminAuth->pocetnaStrana());
        exit;
    } else {
        $greska = $adminAuth->getGreska();
    }
}

$csrfToken = $adminAuth->generateCsrfToken();

require __DIR__ . '/views/admin_login.view.php';
