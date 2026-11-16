<?php
/**
 * LOGIKA: Moji obroci – preostali obroci u tekućem mesecu, QR bon i poslednja iskorišćenja.
 * Izgled je u views/obroci.view.php.
 *
 * Adresa: obroci.php           – bon za prvi obrok kojeg još ima
 *         obroci.php?tip=2     – bon za ručak (id iz tabele tipovi_obroka)
 *         obroci.php?bon=2     – samo novi tekst bona kao JSON, za osvežavanje QR koda bez ponovnog učitavanja:
 *                                {"tekst": "DM1.1.2.1793915376.a1b2c3d4e5f6a7b8", "osvezi": 60}
 *                                ili {"greska": "..."} (HTTP 409) kad tog obroka više nema
 *
 * Bon se pravi samo za obrok kojeg student još ima u ovom mesecu (vidi src/Bon.php) i na koji njegova
 * kategorija ima pravo. Student koji je prebačen u kategoriju "Kragujevac" (samo ručak) ne dobija bon za
 * ranije kupljene doručke i večere – kasa ih ne izdaje, a novac za njih mu se refundira.
 *
 * Prikaz dobija:
 *   $student          Student
 *   $kartica          Kartica        formatiranBroj()
 *   $nazivMeseca      string         npr. 'novembar 2026'
 *   $vaziDo           string         npr. '30.11.2026.' (poslednji dan meseca)
 *   $obroci           array          po tipu: [['tip_obroka_id' => 2, 'naziv' => 'Ručak', 'kupljeno' => 10, 'iskorisceno' => 3, 'preostalo' => 7,
 *                                    'dozvoljen' => true (false = kategorija studenta nema pravo na taj obrok)], ...]
 *   $zaRefundaciju    int            koliko preostalih obroka student više ne može da iskoristi (čekaju refundaciju)
 *   $ukupnoKupljeno   int            zbir za ceo mesec (npr. "21 od 30")
 *   $ukupnoPreostalo  int
 *   $izabraniTip      int|null       id tipa obroka za koji je bon (null = nijednog obroka nema)
 *   $bonTekst         string|null    tekst koji ide u QR kod (null kad nema obroka tog tipa)
 *   $bonOsvezavanje   int            na koliko sekundi se traži novi bon (60)
 *   $iskoriscenja     array          poslednjih 5: [['tip_obroka_id', 'obrok' => 'Doručak', 'menza' => 'Menza Ekonomski', 'iskorisceno' => '2026-10-13 08:12:00'], ...]
 *   $poruka           array|null     ['tip' => 'success'|'danger', 'tekst' => '...'] (npr. posle kupovine)
 */
require_once __DIR__ . '/includes/provera_prijave.php';
require_once __DIR__ . '/src/StudentRepository.php';
require_once __DIR__ . '/src/KarticaRepository.php';
require_once __DIR__ . '/src/KupljeniObrokRepository.php';
require_once __DIR__ . '/src/Bon.php';
require_once __DIR__ . '/src/Poruka.php';
require_once __DIR__ . '/src/Format.php';

$mesec = date('Y-m-01');
$kupljeniRepo = new KupljeniObrokRepository();
$student = (new StudentRepository())->read($studentId);

$obroci = $kupljeniRepo->stanjeZaMesec($karticaId, $mesec);
$zaRefundaciju = 0;
foreach ($obroci as &$obrok) {
    $obrok['dozvoljen'] = $student->smeDaKupi((int)$obrok['tip_obroka_id']);
    if (!$obrok['dozvoljen']) {
        $zaRefundaciju += (int)$obrok['preostalo'];
    }
}
unset($obrok);
$poTipu = array_column($obroci, null, 'tip_obroka_id');

/** Bon postoji samo za obrok kojeg još ima i na koji kategorija studenta ima pravo */
$imaBon = fn(int $tipId): bool => isset($poTipu[$tipId]) && $poTipu[$tipId]['preostalo'] > 0 && $poTipu[$tipId]['dozvoljen'];

// ---------- Osvežavanje bona (poziva js/bon_qr.js) ----------
if (isset($_GET['bon'])) {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');

    $tipId = (int)$_GET['bon'];
    if (!$imaBon($tipId)) {
        http_response_code(409);
        echo json_encode(['greska' => 'Nema više obroka ove vrste za ovaj mesec.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode(['tekst' => Bon::napravi($karticaId, $tipId), 'osvezi' => Bon::OSVEZAVANJE]);
    exit;
}

// ---------- Strana ----------
$ukupnoKupljeno = array_sum(array_column($obroci, 'kupljeno'));
$ukupnoPreostalo = array_sum(array_column($obroci, 'preostalo'));

// Izabrani obrok: iz adrese, inače prvi za koji postoji bon
$izabraniTip = isset($_GET['tip']) && isset($poTipu[(int)$_GET['tip']]) ? (int)$_GET['tip'] : null;
if ($izabraniTip === null) {
    foreach ($obroci as $o) {
        if ($imaBon((int)$o['tip_obroka_id'])) {
            $izabraniTip = (int)$o['tip_obroka_id'];
            break;
        }
    }
}

$bonTekst = ($izabraniTip !== null && $imaBon($izabraniTip))
    ? Bon::napravi($karticaId, $izabraniTip)
    : null;
$bonOsvezavanje = Bon::OSVEZAVANJE;

$kartica = (new KarticaRepository())->read($karticaId);
$nazivMeseca = Format::mesecGodina($mesec);
$vaziDo = date('t.m.Y.');
$iskoriscenja = $kupljeniRepo->poslednjaIskoriscenja($karticaId, 5);
$poruka = Poruka::uzmi();

require __DIR__ . '/views/obroci.view.php';
