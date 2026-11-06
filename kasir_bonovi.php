<?php
/**
 * LOGIKA: provera QR bonova i izdavanje obroka na kasi.
 * Izgled je u views/kasir_bonovi.view.php. Na stranicu smeju kasir i administrator.
 *
 * Tok:
 *   1. kasir izabere menzu u kojoj radi (pamti se u sesiji)
 *   2. skenira QR bon kamerom ili čitačem (akcija=skeniraj, polje bon)
 *      ili upiše broj kartice kad kamera ne radi (akcija=kartica, polja broj_kartice i tip_obroka)
 *   3. ako obrok sme da se izda, vidi studenta i koliko mu ostaje -> akcija=izdaj (ili akcija=odustani)
 * Svaki POST se završava preusmeravanjem na kasir_bonovi.php (Post/Redirect/Get).
 *
 * Prikaz dobija:
 *   $admin            Admin           ime_prezime, inicijali(), nazivUloge()
 *   $menze            array           [['id' => 1, 'naziv' => 'Menza Ekonomski', ...], ...] za izbor menze
 *   $menza            array           menza u kojoj kasa radi (isti oblik)
 *   $tipovi           TipObroka[]
 *   $trenutniObrok    TipObroka|null  obrok koji se sada služi (null = van radnog vremena)
 *   $rezultat         array|null      poslednja provera, prikazuje se jednom:
 *                                     ['ishod' => 'moze'|'izdato'|'vec_izdato'|'odbijeno', 'razlog' => '...',
 *                                      'student' => 'Marko Marković', 'broj_kartice' => '001234567890', 'obrok' => 'Ručak',
 *                                      'kupljeno' => 10, 'preostalo_posle' => 6, 'skenirano' => '12:41:08']
 *                                     'moze' = čeka potvrdu kasira (dugme "Potvrdi izdavanje")
 *   $danasBrojevi     array           [tip_obroka_id => ['izdato' => 148, 'ima_obrok' => 220]] za ovu menzu danas
 *   $skeniranja       array           poslednjih 8 provera na ovoj kasi, najnovija prva:
 *                                     [['vreme' => '12:41', 'student' => '...', 'obrok' => 'Ručak', 'ishod' => 'izdato', 'razlog' => ''], ...]
 *   $csrfToken        string
 *
 * Imena polja (POST): akcija, csrf_token, bon, broj_kartice, tip_obroka; izbor menze je GET ?menza=<id>
 */
$dozvoljeneUloge = ['kasir', 'admin'];
require_once __DIR__ . '/includes/provera_admina.php';
require_once __DIR__ . '/src/MenzaRepository.php';
require_once __DIR__ . '/src/TipObrokaRepository.php';
require_once __DIR__ . '/src/Kasa.php';
require_once __DIR__ . '/src/Bon.php';
require_once __DIR__ . '/src/Format.php';

/** Koliko sekundi kasir ima da potvrdi izdavanje posle skeniranja */
const ROK_ZA_POTVRDU = 120;
const BROJ_SKENIRANJA = 8;

$kasa = new Kasa();
$menze = (new MenzaRepository())->list();
$tipovi = (new TipObrokaRepository())->list();
$trenutniObrok = Kasa::trenutniObrok($tipovi);

$_SESSION['kasa'] = $_SESSION['kasa'] ?? ['menza_id' => null, 'provera' => null, 'rezultat' => null, 'skeniranja' => []];
$stanjeKase = &$_SESSION['kasa'];

// ---------- Menza u kojoj kasa radi ----------
$menzePoId = [];
foreach ($menze as $m) {
    $menzePoId[(int)$m['id']] = $m;
}
if (isset($_GET['menza']) && isset($menzePoId[(int)$_GET['menza']])) {
    $stanjeKase['menza_id'] = (int)$_GET['menza'];
    $stanjeKase['provera'] = null;
    header('Location: kasir_bonovi.php');
    exit;
}
if (!isset($menzePoId[(int)$stanjeKase['menza_id']])) {
    $stanjeKase['menza_id'] = (int)$menze[0]['id'];
}
$menza = $menzePoId[$stanjeKase['menza_id']];

/** Upis u "Poslednja skeniranja" (čuva se u sesiji kase) */
function zapamtiSkeniranje(array &$stanjeKase, array $rezultat): void
{
    array_unshift($stanjeKase['skeniranja'], [
        'vreme' => date('H:i'),
        'student' => $rezultat['student'] ?? 'Nepoznato',
        'obrok' => $rezultat['obrok'] ?? '',
        'ishod' => $rezultat['ishod'],
        'razlog' => $rezultat['ishod'] === 'izdato' ? '' : ($rezultat['razlog'] ?? ''),
    ]);
    $stanjeKase['skeniranja'] = array_slice($stanjeKase['skeniranja'], 0, BROJ_SKENIRANJA);
}

/** Posle provere: ako može, čeka se potvrda kasira; ako ne može, odmah u spisak skeniranja */
function posleProvere(array &$stanjeKase, array $rezultat): void
{
    $rezultat['skenirano'] = date('H:i:s');
    if ($rezultat['ishod'] === 'moze') {
        $stanjeKase['provera'] = [
            'kartica_id' => $rezultat['kartica_id'],
            'tip_obroka_id' => $rezultat['tip_obroka_id'],
            'vreme' => time(),
        ];
    } else {
        $stanjeKase['provera'] = null;
        zapamtiSkeniranje($stanjeKase, $rezultat);
    }
    $stanjeKase['rezultat'] = $rezultat;
}

// ---------- Akcije ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $akcija = (string)($_POST['akcija'] ?? '');

    if (!$adminAuth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $stanjeKase['rezultat'] = ['ishod' => 'odbijeno', 'razlog' => 'Sesija je istekla. Osveži stranicu i skeniraj ponovo.', 'skenirano' => date('H:i:s')];

    } elseif ($akcija === 'skeniraj') {
        $bon = Bon::proveri((string)($_POST['bon'] ?? ''));
        if (!$bon['ispravan']) {
            posleProvere($stanjeKase, ['ishod' => 'odbijeno', 'razlog' => $bon['greska']]);
        } else {
            posleProvere($stanjeKase, $kasa->proveri($bon['kartica_id'], $bon['tip_obroka_id']));
        }

    } elseif ($akcija === 'kartica') {
        // Rezervni put kad kamera ne radi: broj kartice + obrok (podrazumevano onaj koji se sada služi)
        $broj = Kasa::ocistiBrojKartice((string)($_POST['broj_kartice'] ?? ''));
        $tipId = (int)($_POST['tip_obroka'] ?? ($trenutniObrok->id ?? 0));
        $karticaId = $broj !== null ? $kasa->karticaPoBroju($broj) : null;

        if ($broj === null) {
            posleProvere($stanjeKase, ['ishod' => 'odbijeno', 'razlog' => 'Broj kartice ima 12 cifara.']);
        } elseif ($karticaId === null) {
            posleProvere($stanjeKase, ['ishod' => 'odbijeno', 'razlog' => 'Kartica ' . Format::brojKartice($broj) . ' ne postoji.']);
        } else {
            posleProvere($stanjeKase, $kasa->proveri($karticaId, $tipId));
        }

    } elseif ($akcija === 'izdaj') {
        $provera = $stanjeKase['provera'];
        if ($provera === null || time() - $provera['vreme'] > ROK_ZA_POTVRDU) {
            $stanjeKase['rezultat'] = ['ishod' => 'odbijeno', 'razlog' => 'Provera je istekla – skeniraj bon ponovo.', 'skenirano' => date('H:i:s')];
        } else {
            $rezultat = $kasa->izdaj($provera['kartica_id'], $provera['tip_obroka_id'], (int)$menza['id'], $adminId);
            $rezultat['skenirano'] = date('H:i:s');
            zapamtiSkeniranje($stanjeKase, $rezultat);
            $stanjeKase['rezultat'] = $rezultat;
        }
        $stanjeKase['provera'] = null;

    } elseif ($akcija === 'odustani') {
        $stanjeKase['provera'] = null;
        $stanjeKase['rezultat'] = null;
    }

    header('Location: kasir_bonovi.php');
    exit;
}

// ---------- Prikaz ----------
// Rezultat se prikazuje jednom; "moze" ostaje dok kasir ne potvrdi ili odustane
$rezultat = $stanjeKase['rezultat'];
if ($rezultat !== null && $rezultat['ishod'] !== 'moze') {
    $stanjeKase['rezultat'] = null;
}
if ($rezultat !== null && $rezultat['ishod'] === 'moze'
    && ($stanjeKase['provera'] === null || time() - $stanjeKase['provera']['vreme'] > ROK_ZA_POTVRDU)) {
    $rezultat = null;
    $stanjeKase['rezultat'] = null;
    $stanjeKase['provera'] = null;
}

$danasBrojevi = $kasa->danasUMenzi((int)$menza['id']);
$skeniranja = $stanjeKase['skeniranja'];
$csrfToken = $adminAuth->generateCsrfToken();

require __DIR__ . '/views/kasir_bonovi.view.php';
