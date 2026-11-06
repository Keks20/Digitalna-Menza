<?php
/**
 * LOGIKA: administratorski pregled – dnevna statistika.
 * Izgled je u views/admin_pocetna.view.php. Samo za ulogu admin.
 *
 * Prikaz dobija:
 *   $admin              Admin      ime_prezime, inicijali(), nazivUloge()
 *   $danas              string     'Y-m-d'
 *   $uplateDanas        array      ['broj' => 12, 'zbir' => 48500.0] – uspešne uplate danas
 *   $uplatePromena      float|null promena zbira u odnosu na juče u % (12.0 = +12%), null kad juče nije bilo uplata
 *   $prodatoDanas       int        prodatih obroka danas
 *   $prodatoPromena     float|null promena u odnosu na juče u %
 *   $izdatoDanas        int        izdatih obroka danas (sve menze)
 *   $mogucihDanas       int        koliko je obroka danas moglo da se izda (studenti koji imaju obrok)
 *   $procenatIzdatih    int|null   izdatoDanas / mogucihDanas u %, null kad nema mogućih
 *   $niskoStanje        int        aktivnih kartica sa stanjem ispod $pragStanja
 *   $pragStanja         float      500.0
 *   $poslednjihSedam    array      [['datum' => '2026-10-13', 'dan' => 'Pon', 'izdato' => 812, 'danas' => false], ...] 7 dana, danas poslednji
 *   $ukupnoSedam        int        zbir izdatih za 7 dana
 *   $poMenzama          array      [['id', 'naziv' => 'Menza Ekonomski', 'izdato' => 412, 'kapacitet' => 600, 'procenat' => 69], ...]
 *   $poslednjeUplate    array      5 najnovijih uplata: [['id', 'kreirano', 'ime', 'prezime', 'broj_kartice', 'nacin', 'iznos', 'status'], ...]
 *   $brojNaCekanju      int        IPS uplate koje čekaju potvrdu
 */
$dozvoljeneUloge = ['admin'];
require_once __DIR__ . '/includes/provera_admina.php';
require_once __DIR__ . '/src/TransakcijaRepository.php';
require_once __DIR__ . '/src/Statistika.php';
require_once __DIR__ . '/src/Format.php';

const PRAG_STANJA = 500.0;
const BROJ_POSLEDNJIH_UPLATA = 5;

$danas = date('Y-m-d');
$juce = date('Y-m-d', strtotime('-1 day'));

$transakcijaRepo = new TransakcijaRepository();
$statistika = new Statistika();

// ---------- Kartice gore ----------
$uplateDanas = $transakcijaRepo->uplateZaDan($danas);
$uplatePromena = Statistika::promena($uplateDanas['zbir'], $transakcijaRepo->uplateZaDan($juce)['zbir']);

$prodatoDanas = $statistika->prodatoObroka($danas);
$prodatoPromena = Statistika::promena($prodatoDanas, $statistika->prodatoObroka($juce));

$izdatoDanas = $statistika->izdatoObroka($danas);
$mogucihDanas = $statistika->mogucihObroka($danas);
$procenatIzdatih = $mogucihDanas > 0 ? (int)round($izdatoDanas / $mogucihDanas * 100) : null;

$pragStanja = PRAG_STANJA;
$niskoStanje = $statistika->niskoStanje($pragStanja);

// ---------- Izdati obroci za poslednjih 7 dana ----------
$poslednjihSedam = [];
foreach ($statistika->izdatoPoDanima(date('Y-m-d', strtotime('-6 days')), $danas) as $datum => $broj) {
    $poslednjihSedam[] = [
        'datum' => $datum,
        'dan' => Format::danKratko($datum),
        'izdato' => $broj,
        'danas' => $datum === $danas,
    ];
}
$ukupnoSedam = array_sum(array_column($poslednjihSedam, 'izdato'));

// ---------- Danas po menzama ----------
$poMenzama = $statistika->poMenzama($danas);
foreach ($poMenzama as &$menza) {
    $menza['procenat'] = $menza['kapacitet'] > 0 ? min(100, (int)round($menza['izdato'] / $menza['kapacitet'] * 100)) : 0;
}
unset($menza);

// ---------- Poslednje uplate ----------
$poslednjeUplate = $transakcijaRepo->uplateZaAdmina(['limit' => BROJ_POSLEDNJIH_UPLATA]);
$brojNaCekanju = $transakcijaRepo->brojUplataNaCekanju();

require __DIR__ . '/views/admin_pocetna.view.php';
