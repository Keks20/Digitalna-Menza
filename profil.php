<?php
/**
 * LOGIKA: profil studenta – podaci, kartica, kategorija, dom i izmena lozinke, kontakta i profilne slike.
 * Izgled je u views/profil.view.php.
 *
 * Adresa: profil.php
 *
 * Forme šalju POST sa poljem "akcija" (svaka ima csrf_token):
 *   kontakt       – email, telefon (prazan telefon = broj se briše)
 *   lozinka       – stara_lozinka, nova_lozinka, potvrda_lozinke
 *   slika         – slika (fajl: JPG, PNG ili WebP, najviše 2 MB); forma mora imati enctype="multipart/form-data"
 *   ukloni_sliku  – briše profilnu sliku
 * Posle uspeha: preusmeravanje na profil.php sa porukom (Post/Redirect/Get).
 * Kod greške stranica se prikazuje ponovo, a uneti email i telefon ostaju u formi (lozinke se nikad ne vraćaju).
 *
 * Ime, indeks, ustanova i kategorija se ne menjaju ovde – njih vodi Studentski centar (admin_studenti.php).
 * Profilna slika se čuva u uploads/profilne/ pod nasumičnim imenom; tip se proverava po sadržaju fajla,
 * a ne po nazivu ili tipu koji pošalje pregledač.
 *
 * Prikaz dobija:
 *   $student        Student      ime, prezime, broj_indeksa, ustanova, email, telefon, putanjaSlike(), inicijali(),
 *                                nazivKategorije(), opisKategorije()
 *   $kartica        Kartica      formatiranBroj(), stanje, status, kreirano
 *   $ugovor         array|null   aktivan ugovor u domu: ['dom', 'paviljon', 'soba', 'skolska_godina', 'mesecna_cena', ...] (null = nije u domu)
 *   $forma          array        ['email' => '...', 'telefon' => '...'] – vrednosti u poljima kontakta
 *   $greske         array        ['kontakt' => string[], 'lozinka' => string[], 'slika' => string[]] – greške po formi
 *   $najmanjeLozinka int         najmanja dužina nove lozinke (8)
 *   $najviseSlikaMb int          najveća veličina slike u MB (2)
 *   $poruka         array|null   ['tip' => 'success'|'danger', 'tekst' => '...']
 *   $csrfToken      string
 */
require_once __DIR__ . '/includes/provera_prijave.php';
require_once __DIR__ . '/src/StudentRepository.php';
require_once __DIR__ . '/src/KarticaRepository.php';
require_once __DIR__ . '/src/SmestajRepository.php';
require_once __DIR__ . '/src/Poruka.php';
require_once __DIR__ . '/src/Format.php';

const NAJMANJE_LOZINKA = 8;
const NAJVISE_LOZINKA = 72;                  // bcrypt ne gleda znakove posle 72. bajta
const NAJVISE_EMAIL = 100;                   // kolona studenti.email je VARCHAR(100)
const NAJVISE_SLIKA_BAJTOVA = 2 * 1024 * 1024;
const NAJVISE_SLIKA_PIKSELA = 4000;          // po širini i po visini

// Dozvoljeni tipovi slike (po sadržaju fajla) i nastavak pod kojim se čuvaju
const TIPOVI_SLIKE = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

$studentRepo = new StudentRepository();
$karticaRepo = new KarticaRepository();
$student = $studentRepo->read($studentId);

$folderSlika = __DIR__ . '/' . Student::FOLDER_SLIKA;

/** Briše fajl profilne slike; naziv je uvek iz baze, a basename() ne dozvoljava izlazak iz foldera */
function obrisiFajlSlike(string $folder, ?string $naziv): void
{
    if ($naziv === null || $naziv === '') {
        return;
    }
    $putanja = $folder . basename($naziv);
    if (is_file($putanja)) {
        @unlink($putanja);
    }
}

$forma = ['email' => $student->email, 'telefon' => (string)$student->telefon];
$greske = ['kontakt' => [], 'lozinka' => [], 'slika' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $akcija = (string)($_POST['akcija'] ?? '');

    if (!$auth->validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
        // I kad je fajl veći od post_max_size, PHP odbaci ceo POST pa token ne stigne
        Poruka::postavi('danger', 'Sesija je istekla ili je poslati fajl prevelik. Osveži stranicu i pokušaj ponovo.');
        header('Location: profil.php');
        exit;
    }

    // ---------- E-adresa i telefon ----------
    if ($akcija === 'kontakt') {
        $email = trim((string)($_POST['email'] ?? ''));
        $telefon = trim((string)($_POST['telefon'] ?? ''));
        $forma = ['email' => $email, 'telefon' => $telefon];

        if ($email === '') {
            $greske['kontakt'][] = 'Upiši e-adresu.';
        } elseif (mb_strlen($email) > NAJVISE_EMAIL || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $greske['kontakt'][] = 'E-adresa nije ispravna (primer: ime@primer.rs).';
        } elseif ($studentRepo->emailZauzet($email, $studentId)) {
            $greske['kontakt'][] = 'Ovu e-adresu već koristi drugi student.';
        }

        // Telefon: cifre, razmaci, crtice, kosa crta i + na početku; 6–15 cifara ("064 123 4567", "+381 64 1234567")
        if ($telefon !== '') {
            $cifre = preg_replace('/\D/', '', $telefon);
            if (!preg_match('/^\+?[0-9][0-9 \/-]*$/', $telefon) || strlen($cifre) < 6 || strlen($cifre) > 15 || strlen($telefon) > 20) {
                $greske['kontakt'][] = 'Broj telefona nije ispravan (primer: 064 123 4567 ili +381 64 1234567).';
            }
        }

        if (empty($greske['kontakt'])) {
            $studentRepo->promeniKontakt($studentId, $email, $telefon !== '' ? $telefon : null);
            Poruka::postavi('success', 'Kontakt podaci su sačuvani.');
            header('Location: profil.php');
            exit;
        }
    }

    // ---------- Lozinka ----------
    if ($akcija === 'lozinka') {
        $stara = (string)($_POST['stara_lozinka'] ?? '');
        $nova = (string)($_POST['nova_lozinka'] ?? '');
        $potvrda = (string)($_POST['potvrda_lozinke'] ?? '');

        if ($stara === '' || $nova === '' || $potvrda === '') {
            $greske['lozinka'][] = 'Popuni sva tri polja.';
        } elseif (mb_strlen($nova) < NAJMANJE_LOZINKA) {
            $greske['lozinka'][] = 'Nova lozinka mora imati najmanje ' . NAJMANJE_LOZINKA . ' znakova.';
        } elseif (strlen($nova) > NAJVISE_LOZINKA) {
            $greske['lozinka'][] = 'Nova lozinka je predugačka (najviše ' . NAJVISE_LOZINKA . ' znakova).';
        } elseif ($nova !== $potvrda) {
            $greske['lozinka'][] = 'Nova lozinka i potvrda se ne poklapaju.';
        } elseif ($nova === $stara) {
            $greske['lozinka'][] = 'Nova lozinka mora da se razlikuje od trenutne.';
        } elseif (!$karticaRepo->promeniLozinku($karticaId, $stara, $nova)) {
            $greske['lozinka'][] = 'Trenutna lozinka nije tačna.';
        } else {
            // Nov ID sesije posle promene lozinke, kao i posle prijave
            session_regenerate_id(true);
            Poruka::postavi('success', 'Lozinka je promenjena.');
            header('Location: profil.php');
            exit;
        }
    }

    // ---------- Profilna slika ----------
    if ($akcija === 'slika') {
        $fajl = $_FILES['slika'] ?? null;
        $kodGreske = is_array($fajl) && is_int($fajl['error'] ?? null) ? $fajl['error'] : UPLOAD_ERR_NO_FILE;
        $nastavak = null;

        if ($kodGreske === UPLOAD_ERR_NO_FILE) {
            $greske['slika'][] = 'Izaberi sliku.';
        } elseif ($kodGreske === UPLOAD_ERR_INI_SIZE || $kodGreske === UPLOAD_ERR_FORM_SIZE || ($kodGreske === UPLOAD_ERR_OK && $fajl['size'] > NAJVISE_SLIKA_BAJTOVA)) {
            $greske['slika'][] = 'Slika je prevelika – najviše ' . (NAJVISE_SLIKA_BAJTOVA / 1024 / 1024) . ' MB.';
        } elseif ($kodGreske !== UPLOAD_ERR_OK || !is_uploaded_file($fajl['tmp_name'])) {
            $greske['slika'][] = 'Slika nije poslata do kraja. Pokušaj ponovo.';
        } else {
            // Tip se čita iz sadržaja fajla; naziv i tip koje šalje pregledač se ne koriste
            $tip = (new finfo(FILEINFO_MIME_TYPE))->file($fajl['tmp_name']);
            $dimenzije = @getimagesize($fajl['tmp_name']);
            $nastavak = TIPOVI_SLIKE[$tip] ?? null;

            if ($nastavak === null || $dimenzije === false) {
                $greske['slika'][] = 'Slika mora biti JPG, PNG ili WebP.';
            } elseif ($dimenzije[0] > NAJVISE_SLIKA_PIKSELA || $dimenzije[1] > NAJVISE_SLIKA_PIKSELA) {
                $greske['slika'][] = 'Slika je prevelika – najviše ' . NAJVISE_SLIKA_PIKSELA . ' × ' . NAJVISE_SLIKA_PIKSELA . ' piksela.';
            }
        }

        if (empty($greske['slika'])) {
            $naziv = bin2hex(random_bytes(16)) . '.' . $nastavak;
            if (!is_dir($folderSlika)) {
                mkdir($folderSlika, 0755, true);
            }
            if (move_uploaded_file($fajl['tmp_name'], $folderSlika . $naziv)) {
                $studentRepo->postaviSliku($studentId, $naziv);
                obrisiFajlSlike($folderSlika, $student->slika);
                Poruka::postavi('success', 'Profilna slika je postavljena.');
                header('Location: profil.php');
                exit;
            }
            $greske['slika'][] = 'Slika nije sačuvana. Pokušaj ponovo.';
        }
    }

    if ($akcija === 'ukloni_sliku') {
        if ($student->slika !== null) {
            $studentRepo->postaviSliku($studentId, null);
            obrisiFajlSlike($folderSlika, $student->slika);
            Poruka::postavi('success', 'Profilna slika je uklonjena.');
        }
        header('Location: profil.php');
        exit;
    }
}

$kartica = $karticaRepo->read($karticaId);
$ugovor = (new SmestajRepository())->ugovorStudenta($studentId);
$najmanjeLozinka = NAJMANJE_LOZINKA;
$najviseSlikaMb = (int)(NAJVISE_SLIKA_BAJTOVA / 1024 / 1024);
$poruka = Poruka::uzmi();
$csrfToken = $auth->generateCsrfToken();

require __DIR__ . '/views/profil.view.php';
