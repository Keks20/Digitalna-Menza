<?php
/**
 * LOGIKA: dopuna kartice – korak 2b: uplata preko IPS QR koda.
 * Izgled je u views/dopuna_ips.view.php.
 *
 * Pri prvom otvaranju upisuje se uplata sa statusom 'ceka_potvrdu' (stanje se još ne menja).
 * Novac stiže na karticu kad uplatu potvrdi administrator (admin_uplate.php)
 * ili kad se klikne "Simuliraj prijem uplate" (zamena za obaveštenje banke u test režimu).
 *
 * POST "akcija":
 *   simuliraj  – potvrđuje uplatu -> dopuna_uspesno.php?id=...
 *   promeni    – otkazuje uplatu (status 'odbijeno') -> dopuna.php
 *
 * Prikaz dobija:
 *   $student, $kartica   Student, Kartica
 *   $iznos               float    iznos dopune
 *   $podaciZaUplatu      array    ['primalac', 'racun', 'iznos', 'sifra', 'poziv_na_broj', 'svrha']
 *   $ipsQrTekst          string   tekst za QR kod (NBS IPS format)
 *   $transakcijaId       int      broj uplate koja čeka
 *   $greska              string
 *   $csrfToken           string
 */
require_once __DIR__ . '/includes/provera_prijave.php';
require_once __DIR__ . '/src/StudentRepository.php';
require_once __DIR__ . '/src/KarticaRepository.php';
require_once __DIR__ . '/src/TransakcijaRepository.php';
require_once __DIR__ . '/src/IpsQr.php';
require_once __DIR__ . '/src/Dopuna.php';
require_once __DIR__ . '/src/Poruka.php';
require_once __DIR__ . '/src/Format.php';

$dopuna = Dopuna::trenutna();
if (!$dopuna || $dopuna['nacin'] !== 'ips_qr') {
    header('Location: dopuna.php');
    exit;
}

$student = (new StudentRepository())->read($studentId);
$kartica = (new KarticaRepository())->read($karticaId);
$transakcije = new TransakcijaRepository();
$iznos = (float)$dopuna['iznos'];

// Uplata koja čeka – pravi se jednom, osvežavanje stranice ne pravi novu
$transakcijaId = (int)$dopuna['transakcija_id'];
if ($transakcijaId === 0) {
    $transakcijaId = $transakcije->zabeleziUplatu($karticaId, $iznos, 'ips_qr', 'ceka_potvrdu', 'Dopuna kartice — IPS QR');
    Dopuna::postaviTransakciju($transakcijaId);
}

// Ako je administrator u međuvremenu potvrdio ili odbio uplatu, dopuna je završena
$uplata = $transakcije->read($transakcijaId);
if (!$uplata || (int)$uplata->kartica_id !== $karticaId || $uplata->status !== 'ceka_potvrdu') {
    Dopuna::zavrsi();
    if ($uplata && (int)$uplata->kartica_id === $karticaId) {
        header('Location: dopuna_uspesno.php?id=' . $transakcijaId);
    } else {
        header('Location: dopuna.php');
    }
    exit;
}

$greska = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $akcija = $_POST['akcija'] ?? '';

    if (!$auth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $greska = 'Sesija je istekla. Osveži stranicu i pokušaj ponovo.';
    } elseif ($akcija === 'simuliraj') {
        $transakcije->potvrdiUplatu($transakcijaId);
        Dopuna::zavrsi();
        header('Location: dopuna_uspesno.php?id=' . $transakcijaId);
        exit;
    } elseif ($akcija === 'promeni') {
        $transakcije->odbijUplatu($transakcijaId);
        Dopuna::zavrsi();
        Poruka::postavi('danger', 'IPS uplata je otkazana. Izaberi drugi način plaćanja.');
        header('Location: dopuna.php');
        exit;
    }
}

$podaciZaUplatu = IpsQr::podaci($iznos, $kartica->broj_kartice);
$ipsQrTekst = IpsQr::tekst($iznos, $kartica->broj_kartice);
$csrfToken = $auth->generateCsrfToken();

require __DIR__ . '/views/dopuna_ips.view.php';
