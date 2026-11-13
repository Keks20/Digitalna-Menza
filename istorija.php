<?php
/**
 * LOGIKA: istorija transakcija studenta (uplate, kupovine obroka, smeštaj).
 * Izgled je u views/istorija.view.php.
 *
 * Adresa: istorija.php                                       – sve transakcije od početka prošlog meseca do danas
 *         istorija.php?tip=uplata&od=2026-09-01&do=2026-10-19 – filter po tipu i periodu
 *         istorija.php?...&strana=2                          – sledećih 10
 *         istorija.php?...&izvoz=csv                         – preuzimanje CSV fajla (sve iz filtera, bez strana)
 *         istorija.php?...&izvoz=pdf                         – strana za štampu, pregledač je čuva kao PDF
 *
 * Prikaz dobija:
 *   $student       Student
 *   $kartica       Kartica         stanje, formatiranBroj()
 *   $TIPOVI        array           ['' => 'Sve', 'uplata' => 'Uplate', 'obroci' => 'Obroci', 'smestaj' => 'Smeštaj']
 *   $tip           string          izabrani tip ('' = sve)
 *   $od, $do       string          period, 'Y-m-d'
 *   $transakcije   Transakcija[]   transakcije na ovoj strani (kod PDF-a sve)
 *   $zbirovi       array           ['uplata' => 10000.0, 'obroci' => -5320.0, 'smestaj' => -2800.0] uspešne u periodu
 *   $ukupno        int             broj transakcija za filter
 *   $strana        int             trenutna strana (od 1)
 *   $brojStrana    int
 *   $poStrani      int
 *   $stampa        bool            true = izgled za štampu / PDF
 *   $greska        string          poruka kad je period pogrešno unet ('' ako je sve u redu)
 *   $refundabilne  array           [id uplate => rok '2026-11-13 15:42:10'] – uplate koje još mogu da se refundiraju;
 *                                  uz njih ide forma: POST na refundacija.php (id, nazad=istorija, csrf_token)
 *   $poruka        array|null      ['tip' => 'success'|'danger', 'tekst' => '...'] posle refundacije
 *   $csrfToken     string
 *   istorijaUrl([...])             funkcija – link sa trenutnim filterom i izmenama, npr. istorijaUrl(['strana' => 2])
 *
 * Imena polja u formi (GET): tip, od, do
 */
require_once __DIR__ . '/includes/provera_prijave.php';
require_once __DIR__ . '/src/StudentRepository.php';
require_once __DIR__ . '/src/KarticaRepository.php';
require_once __DIR__ . '/src/TransakcijaRepository.php';
require_once __DIR__ . '/src/Poruka.php';
require_once __DIR__ . '/src/Format.php';

const PO_STRANI = 10;
const NAJVISE_ZA_IZVOZ = 1000;

$TIPOVI = [
    '' => 'Sve',
    'uplata' => 'Uplate',          // sa refundacijama
    'obroci' => 'Obroci',
    'smestaj' => 'Smeštaj',
];

/** Da li je tekst ispravan datum u obliku 2026-10-19 */
function ispravanDatum(string $datum): bool
{
    $d = DateTime::createFromFormat('!Y-m-d', $datum);
    return $d !== false && $d->format('Y-m-d') === $datum;
}

// ---------- Filter iz adrese ----------
$tip = (string)($_GET['tip'] ?? '');
if (!isset($TIPOVI[$tip])) {
    $tip = '';
}

// Podrazumevani period: od prvog dana prošlog meseca do danas
$od = (string)($_GET['od'] ?? '');
$do = (string)($_GET['do'] ?? '');
$greska = '';
if ($od === '' && $do === '') {
    $od = date('Y-m-01', strtotime('first day of last month'));
    $do = date('Y-m-d');
} elseif (!ispravanDatum($od) || !ispravanDatum($do)) {
    $greska = 'Period nije dobro unet – prikazan je period od početka prošlog meseca do danas.';
    $od = date('Y-m-01', strtotime('first day of last month'));
    $do = date('Y-m-d');
} elseif ($od > $do) {
    [$od, $do] = [$do, $od];
}

$filter = ['tip' => $tip, 'od' => $od, 'do' => $do];

/** Link na istoriju sa trenutnim filterom; prazne vrednosti i prva strana se izostavljaju */
function istorijaUrl(array $izmene = []): string
{
    global $filter, $strana;
    $parametri = array_merge($filter, ['strana' => $strana ?? 1], $izmene);
    $parametri = array_filter($parametri, fn($v) => $v !== '' && $v !== null && $v !== 1);
    return 'istorija.php' . ($parametri ? '?' . http_build_query($parametri) : '');
}

$transakcijaRepo = new TransakcijaRepository();
$izvoz = (string)($_GET['izvoz'] ?? '');

// ---------- Izvoz u CSV (Excel ga otvara direktno) ----------
if ($izvoz === 'csv') {
    $sve = $transakcijaRepo->istorija($karticaId, $filter + ['limit' => NAJVISE_ZA_IZVOZ]);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="istorija_' . $od . '_' . $do . '.csv"');

    $izlaz = fopen('php://output', 'w');
    fwrite($izlaz, "\xEF\xBB\xBF");   // BOM da Excel prepozna UTF-8 (š, đ, č...)
    fputcsv($izlaz, ['Datum', 'Broj transakcije', 'Opis', 'Tip', 'Način plaćanja', 'Status', 'Iznos (RSD)', 'Stanje posle (RSD)'], ';');
    foreach ($sve as $t) {
        fputcsv($izlaz, [
            date('d.m.Y. H:i', strtotime($t->kreirano)),
            Format::brojTransakcije((int)$t->id, $t->kreirano),
            $t->opis,
            Format::tipTransakcije($t->tip),
            Format::nacinPlacanja($t->nacin),
            Format::statusTransakcije($t->status),
            number_format($t->iznos, 2, ',', ''),
            $t->stanje_posle !== null ? number_format($t->stanje_posle, 2, ',', '') : '',
        ], ';');
    }
    fclose($izlaz);
    exit;
}

// ---------- Strana ili štampa ----------
$stampa = $izvoz === 'pdf';
$ukupno = $transakcijaRepo->brojUIstoriji($karticaId, $filter);
$poStrani = PO_STRANI;
$brojStrana = max(1, (int)ceil($ukupno / $poStrani));
$strana = min(max(1, (int)($_GET['strana'] ?? 1)), $brojStrana);

if ($stampa) {
    $transakcije = $transakcijaRepo->istorija($karticaId, $filter + ['limit' => NAJVISE_ZA_IZVOZ]);
} else {
    $transakcije = $transakcijaRepo->istorija($karticaId, $filter + ['limit' => $poStrani, 'offset' => ($strana - 1) * $poStrani]);
}

$zbirovi = $transakcijaRepo->zbirPoTipu($karticaId, $od, $do);
$student = (new StudentRepository())->read($studentId);
$kartica = (new KarticaRepository())->read($karticaId);

// U štampi nema dugmadi, pa ni refundacije
$refundabilne = $stampa ? [] : $transakcijaRepo->refundabilneUplate($karticaId);
$poruka = Poruka::uzmi();
$csrfToken = $auth->generateCsrfToken();

require __DIR__ . '/views/istorija.view.php';
