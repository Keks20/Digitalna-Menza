<?php
/**
 * LOGIKA: admin – smeštaj: ko je platio stanarinu za mesec i pravljenje mesečnih zaduženja.
 * Izgled je u views/admin_smestaj.view.php. Samo za ulogu admin.
 *
 * Adresa: admin_smestaj.php                                           – tekući mesec, svi
 *         admin_smestaj.php?mesec=2026-10-01&status=kasni&dom=Dom+1&q=Marko
 *
 * Forma za nova zaduženja šalje POST: akcija=napravi_zaduzenja, mesec, csrf_token
 * (može samo za tekući i sledeći mesec; ugovori koji već imaju zaduženje se preskaču).
 *
 * Prikaz dobija:
 *   $admin            Admin
 *   $mesec            string      'Y-m-01'
 *   $nazivMeseca      string      npr. 'Novembar 2026'
 *   $prethodniMesec   string      'Y-m-01' (za strelicu levo)
 *   $sledeciMesec     string      'Y-m-01' (za strelicu desno)
 *   $FILTERI          array       ['' => 'Sve', 'neplaceno' => 'Neplaćeno', 'kasni' => 'Kasni', 'placeno' => 'Plaćeno']
 *   $filterStatus     string
 *   $dom              string      '' = svi domovi
 *   $pretraga         string      ime, indeks ili broj kartice
 *   $domovi           string[]    ['Dom 1', 'Dom 2', ...]
 *   $zaduzenja        array       [['id', 'ime', 'prezime', 'broj_indeksa', 'broj_kartice', 'dom', 'paviljon', 'soba',
 *                                   'iznos', 'rok_placanja', 'placeno', 'datum_placanja', 'kasni' => bool], ...] neplaćena prva
 *   $zbir             array       ['broj', 'placeno_broj', 'kasni_broj', 'ukupno', 'naplaceno'] za ceo mesec (bez filtera)
 *   $mozeNapraviti    bool        da li se za ovaj mesec prikazuje dugme "Napravi zaduženja"
 *   $poruka           array|null  ['tip' => 'success'|'danger', 'tekst' => '...']
 *   $csrfToken        string
 *   adminSmestajUrl([...])        funkcija – link sa trenutnim filterom i izmenama, npr. adminSmestajUrl(['status' => 'kasni'])
 */
$dozvoljeneUloge = ['admin'];
require_once __DIR__ . '/includes/provera_admina.php';
require_once __DIR__ . '/src/SmestajRepository.php';
require_once __DIR__ . '/src/Poruka.php';
require_once __DIR__ . '/src/Format.php';

$FILTERI = [
    '' => 'Sve',
    'neplaceno' => 'Neplaćeno',
    'kasni' => 'Kasni',
    'placeno' => 'Plaćeno',
];

$smestajRepo = new SmestajRepository();
$domovi = $smestajRepo->domovi();
$tekuci = date('Y-m-01');
$sledeci = date('Y-m-01', strtotime('first day of next month'));

// ---------- Filter iz adrese ----------
$mesec = (string)($_POST['mesec'] ?? $_GET['mesec'] ?? $tekuci);
$datumMeseca = DateTime::createFromFormat('!Y-m-d', $mesec);
if ($datumMeseca === false || $datumMeseca->format('Y-m-d') !== $mesec || $datumMeseca->format('d') !== '01') {
    $mesec = $tekuci;
}

$filterStatus = (string)($_GET['status'] ?? '');
if (!isset($FILTERI[$filterStatus])) {
    $filterStatus = '';
}
$dom = (string)($_GET['dom'] ?? '');
if (!in_array($dom, $domovi, true)) {
    $dom = '';
}
$pretraga = trim((string)($_GET['q'] ?? ''));

/** Link na stranicu sa trenutnim filterom; prazne vrednosti i tekući mesec se izostavljaju */
function adminSmestajUrl(array $izmene = []): string
{
    global $mesec, $filterStatus, $dom, $pretraga, $tekuci;
    $parametri = array_merge(['mesec' => $mesec, 'status' => $filterStatus, 'dom' => $dom, 'q' => $pretraga], $izmene);
    $parametri = array_filter($parametri, fn($v) => $v !== '' && $v !== null);
    if (($parametri['mesec'] ?? '') === $tekuci) {
        unset($parametri['mesec']);
    }
    return 'admin_smestaj.php' . ($parametri ? '?' . http_build_query($parametri) : '');
}

// ---------- Nova zaduženja ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['akcija'] ?? '') === 'napravi_zaduzenja') {
    if (!$adminAuth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
        Poruka::postavi('danger', 'Sesija je istekla. Osveži stranicu i pokušaj ponovo.');
    } elseif ($mesec !== $tekuci && $mesec !== $sledeci) {
        Poruka::postavi('danger', 'Zaduženja mogu da se naprave samo za tekući i sledeći mesec.');
    } else {
        $broj = $smestajRepo->napraviZaduzenja($mesec);
        Poruka::postavi('success', $broj > 0
            ? 'Napravljeno je ' . $broj . ' ' . Format::mnozina($broj, 'zaduženje', 'zaduženja', 'zaduženja') . ' za ' . Format::mesecGodina($mesec) . '.'
            : 'Svi aktivni ugovori već imaju zaduženje za ' . Format::mesecGodina($mesec) . '.');
    }
    header('Location: ' . adminSmestajUrl());
    exit;
}

// ---------- Prikaz ----------
$nazivMeseca = ucfirst(Format::mesecGodina($mesec));
$prethodniMesec = date('Y-m-01', strtotime($mesec . ' -1 month'));
$sledeciMesec = date('Y-m-01', strtotime($mesec . ' +1 month'));
$mozeNapraviti = $mesec === $tekuci || $mesec === $sledeci;

$zaduzenja = $smestajRepo->zaduzenjaZaMesec($mesec, ['status' => $filterStatus, 'dom' => $dom, 'pretraga' => $pretraga]);
$danas = date('Y-m-d');
foreach ($zaduzenja as &$z) {
    $z['placeno'] = (bool)$z['placeno'];
    $z['kasni'] = !$z['placeno'] && $z['rok_placanja'] < $danas;
}
unset($z);

$zbir = $smestajRepo->zbirZaMesec($mesec);
$poruka = Poruka::uzmi();
$csrfToken = $adminAuth->generateCsrfToken();

require __DIR__ . '/views/admin_smestaj.view.php';
