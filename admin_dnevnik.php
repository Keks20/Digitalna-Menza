<?php
/**
 * LOGIKA: dnevnik akcija zaposlenih – ko je, kada i šta uradio (samo administrator).
 * Izgled je u views/admin_dnevnik.view.php.
 *
 * Adresa: admin_dnevnik.php                          – svi zapisi, najnoviji prvo
 *         admin_dnevnik.php?akcija=kartica&q=Marko   – filter po vrsti akcije i pretraga
 *         admin_dnevnik.php?...&strana=2             – sledećih 30
 *
 *   akcija  – '' (sve) | prijava | kartica | kategorija | cene | obavestenje | zaduzenja
 *   q       – deo opisa ili imena zaposlenog
 *
 * Stranica samo prikazuje: zapisi se ne menjaju i ne brišu. Upisuju ih stranice na kojima se akcija dešava
 * (admin_login.php, admin_studenti.php, admin_jelovnik.php, admin_uplate.php, admin_smestaj.php)
 * preko AdminDnevnikRepository::upisi().
 *
 * Prikaz dobija:
 *   $admin          Admin        prijavljeni administrator
 *   $zapisi         array        zapisi na ovoj strani: [['id', 'admin_ime', 'akcija', 'naziv_akcije' (npr. "Kartica"),
 *                                'opis', 'ip', 'vreme'], ...]
 *   $filterAkcija   string       '' ili ključ iz $FILTERI
 *   $FILTERI        array        ['' => 'Sve', 'prijava' => 'Prijava', 'kartica' => 'Kartica', ...] za dugmad filtera
 *   $q              string       tekst iz polja za pretragu
 *   $ukupno         int          koliko zapisa odgovara filteru
 *   $strana         int          trenutna strana (od 1)
 *   $brojStrana     int
 *   $poStrani       int          30
 *   adminDnevnikUrl([...])       funkcija – link sa trenutnim filterom i izmenama, npr. adminDnevnikUrl(['strana' => 2])
 */
$dozvoljeneUloge = ['admin'];
require_once __DIR__ . '/includes/provera_admina.php';
require_once __DIR__ . '/src/AdminDnevnikRepository.php';
require_once __DIR__ . '/src/Format.php';

const PO_STRANI = 30;
const MAKS_PRETRAGA = 100;

$FILTERI = ['' => 'Sve'] + AdminDnevnikRepository::AKCIJE;

$filterAkcija = (string)($_GET['akcija'] ?? '');
if (!isset($FILTERI[$filterAkcija])) {
    $filterAkcija = '';
}
$q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, MAKS_PRETRAGA);
$strana = max(1, (int)($_GET['strana'] ?? 1));

/** Link na dnevnik sa trenutnim filterom; prazne vrednosti i prva strana se izostavljaju */
function adminDnevnikUrl(array $izmene = []): string
{
    global $filterAkcija, $q, $strana;
    $parametri = array_merge(['akcija' => $filterAkcija, 'q' => $q, 'strana' => $strana], $izmene);
    $parametri = array_filter($parametri, fn($v) => $v !== '' && $v !== null && $v !== 1);
    return 'admin_dnevnik.php' . ($parametri ? '?' . http_build_query($parametri) : '');
}

$dnevnikRepo = new AdminDnevnikRepository();
$uslovi = ['akcija' => $filterAkcija, 'pretraga' => $q];

$ukupno = $dnevnikRepo->broj($uslovi);
$poStrani = PO_STRANI;
$brojStrana = max(1, (int)ceil($ukupno / $poStrani));
$strana = min($strana, $brojStrana);

$zapisi = $dnevnikRepo->list($uslovi + ['limit' => $poStrani, 'offset' => ($strana - 1) * $poStrani]);
foreach ($zapisi as &$zapis) {
    $zapis['naziv_akcije'] = AdminDnevnikRepository::AKCIJE[$zapis['akcija']] ?? $zapis['akcija'];
}
unset($zapis);

require __DIR__ . '/views/admin_dnevnik.view.php';
