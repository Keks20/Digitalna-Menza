<?php
/**
 * LOGIKA: dopuna kartice – korak 2b: uplata preko IPS QR koda.
 * Izgled je u views/dopuna_ips.view.php.
 *
 * Pri prvom otvaranju upisuje se uplata sa statusom 'ceka_potvrdu' (stanje se još ne menja).
 * Novac stiže na karticu kad banka javi da je uplata stigla – u prototipu je to dugme
 * "Simuliraj prijem uplate". Administrator uplate samo vidi u listi (admin_uplate.php), ne potvrđuje ih.
 * Uplata koja se ne potvrdi za 30 minuta ističe (status 'isteklo') i student vidi to na dopuna_uspesno.php.
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
 *   $vaziDo              string   do kada banka može da potvrdi uplatu, npr. '15:42'
 *   $rokMinuta           int      koliko minuta uplata čeka potvrdu (30)
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

// Ako uplata u međuvremenu više ne čeka (potvrđena, otkazana u drugoj kartici pregledača ili istekla), dopuna je završena
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
        // Ako je rok baš sada prošao, potvrda ne uspeva i sledeća stranica prikazuje isteklu uplatu
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
$rokMinuta = TransakcijaRepository::ROK_IPS_MIN;
$vaziDo = date('H:i', strtotime($uplata->kreirano) + $rokMinuta * 60);
$csrfToken = $auth->generateCsrfToken();

require __DIR__ . '/views/dopuna_ips.view.php';
