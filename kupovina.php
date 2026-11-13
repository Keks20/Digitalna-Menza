<?php
/**
 * LOGIKA: kupovina mesečnih obroka sa studentske kartice.
 * Izgled je u views/kupovina.view.php.
 *
 * Adresa: kupovina.php                    – tekući mesec
 *         kupovina.php?mesec=2026-12-01   – sledeći mesec (drugi meseci nisu dozvoljeni)
 *
 * Forma šalje POST: akcija=kupi, mesec, kolicina[<id tipa obroka>] (0 = taj obrok se ne kupuje), csrf_token.
 * Posle uspešne kupovine preusmerava na obroci.php (Moji obroci) sa porukom.
 *
 * Kategorija studenta (src/Student.php) određuje šta i po kojoj ceni može da kupi:
 *   – samofinansirajući studenti plaćaju trostruku cenu obroka,
 *   – studenti koji žive u Kragujevcu (a nisu u domu) mogu da kupe samo ručak.
 * Cena se uvek računa na serveru iz osnovne cene u bazi i kategorije studenta.
 *
 * Ograničenje: najviše jedan obrok svake vrste po danu. Za tekući mesec to znači
 * najviše onoliko koliko je ostalo dana (računajući danas), umanjeno za već kupljene a neiskorišćene.
 *
 * Prikaz dobija:
 *   $student      Student         nazivKategorije(), opisKategorije(), samoRucak(), jeSamofinansirajuci()
 *   $kartica      Kartica         stanje, formatiranBroj()
 *   $meseci       array           [['datum' => '2026-11-01', 'naziv' => 'Novembar 2026', 'vazi_do' => '30.11.2026.'], ...] (tekući i sledeći)
 *   $mesec        string          izabrani mesec, 'Y-m-01'
 *   $nazivMeseca  string          npr. 'Novembar 2026'
 *   $vaziDo       string          npr. '30.11.2026.'
 *   $obroci       array           po tipu obroka: [['tip' => TipObroka, 'cena' => 120.0, 'dozvoljen' => true,
 *                                 'kupljeno' => 10, 'preostalo' => 7, 'maks' => 18], ...]
 *                                 cena = cena jednog obroka za ovog studenta (ne osnovna cena iz tipa)
 *                                 dozvoljen = false kad kategorija studenta nema pravo na taj obrok (tada je maks = 0)
 *                                 maks = koliko još sme da se kupi za izabrani mesec
 *   $forma        array           ['kolicine' => [tip_id => broj]] – broj u polju (posle greške ostaje uneto)
 *   $menze        array           [['id', 'naziv', ...], ...] (za napomenu gde važe obroci)
 *   $poruka       array|null      ['tip' => 'success'|'danger', 'tekst' => '...']
 *   $greske       string[]
 *   $csrfToken    string
 *
 * Cene i iznosi u pregledu se računaju u pregledaču samo za prikaz – server sve ponovo računa iz baze.
 */
require_once __DIR__ . '/includes/provera_prijave.php';
require_once __DIR__ . '/src/StudentRepository.php';
require_once __DIR__ . '/src/KarticaRepository.php';
require_once __DIR__ . '/src/TransakcijaRepository.php';
require_once __DIR__ . '/src/KupljeniObrokRepository.php';
require_once __DIR__ . '/src/TipObrokaRepository.php';
require_once __DIR__ . '/src/MenzaRepository.php';
require_once __DIR__ . '/src/Poruka.php';
require_once __DIR__ . '/src/Format.php';

const PODRAZUMEVANO_OBROKA = 10;

// Može da se kupi za tekući i za sledeći mesec
$tekuci = date('Y-m-01');
$sledeci = date('Y-m-01', strtotime('first day of next month'));
$meseci = [];
foreach ([$tekuci, $sledeci] as $datum) {
    $meseci[$datum] = [
        'datum' => $datum,
        'naziv' => ucfirst(Format::mesecGodina($datum)),
        'vazi_do' => date('t.m.Y.', strtotime($datum)),
    ];
}

$mesec = (string)($_POST['mesec'] ?? $_GET['mesec'] ?? $tekuci);
if (!isset($meseci[$mesec])) {
    $mesec = $tekuci;
}
$nazivMeseca = $meseci[$mesec]['naziv'];
$vaziDo = $meseci[$mesec]['vazi_do'];

$student = (new StudentRepository())->read($studentId);
$kartica = (new KarticaRepository())->read($karticaId);
$tipovi = (new TipObrokaRepository())->list();
$kupljeniRepo = new KupljeniObrokRepository();

// Koliko je dana u mesecu i koliko ih je još ostalo (za tekući mesec od danas)
$danaUMesecu = (int)date('t', strtotime($mesec));
$ostaloDana = $mesec === $tekuci ? $danaUMesecu - (int)date('j') + 1 : $danaUMesecu;

/** Po tipu obroka: kupljeno, preostalo i koliko još sme da se kupi */
function obrociZaKupovinu(Student $student, array $tipovi, array $stanje, int $danaUMesecu, int $ostaloDana): array
{
    $poTipu = array_column($stanje, null, 'tip_obroka_id');
    $obroci = [];
    foreach ($tipovi as $tip) {
        $kupljeno = (int)($poTipu[$tip->id]['kupljeno'] ?? 0);
        $preostalo = (int)($poTipu[$tip->id]['preostalo'] ?? 0);
        $dozvoljen = $student->smeDaKupi((int)$tip->id);
        $maks = $dozvoljen ? min($danaUMesecu - $kupljeno, $ostaloDana - $preostalo) : 0;
        $obroci[$tip->id] = [
            'tip' => $tip,
            'cena' => $student->cenaObroka($tip->cena),
            'dozvoljen' => $dozvoljen,
            'kupljeno' => $kupljeno,
            'preostalo' => $preostalo,
            'maks' => max(0, $maks),
        ];
    }
    return $obroci;
}

$obroci = obrociZaKupovinu($student, $tipovi, $kupljeniRepo->stanjeZaMesec($karticaId, $mesec), $danaUMesecu, $ostaloDana);

// Podrazumevano po 10 od svakog obroka (ili manje ako ne sme više)
$forma = ['kolicine' => []];
foreach ($obroci as $tipId => $o) {
    $forma['kolicine'][$tipId] = min(PODRAZUMEVANO_OBROKA, $o['maks']);
}
$greske = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['akcija'] ?? '') === 'kupi') {
    $unete = (array)($_POST['kolicina'] ?? []);
    $stavke = [];

    foreach ($obroci as $tipId => $o) {
        $vrednost = trim((string)($unete[$tipId] ?? '0'));
        $forma['kolicine'][$tipId] = $vrednost;

        if (!ctype_digit($vrednost)) {
            $greske[] = $o['tip']->naziv . ': broj obroka mora biti ceo broj.';
            continue;
        }
        $kolicina = (int)$vrednost;
        if ($kolicina > 0 && !$o['dozvoljen']) {
            $greske[] = $o['tip']->naziv . ': studenti koji žive u Kragujevcu mogu da kupe samo ručak.';
            continue;
        }
        if ($kolicina > $o['maks']) {
            $greske[] = $o['tip']->naziv . ': za ' . Format::mesecGodina($mesec) . ' može još najviše ' . $o['maks'] . '.';
            continue;
        }
        if ($kolicina > 0) {
            $stavke[$tipId] = ['kolicina' => $kolicina, 'cena' => $o['cena']];
        }
    }

    if (!$auth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $greske = ['Sesija je istekla. Osveži stranicu i pokušaj ponovo.'];
    } elseif (($_POST['mesec'] ?? '') !== $mesec) {
        // Mesec koji nije ponuđen se ne zamenjuje tiho drugim – kupovina se odbija
        $greske = ['Za izabrani mesec obroci ne mogu da se kupe.'];
    } elseif (empty($greske) && empty($stavke)) {
        $greske[] = 'Izaberi bar jedan obrok.';
    }

    if (empty($greske)) {
        // "Kupovina obroka za novembar 2026 (10 + 10 + 10)" – doručak + ručak + večera
        $brojevi = [];
        foreach ($obroci as $tipId => $o) {
            $brojevi[] = $stavke[$tipId]['kolicina'] ?? 0;
        }
        $opis = 'Kupovina obroka za ' . Format::mesecGodina($mesec) . ' (' . implode(' + ', $brojevi) . ')';

        try {
            $transakcijaId = (new TransakcijaRepository())->kupiObroke($karticaId, $mesec, $stavke, $danaUMesecu, $opis);

            $ukupno = 0.0;
            foreach ($stavke as $stavka) {
                $ukupno += $stavka['kolicina'] * $stavka['cena'];
            }
            Poruka::postavi('success', 'Obroci za ' . Format::mesecGodina($mesec) . ' su kupljeni (' . implode(' + ', $brojevi)
                . '). Sa kartice je skinuto ' . Format::novac($ukupno) . '.');
            header('Location: obroci.php');
            exit;
        } catch (RuntimeException $e) {
            // Poruke iz kupiObroke() su pisane za studenta (nema novca, blokirana kartica, ograničenje)
            $greske[] = $e->getMessage();
        } catch (mysqli_sql_exception $e) {
            $greske[] = 'Kupovina trenutno nije uspela. Pokušaj ponovo za par minuta.';
        }
    }
}

$menze = (new MenzaRepository())->list();
$poruka = Poruka::uzmi();
$csrfToken = $auth->generateCsrfToken();

require __DIR__ . '/views/kupovina.view.php';
