<?php
/**
 * LOGIKA: uplate i obaveštenja (samo administrator).
 * Izgled je u views/admin_uplate.view.php.
 *
 * Za sada deo sa obaveštenjima: objava, izmena i brisanje.
 * Pregled i potvrda uplata studenata se dodaju uz dopunu kartice.
 *
 * Adresa: admin_uplate.php            – nova poruka
 *         admin_uplate.php?izmeni=4   – forma popunjena obaveštenjem br. 4
 *
 * Forma šalje POST sa poljem "akcija":
 *   objavi  – novo obaveštenje
 *   izmeni  – izmena postojećeg (polje id)
 *   obrisi  – brisanje (polje id)
 * Posle uspeha preusmeravanje na admin_uplate.php (Post/Redirect/Get).
 *
 * Prikaz dobija:
 *   $admin         Admin        prijavljeni administrator
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
require_once __DIR__ . '/src/Format.php';
require_once __DIR__ . '/src/Poruka.php';

const MAKS_NASLOV = 150;    // kolona obavestenja.naslov je VARCHAR(150)
const MAKS_TEKST = 2000;
const BROJ_U_LISTI = 20;

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

    if (!$adminAuth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
        Poruka::postavi('danger', 'Sesija je istekla. Osveži stranicu i pokušaj ponovo.');
        header('Location: admin_uplate.php');
        exit;
    }

    if ($akcija === 'obrisi') {
        if ($id > 0 && $obavestenjeRepo->read($id)) {
            $obavestenjeRepo->delete($id);
            Poruka::postavi('success', 'Obaveštenje je obrisano.');
        } else {
            Poruka::postavi('danger', 'Obaveštenje ne postoji.');
        }
        header('Location: admin_uplate.php');
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
            header('Location: admin_uplate.php');
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

$poruka = Poruka::uzmi();

$csrfToken = $adminAuth->generateCsrfToken();

require __DIR__ . '/views/admin_uplate.view.php';
