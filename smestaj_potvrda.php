<?php
/**
 * LOGIKA: potvrda o plaćenoj stanarini (link "Potvrda" u tabeli zaduženja).
 * Izgled je u views/smestaj_potvrda.view.php – strana je napravljena i za štampu (Ctrl+P / Sačuvaj kao PDF).
 *
 * Adresa: smestaj_potvrda.php?id=12 – student vidi samo svoja plaćena zaduženja,
 * za tuđe, nepostojeće ili neplaćeno vraća se na smestaj.php.
 *
 * Prikaz dobija:
 *   $student           Student
 *   $kartica           Kartica             formatiranBroj()
 *   $zaduzenje         SmestajZaduzenje    mesec, iznos, rok_placanja, datum_placanja, dom, paviljon, soba
 *   $ugovor            array|null          skolska_godina, vazi_do (aktivan ugovor, za zaglavlje potvrde)
 *   $brojTransakcije   string              npr. "TX-2026-11-000123"
 *   $nacinPlacanja     string              npr. "Sa studentske kartice"
 */
require_once __DIR__ . '/includes/provera_prijave.php';
require_once __DIR__ . '/src/StudentRepository.php';
require_once __DIR__ . '/src/KarticaRepository.php';
require_once __DIR__ . '/src/SmestajRepository.php';
require_once __DIR__ . '/src/TransakcijaRepository.php';
require_once __DIR__ . '/src/Format.php';

$smestajRepo = new SmestajRepository();
$zaduzenje = $smestajRepo->zaduzenjeStudenta($studentId, (int)($_GET['id'] ?? 0));

if ($zaduzenje === null || !$zaduzenje->placeno) {
    header('Location: smestaj.php');
    exit;
}

$transakcija = $zaduzenje->transakcija_id !== null ? (new TransakcijaRepository())->read((int)$zaduzenje->transakcija_id) : null;
$brojTransakcije = $transakcija ? Format::brojTransakcije((int)$transakcija->id, $transakcija->kreirano) : '–';
$nacinPlacanja = $transakcija ? Format::nacinPlacanja($transakcija->nacin) : '–';

$student = (new StudentRepository())->read($studentId);
$kartica = (new KarticaRepository())->read($karticaId);
$ugovor = $smestajRepo->ugovorStudenta($studentId);

require __DIR__ . '/views/smestaj_potvrda.view.php';
