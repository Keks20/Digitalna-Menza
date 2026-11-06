<?php
/**
 * LOGIKA: studenti i njihove kartice – pretraga i blokiranje kartice (samo administrator).
 * Izgled je u views/admin_studenti.view.php.
 *
 * Adresa: admin_studenti.php                              – svi studenti, po prezimenu
 *         admin_studenti.php?q=Marko&status=blokirana     – pretraga i filter po statusu kartice
 *         admin_studenti.php?...&strana=2                 – sledećih 20
 *
 *   q       – ime, prezime, broj indeksa ili broj kartice (sa razmacima ili bez)
 *   status  – '' (sve) | aktivna | blokirana
 *
 * Forma za karticu šalje POST: akcija (blokiraj | odblokiraj), kartica_id, csrf_token
 * i skrivena polja filter_q, filter_status, filter_strana (da se posle izmene vrati na isti spisak).
 * Menja se samo kartice.status, nikad stanje. Posle izmene: preusmeravanje nazad sa porukom (Post/Redirect/Get).
 * Blokirana kartica ne može da se prijavi, dopuni, kupi obroke ni dobije obrok na kasi;
 * student koji je baš tada prijavljen biva odjavljen na sledećem kliku (includes/provera_prijave.php).
 *
 * Prikaz dobija:
 *   $admin          Admin        prijavljeni administrator
 *   $studenti       array        studenti na ovoj strani: [['id', 'ime', 'prezime', 'broj_indeksa', 'ustanova', 'email',
 *                                'kartica_id', 'broj_kartice', 'stanje', 'status'], ...]
 *   $q              string       tekst iz polja za pretragu
 *   $filterStatus   string       '' | 'aktivna' | 'blokirana'
 *   $FILTERI        array        ['' => 'Svi', 'aktivna' => 'Aktivne kartice', 'blokirana' => 'Blokirane']
 *   $brojPoStatusu  array        ['aktivna' => 29, 'blokirana' => 1] (za dugmad filtera)
 *   $ukupno         int          koliko studenata odgovara pretrazi
 *   $strana         int          trenutna strana (od 1)
 *   $brojStrana     int
 *   $poStrani       int          20
 *   adminStudentiUrl([...])      funkcija – link sa trenutnim filterom i izmenama, npr. adminStudentiUrl(['strana' => 2])
 *   $poruka         array|null   ['tip' => 'success'|'danger', 'tekst' => '...'] posle izmene
 *   $csrfToken      string
 */
$dozvoljeneUloge = ['admin'];
require_once __DIR__ . '/includes/provera_admina.php';
require_once __DIR__ . '/src/StudentRepository.php';
require_once __DIR__ . '/src/KarticaRepository.php';
require_once __DIR__ . '/src/Format.php';
require_once __DIR__ . '/src/Poruka.php';

const PO_STRANI = 20;
const MAKS_PRETRAGA = 100;

$FILTERI = [
    '' => 'Svi',
    'aktivna' => 'Aktivne kartice',
    'blokirana' => 'Blokirane',
];

// Filter iz adrese; kod POST-a stiže iz skrivenih polja da se posle izmene vrati na isti spisak
$q = mb_substr(trim((string)($_POST['filter_q'] ?? $_GET['q'] ?? '')), 0, MAKS_PRETRAGA);
$filterStatus = (string)($_POST['filter_status'] ?? $_GET['status'] ?? '');
if (!isset($FILTERI[$filterStatus])) {
    $filterStatus = '';
}
$strana = max(1, (int)($_POST['filter_strana'] ?? $_GET['strana'] ?? 1));

/** Link na spisak sa trenutnim filterom; prazne vrednosti i prva strana se izostavljaju */
function adminStudentiUrl(array $izmene = []): string
{
    global $q, $filterStatus, $strana;
    $parametri = array_merge(['q' => $q, 'status' => $filterStatus, 'strana' => $strana], $izmene);
    $parametri = array_filter($parametri, fn($v) => $v !== '' && $v !== null && $v !== 1);
    return 'admin_studenti.php' . ($parametri ? '?' . http_build_query($parametri) : '');
}

$studentRepo = new StudentRepository();
$karticaRepo = new KarticaRepository();

// =====================================================================
// POST – blokiranje / odblokiranje kartice
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $akcija = (string)($_POST['akcija'] ?? '');
    $karticaId = (int)($_POST['kartica_id'] ?? 0);
    $nazad = adminStudentiUrl();

    if (!$adminAuth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
        Poruka::postavi('danger', 'Sesija je istekla. Osveži stranicu i pokušaj ponovo.');
        header('Location: ' . $nazad);
        exit;
    }

    $noviStatus = ['blokiraj' => 'blokirana', 'odblokiraj' => 'aktivna'][$akcija] ?? null;
    $kartica = $karticaId > 0 ? $karticaRepo->read($karticaId) : null;
    $student = $kartica ? $studentRepo->read((int)$kartica->student_id) : null;

    if ($noviStatus === null) {
        Poruka::postavi('danger', 'Nepoznata akcija.');
    } elseif (!$kartica || !$student) {
        Poruka::postavi('danger', 'Kartica ne postoji.');
    } else {
        $opis = 'Kartica ' . $kartica->formatiranBroj() . ' (' . $student->punoIme() . ')';
        if ($karticaRepo->postaviStatus($karticaId, $noviStatus)) {
            Poruka::postavi('success', $opis . ($noviStatus === 'blokirana'
                ? ' je blokirana. Student ne može da se prijavi, dopuni karticu, kupi obroke ni dobije obrok na kasi.'
                : ' je ponovo aktivna.'));
        } else {
            Poruka::postavi('danger', $opis . ($noviStatus === 'blokirana' ? ' je već blokirana.' : ' je već aktivna.'));
        }
    }

    header('Location: ' . $nazad);
    exit;
}

// =====================================================================
// Podaci za prikaz
// =====================================================================
$uslovi = ['q' => $q, 'status' => $filterStatus];
$ukupno = $studentRepo->brojUPretrazi($uslovi);
$poStrani = PO_STRANI;
$brojStrana = max(1, (int)ceil($ukupno / $poStrani));
$strana = min($strana, $brojStrana);

$studenti = $studentRepo->pretraga($uslovi + ['limit' => $poStrani, 'offset' => ($strana - 1) * $poStrani]);
$brojPoStatusu = $studentRepo->brojPoStatusuKartice();
$poruka = Poruka::uzmi();
$csrfToken = $adminAuth->generateCsrfToken();

require __DIR__ . '/views/admin_studenti.view.php';
