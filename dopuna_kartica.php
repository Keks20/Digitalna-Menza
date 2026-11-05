<?php
/**
 * LOGIKA: dopuna kartice – korak 2a: plaćanje platnom karticom (simulacija banke).
 * Izgled je u views/dopuna_kartica.view.php.
 *
 * Dolazi se samo iz dopuna.php (iznos i način su u sesiji); inače povratak na dopuna.php.
 * Podaci platne kartice se ne čuvaju – u opisu transakcije ostaju samo poslednje 4 cifre.
 *
 * POST "akcija":
 *   plati     – broj_kartice, ime_na_kartici, istek (MM/GG), cvc -> dopuna_uspesno.php?id=...
 *   odustani  – povratak na dopuna.php
 *
 * Prikaz dobija:
 *   $student, $kartica     Student, Kartica
 *   $iznos                 float     iznos dopune
 *   $novoStanje            float     stanje posle uplate
 *   $imeNaKartici          string    upisano ime (broj kartice i CVC se nikad ne vraćaju u formu)
 *   $greskePolja           array     ['broj' => '...', 'ime' => '...', 'istek' => '...', 'cvc' => '...']
 *   $greska                string    poruka banke kad odbije plaćanje ('' ako je nema)
 *   $csrfToken             string
 */
require_once __DIR__ . '/includes/provera_prijave.php';
require_once __DIR__ . '/src/StudentRepository.php';
require_once __DIR__ . '/src/KarticaRepository.php';
require_once __DIR__ . '/src/TransakcijaRepository.php';
require_once __DIR__ . '/src/PlatniSistem.php';
require_once __DIR__ . '/src/Dopuna.php';
require_once __DIR__ . '/src/Poruka.php';
require_once __DIR__ . '/src/Format.php';

$dopuna = Dopuna::trenutna();
if (!$dopuna || $dopuna['nacin'] !== 'platna_kartica') {
    header('Location: dopuna.php');
    exit;
}

$student = (new StudentRepository())->read($studentId);
$kartica = (new KarticaRepository())->read($karticaId);
$iznos = (float)$dopuna['iznos'];
$novoStanje = $kartica->stanje + $iznos;

$imeNaKartici = '';
$greskePolja = [];
$greska = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $akcija = $_POST['akcija'] ?? '';

    if (!$auth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $greska = 'Sesija je istekla. Osveži stranicu i pokušaj ponovo.';
    } elseif ($akcija === 'odustani') {
        Dopuna::zavrsi();
        Poruka::postavi('danger', 'Plaćanje je otkazano – novac nije skinut.');
        header('Location: dopuna.php');
        exit;
    } elseif ($akcija === 'plati') {
        $brojKartice = (string)($_POST['broj_kartice'] ?? '');
        $imeNaKartici = trim((string)($_POST['ime_na_kartici'] ?? ''));
        $istek = (string)($_POST['istek'] ?? '');
        $cvc = (string)($_POST['cvc'] ?? '');

        $platniSistem = new PlatniSistem();
        $greskePolja = $platniSistem->proveriPodatke($brojKartice, $imeNaKartici, $istek, $cvc);

        if (empty($greskePolja)) {
            $transakcije = new TransakcijaRepository();
            $opis = 'Dopuna kartice — platna kartica •••• ' . PlatniSistem::poslednjeCetiri($brojKartice);
            $odgovor = $platniSistem->naplati($brojKartice, $iznos);

            if ($odgovor['odobreno']) {
                $id = $transakcije->uplati($karticaId, $iznos, 'platna_kartica', $opis);
                Dopuna::zavrsi();
                header('Location: dopuna_uspesno.php?id=' . $id);
                exit;
            }

            // Odbijena uplata ostaje u evidenciji (vidi je i administrator), stanje se ne menja
            $transakcije->zabeleziUplatu($karticaId, $iznos, 'platna_kartica', 'odbijeno', $opis);
            $greska = $odgovor['poruka'];
        }
    }
}

$csrfToken = $auth->generateCsrfToken();

require __DIR__ . '/views/dopuna_kartica.view.php';
