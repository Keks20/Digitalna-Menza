<?php
/**
 * LOGIKA: uplate studenata i obaveštenja (samo administrator).
 * Izgled je u views/admin_uplate.view.php.
 *
 * Adresa: admin_uplate.php                          – sve uplate, nova poruka
 *         admin_uplate.php?status=ceka_potvrdu&q=Ana – filter uplata i pretraga
 *         admin_uplate.php?izmeni=4                 – forma popunjena obaveštenjem br. 4
 *
 * Forma šalje POST sa poljem "akcija":
 *   potvrdi  – uplata koja čeka postaje uspešna i novac se dodaje na karticu (polje id)
 *   odbij    – uplata koja čeka se odbija (polje id)
 *   objavi   – novo obaveštenje
 *   izmeni   – izmena postojećeg obaveštenja (polje id)
 *   obrisi   – brisanje obaveštenja (polje id)
 * Posle uspeha preusmeravanje nazad (Post/Redirect/Get), filter uplata ostaje isti.
 *
 * Prikaz dobija:
 *   $admin         Admin        prijavljeni administrator
 *   $uplate        array        uplate: ['id', 'kreirano', 'ime', 'prezime', 'broj_kartice', 'nacin', 'iznos', 'status']
 *   $filterStatus  string       '' (sve) | 'ceka_potvrdu' | 'odbijeno' | 'uspesno'
 *   $pretraga      string       tekst iz polja za pretragu
 *   $FILTERI       array        ['' => 'Sve', 'ceka_potvrdu' => 'Čeka potvrdu', ...] za dugmad filtera
 *   $uplateDanas   array        ['broj' => 47, 'zbir' => 48500.0] uspešne uplate danas
 *   $brojNaCekanju int          koliko uplata čeka potvrdu (za značku)
 *   adminUplateUrl(status, q)   funkcija – link za filter (zadržava pretragu)
 *   $menze         array        [['id', 'naziv', ...], ...] za izbor menze
 *   $domovi        string[]     ['Dom 1', ...] za izbor doma
 *   $forma         array        ['id' (null = nova), 'naslov', 'tekst', 'ciljna_grupa', 'menza_id', 'dom']
 *   $obavestenja   array        objavljena: ['id', 'naslov', 'tekst', 'kreirano', 'ciljna_grupa', 'menza', 'dom', 'autor']
 *   $NAZIVI_GRUPA  array        ['svi' => 'Svi studenti', 'menza' => 'Jedna menza', 'dom' => 'Jedan dom']
 *   $poruka        array|null   ['tip' => 'success'|'danger', 'tekst' => '...']
 *   $greske        string[]     greške u formi (forma ostaje popunjena)
 *   $csrfToken     string
 *
 * Imena polja u formi: naslov, tekst, ciljna_grupa (svi | menza | dom), menza_id, dom,
 * id (samo kod izmene), akcija, csrf_token
 */
$dozvoljeneUloge = ['admin'];
require_once __DIR__ . '/includes/provera_admina.php';
require_once __DIR__ . '/src/ObavestenjeRepository.php';
require_once __DIR__ . '/src/MenzaRepository.php';
require_once __DIR__ . '/src/SmestajRepository.php';
require_once __DIR__ . '/src/TransakcijaRepository.php';
require_once __DIR__ . '/src/Format.php';
require_once __DIR__ . '/src/Poruka.php';

const MAKS_NASLOV = 150;    // kolona obavestenja.naslov je VARCHAR(150)
const MAKS_TEKST = 2000;
const BROJ_U_LISTI = 20;
const BROJ_UPLATA = 20;

$FILTERI = [
    '' => 'Sve',
    'ceka_potvrdu' => 'Čeka potvrdu',
    'odbijeno' => 'Odbijeno',
    'uspesno' => 'Uspešno',
];

$transakcijaRepo = new TransakcijaRepository();

// Filter i pretraga uplata (iz adrese; kod POST-a stižu iz skrivenih polja da se zadrže posle čuvanja)
$filterStatus = (string)($_POST['filter_status'] ?? $_GET['status'] ?? '');
if (!isset($FILTERI[$filterStatus])) {
    $filterStatus = '';
}
$pretraga = trim((string)($_POST['filter_q'] ?? $_GET['q'] ?? ''));

/** Link za filter uplata – prazni parametri se izostavljaju */
function adminUplateUrl(string $status, string $q): string
{
    $parametri = array_filter(['status' => $status, 'q' => $q], fn($v) => $v !== '');
    return 'admin_uplate.php' . ($parametri ? '?' . http_build_query($parametri) : '');
}

$NAZIVI_GRUPA = [
    'svi' => 'Svi studenti',
    'menza' => 'Jedna menza',
    'dom' => 'Jedan dom',
];

$obavestenjeRepo = new ObavestenjeRepository();
$menze = (new MenzaRepository())->list();
$domovi = (new SmestajRepository())->domovi();

$praznaForma = ['id' => null, 'naslov' => '', 'tekst' => '', 'ciljna_grupa' => 'svi', 'menza_id' => null, 'dom' => null];
$forma = $praznaForma;
$greske = [];

// =====================================================================
// POST – objava, izmena, brisanje
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $akcija = $_POST['akcija'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    $nazad = adminUplateUrl($filterStatus, $pretraga);

    if (!$adminAuth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
        Poruka::postavi('danger', 'Sesija je istekla. Osveži stranicu i pokušaj ponovo.');
        header('Location: ' . $nazad);
        exit;
    }

    // ---------- Uplate: potvrda ili odbijanje ----------
    if ($akcija === 'potvrdi' || $akcija === 'odbij') {
        $uspelo = $akcija === 'potvrdi' ? $transakcijaRepo->potvrdiUplatu($id) : $transakcijaRepo->odbijUplatu($id);
        if (!$uspelo) {
            Poruka::postavi('danger', 'Uplata ne postoji ili više ne čeka potvrdu.');
        } elseif ($akcija === 'potvrdi') {
            Poruka::postavi('success', 'Uplata br. ' . $id . ' je potvrđena – novac je dodat na karticu.');
        } else {
            Poruka::postavi('success', 'Uplata br. ' . $id . ' je odbijena.');
        }
        header('Location: ' . $nazad);
        exit;
    }

    if ($akcija === 'obrisi') {
        if ($id > 0 && $obavestenjeRepo->read($id)) {
            $obavestenjeRepo->delete($id);
            Poruka::postavi('success', 'Obaveštenje je obrisano.');
        } else {
            Poruka::postavi('danger', 'Obaveštenje ne postoji.');
        }
        header('Location: ' . $nazad);
        exit;
    }

    if ($akcija === 'objavi' || $akcija === 'izmeni') {
        $forma = [
            'id' => $akcija === 'izmeni' ? $id : null,
            'naslov' => trim((string)($_POST['naslov'] ?? '')),
            'tekst' => trim((string)($_POST['tekst'] ?? '')),
            'ciljna_grupa' => (string)($_POST['ciljna_grupa'] ?? 'svi'),
            'menza_id' => (int)($_POST['menza_id'] ?? 0),
            'dom' => (string)($_POST['dom'] ?? ''),
        ];

        // ---------- Provera unosa ----------
        if ($forma['naslov'] === '') {
            $greske[] = 'Upiši naslov.';
        } elseif (mb_strlen($forma['naslov']) > MAKS_NASLOV) {
            $greske[] = 'Naslov može imati najviše ' . MAKS_NASLOV . ' znakova.';
        }
        if ($forma['tekst'] === '') {
            $greske[] = 'Upiši tekst poruke.';
        } elseif (mb_strlen($forma['tekst']) > MAKS_TEKST) {
            $greske[] = 'Tekst može imati najviše ' . MAKS_TEKST . ' znakova.';
        }
        if (!isset($NAZIVI_GRUPA[$forma['ciljna_grupa']])) {
            $greske[] = 'Izaberi kome se šalje obaveštenje.';
            $forma['ciljna_grupa'] = 'svi';
        }
        if ($forma['ciljna_grupa'] === 'menza' && !in_array($forma['menza_id'], array_map('intval', array_column($menze, 'id')), true)) {
            $greske[] = 'Izaberi menzu.';
        }
        if ($forma['ciljna_grupa'] === 'dom' && !in_array($forma['dom'], $domovi, true)) {
            $greske[] = 'Izaberi dom.';
        }
        if ($akcija === 'izmeni' && ($id <= 0 || !$obavestenjeRepo->read($id))) {
            $greske[] = 'Obaveštenje koje se menja više ne postoji.';
        }

        // ---------- Čuvanje ----------
        if (empty($greske)) {
            $podaci = [
                'naslov' => $forma['naslov'],
                'tekst' => $forma['tekst'],
                'ciljna_grupa' => $forma['ciljna_grupa'],
                'menza_id' => $forma['menza_id'],
                'dom' => $forma['dom'],
                'admin_id' => $adminId,
            ];
            if ($akcija === 'izmeni') {
                $obavestenjeRepo->update($id, $podaci);
                Poruka::postavi('success', 'Obaveštenje je izmenjeno.');
            } else {
                $obavestenjeRepo->create($podaci);
                Poruka::postavi('success', 'Obaveštenje je objavljeno – studenti ga vide na početnoj.');
            }
            header('Location: ' . $nazad);
            exit;
        }
    }
}

// =====================================================================
// Podaci za prikaz
// =====================================================================

// Izmena: forma se puni postojećim obaveštenjem (osim ako je upravo bila greška u formi)
$izmeniId = (int)($_GET['izmeni'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $izmeniId > 0) {
    $postojece = $obavestenjeRepo->read($izmeniId);
    if ($postojece) {
        $forma = [
            'id' => $postojece->id,
            'naslov' => $postojece->naslov,
            'tekst' => $postojece->tekst,
            'ciljna_grupa' => $postojece->ciljna_grupa,
            'menza_id' => (int)$postojece->menza_id,
            'dom' => $postojece->dom,
        ];
    } else {
        $greske[] = 'Obaveštenje br. ' . $izmeniId . ' ne postoji.';
    }
}

$obavestenja = $obavestenjeRepo->list(['limit' => BROJ_U_LISTI]);

$uplate = $transakcijaRepo->uplateZaAdmina(['status' => $filterStatus, 'pretraga' => $pretraga, 'limit' => BROJ_UPLATA]);
$uplateDanas = $transakcijaRepo->uplateZaDan(date('Y-m-d'));
$brojNaCekanju = $transakcijaRepo->brojUplataNaCekanju();

$poruka = Poruka::uzmi();

$csrfToken = $adminAuth->generateCsrfToken();

require __DIR__ . '/views/admin_uplate.view.php';
