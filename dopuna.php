<?php
/**
 * LOGIKA: dopuna kartice – korak 1: iznos i način plaćanja.
 * Izgled je u views/dopuna.view.php.
 *
 * POST (iznos, drugi_iznos, nacin, csrf_token) -> dopuna_kartica.php ili dopuna_ips.php
 *   iznos        – jedan od ponuđenih iznosa ili 'drugi'
 *   drugi_iznos  – upisan iznos kad je iznos = 'drugi'
 *   nacin        – platna_kartica | ips_qr
 *
 * Prikaz dobija:
 *   $student           Student      za zaglavlje
 *   $kartica           Kartica      stanje, formatiranBroj()
 *   $ponudjeniIznosi   int[]        [500, 1000, 2000, 3000, 5000]
 *   $najmanjiIznos, $najveciIznos  int  granice za "Drugi iznos"
 *   $forma             array        ['iznos' => 2000 | 'drugi', 'drugi_iznos' => '', 'nacin' => 'platna_kartica']
 *   $poslednjeUplate   array        2 poslednje uplate: id, iznos, nacin, status, kreirano, refundirano (null = nije refundirana)
 *   $refundabilne      array        [id uplate => rok '2026-11-13 15:42:10'] – uplate koje još mogu da se refundiraju
 *   $greske            string[]
 *   $poruka            array|null   npr. posle odustajanja od plaćanja
 *   $csrfToken         string
 */
require_once __DIR__ . '/includes/provera_prijave.php';
require_once __DIR__ . '/src/StudentRepository.php';
require_once __DIR__ . '/src/KarticaRepository.php';
require_once __DIR__ . '/src/TransakcijaRepository.php';
require_once __DIR__ . '/src/Dopuna.php';
require_once __DIR__ . '/src/Poruka.php';
require_once __DIR__ . '/src/Format.php';

$student = (new StudentRepository())->read($studentId);
$kartica = (new KarticaRepository())->read($karticaId);

$ponudjeniIznosi = Dopuna::PONUDJENI_IZNOSI;
$najmanjiIznos = Dopuna::NAJMANJI_IZNOS;
$najveciIznos = Dopuna::NAJVECI_IZNOS;

// Podrazumevano: 2.000 RSD platnom karticom (kao u Figmi)
$forma = ['iznos' => 2000, 'drugi_iznos' => '', 'nacin' => 'platna_kartica'];
$greske = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $forma = [
        'iznos' => ($_POST['iznos'] ?? '') === 'drugi' ? 'drugi' : (int)($_POST['iznos'] ?? 0),
        'drugi_iznos' => trim((string)($_POST['drugi_iznos'] ?? '')),
        'nacin' => (string)($_POST['nacin'] ?? ''),
    ];

    if (!$auth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $greske[] = 'Sesija je istekla. Osveži stranicu i pokušaj ponovo.';
    } else {
        if ($forma['iznos'] === 'drugi') {
            $iznos = str_replace(['.', ' '], '', $forma['drugi_iznos']);   // "2.500" -> "2500"
        } elseif (in_array($forma['iznos'], $ponudjeniIznosi, true)) {
            $iznos = $forma['iznos'];
        } else {
            $iznos = null;
        }

        $greske = $iznos === null ? ['Izaberi iznos dopune.'] : Dopuna::proveri($iznos, $forma['nacin']);

        if (empty($greske) && !$kartica->jeAktivna()) {
            $greske[] = 'Kartica je blokirana – dopuna nije moguća. Javi se na šalter Studentskog centra.';
        }

        if (empty($greske)) {
            Dopuna::zapocni((int)$iznos, $forma['nacin']);
            header('Location: ' . ($forma['nacin'] === 'ips_qr' ? 'dopuna_ips.php' : 'dopuna_kartica.php'));
            exit;
        }
    }
}

$transakcijaRepo = new TransakcijaRepository();
$poslednjeUplate = $transakcijaRepo->list(['kartica_id' => $karticaId, 'tip' => 'uplata', 'limit' => 2]);
$refundabilne = $transakcijaRepo->refundabilneUplate($karticaId);
$poruka = Poruka::uzmi();
$csrfToken = $auth->generateCsrfToken();

require __DIR__ . '/views/dopuna.view.php';
