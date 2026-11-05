<?php
/**
 * LOGIKA: unos jelovnika po danu i menzi i izmena cena obroka (samo administrator).
 * Izgled je u views/admin_jelovnik.view.php.
 *
 * Adresa: admin_jelovnik.php?menza=1&dan=2026-11-05 (podrazumevano: prva menza, danas)
 *
 * Forma šalje POST sa poljem "akcija":
 *   sacuvaj  – čuva jela za izabrani dan; dugme "objavi" (studenti vide) ili "nacrt" (U izmeni)
 *   kopiraj  – kopira jela iz prethodnog dana u izabrani dan, kao nacrt
 *   cene     – menja cene obroka (važe odmah; već kupljeni obroci zadržavaju staru cenu)
 * Posle uspešnog čuvanja radi se preusmeravanje na isti dan (Post/Redirect/Get),
 * pa osvežavanje stranice ne šalje formu ponovo.
 *
 * Prikaz dobija:
 *   $admin              Admin        prijavljeni administrator
 *   $menze              array        [['id', 'naziv', ..., 'izabrana' (bool), 'url'], ...]
 *   $izabranaMenza      array        menza čiji se jelovnik unosi
 *   $izabraniDan        string       'Y-m-d'
 *   $dani               array        7 dana: ['datum', 'dan', 'broj', 'izabran', 'danas', 'status', 'url']
 *   $nedeljaOpis        string       npr. "2–8. novembar 2026."
 *   $prethodnaNedeljaUrl, $sledecaNedeljaUrl  string
 *   $tipoviObroka       TipObroka[]  doručak, ručak, večera: id, naziv, cena, radnoVreme()
 *   $forma              array        [tip_obroka_id => [['jelo' => '...', 'posno' => bool], ...]] – BROJ_JELA redova po obroku
 *   $statusDana         string       'objavljen' | 'u_izmeni' | 'nije_unet'
 *   $ceneUFormi         array        [tip_obroka_id => '120'] vrednosti za polja cena
 *   $NAZIVI_STATUSA     array        ['objavljen' => 'Objavljen', ...] za značke
 *   $poruka             array|null   ['tip' => 'success'|'danger', 'tekst' => '...'] posle čuvanja
 *   $greske             string[]     greške u formi (forma ostaje popunjena)
 *   $csrfToken          string
 *
 * Imena polja u formi:
 *   jela[<tip_obroka_id>][<0..BROJ_JELA-1>][jelo], jela[<tip_obroka_id>][<0..BROJ_JELA-1>][posno] (checkbox)
 *   cena[<tip_obroka_id>]
 *   akcija, dugme (objavi | nacrt), csrf_token
 */
$dozvoljeneUloge = ['admin'];
require_once __DIR__ . '/includes/provera_admina.php';
require_once __DIR__ . '/src/MenzaRepository.php';
require_once __DIR__ . '/src/TipObrokaRepository.php';
require_once __DIR__ . '/src/JelovnikRepository.php';
require_once __DIR__ . '/src/Format.php';
require_once __DIR__ . '/src/Kalendar.php';

const BROJ_JELA = 3;          // toliko polja po obroku (kao u Figmi)
const MAKS_DUZINA_JELA = 150; // kolona jelovnik.jelo je VARCHAR(150)
const NAJNIZA_CENA = 1;
const NAJVISA_CENA = 10000;

$NAZIVI_STATUSA = [
    'objavljen' => 'Objavljen',
    'u_izmeni' => 'U izmeni',
    'nije_unet' => 'Nije unet',
];

$menzaRepo = new MenzaRepository();
$tipRepo = new TipObrokaRepository();
$jelovnikRepo = new JelovnikRepository();

$menze = $menzaRepo->list();
$tipoviObroka = $tipRepo->list();

// ---------- Izabrana menza i dan (iz adrese ili iz forme) ----------
$izabranaMenza = $menze[0];
$menzaId = (int)($_POST['menza'] ?? $_GET['menza'] ?? 0);
foreach ($menze as $menza) {
    if ((int)$menza['id'] === $menzaId) {
        $izabranaMenza = $menza;
    }
}
$izabranaMenzaId = (int)$izabranaMenza['id'];
$izabraniDan = Kalendar::datumIzAdrese($_POST['dan'] ?? $_GET['dan'] ?? null, date('Y-m-d'));

/** Link ka unosu jelovnika za datu menzu i dan */
function adminJelovnikUrl(int $menzaId, string $dan): string
{
    return 'admin_jelovnik.php?' . http_build_query(['menza' => $menzaId, 'dan' => $dan]);
}

/** Poruka koja preživi preusmeravanje (prikazuje se jednom) */
function postaviPoruku(string $tip, string $tekst): void
{
    $_SESSION['poruka'] = ['tip' => $tip, 'tekst' => $tekst];
}

/** Prazna forma: BROJ_JELA praznih redova za svaki obrok */
function praznaForma(array $tipoviObroka): array
{
    $forma = [];
    foreach ($tipoviObroka as $tip) {
        $forma[$tip->id] = array_fill(0, BROJ_JELA, ['jelo' => '', 'posno' => false]);
    }
    return $forma;
}

/** Forma popunjena jelima iz baze */
function formaIzBaze(array $tipoviObroka, array $jelovnik): array
{
    $forma = praznaForma($tipoviObroka);
    foreach ($jelovnik as $tipId => $stavke) {
        foreach (array_slice($stavke, 0, BROJ_JELA) as $i => $stavka) {
            $forma[$tipId][$i] = ['jelo' => $stavka->jelo, 'posno' => $stavka->posno];
        }
    }
    return $forma;
}

$greske = [];
$forma = null;

// =====================================================================
// POST – čuvanje
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $akcija = $_POST['akcija'] ?? '';
    $nazad = adminJelovnikUrl($izabranaMenzaId, $izabraniDan);

    if (!$adminAuth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
        postaviPoruku('danger', 'Sesija je istekla. Osveži stranicu i pokušaj ponovo.');
        header('Location: ' . $nazad);
        exit;
    }

    // ---------- Jela za dan ----------
    if ($akcija === 'sacuvaj') {
        $objavi = ($_POST['dugme'] ?? '') === 'objavi';
        $poslato = $_POST['jela'] ?? [];
        $forma = praznaForma($tipoviObroka);
        $zaCuvanje = [];
        $brojJela = 0;

        foreach ($tipoviObroka as $tip) {
            for ($i = 0; $i < BROJ_JELA; $i++) {
                $jelo = trim((string)($poslato[$tip->id][$i]['jelo'] ?? ''));
                $posno = !empty($poslato[$tip->id][$i]['posno']);
                $forma[$tip->id][$i] = ['jelo' => $jelo, 'posno' => $posno];

                if ($jelo === '') {
                    continue;   // prazno polje se preskače
                }
                if (mb_strlen($jelo) > MAKS_DUZINA_JELA) {
                    $greske[] = $tip->naziv . ', ' . ($i + 1) . '. jelo: najviše ' . MAKS_DUZINA_JELA . ' znakova.';
                    continue;
                }
                $zaCuvanje[$tip->id][] = ['jelo' => $jelo, 'posno' => $posno];
                $brojJela++;
            }
        }

        if ($objavi && $brojJela === 0) {
            $greske[] = 'Za objavu je potrebno bar jedno jelo.';
        }

        if (empty($greske)) {
            $jelovnikRepo->sacuvajDan($izabranaMenzaId, $izabraniDan, $zaCuvanje, $objavi);
            if ($brojJela === 0) {
                postaviPoruku('success', 'Jelovnik za ' . Format::datum($izabraniDan) . ' je obrisan.');
            } elseif ($objavi) {
                postaviPoruku('success', 'Jelovnik za ' . Format::datum($izabraniDan) . ' je objavljen – studenti ga vide.');
            } else {
                postaviPoruku('success', 'Jelovnik za ' . Format::datum($izabraniDan) . ' je sačuvan kao nacrt (studenti ga još ne vide).');
            }
            header('Location: ' . $nazad);
            exit;
        }
        // Ima grešaka: stranica se prikazuje sa poslatim vrednostima ($forma)
    }

    // ---------- Kopiranje od prethodnog dana ----------
    elseif ($akcija === 'kopiraj') {
        $juce = Kalendar::pomeri($izabraniDan, -1);
        $jelovnikJuce = $jelovnikRepo->zaDanSvi($izabranaMenzaId, $juce);

        if (empty($jelovnikJuce)) {
            postaviPoruku('danger', 'Za ' . Format::datum($juce) . ' nema jelovnika koji bi se kopirao.');
        } else {
            $stavke = [];
            foreach ($jelovnikJuce as $tipId => $jela) {
                foreach ($jela as $stavka) {
                    $stavke[$tipId][] = ['jelo' => $stavka->jelo, 'posno' => $stavka->posno];
                }
            }
            $jelovnikRepo->sacuvajDan($izabranaMenzaId, $izabraniDan, $stavke, false);
            postaviPoruku('success', 'Jela od ' . Format::datum($juce) . ' su kopirana kao nacrt – proveri ih pa objavi.');
        }
        header('Location: ' . $nazad);
        exit;
    }

    // ---------- Cene obroka ----------
    elseif ($akcija === 'cene') {
        $poslateCene = $_POST['cena'] ?? [];
        $noveCene = [];

        foreach ($tipoviObroka as $tip) {
            $vrednost = str_replace(',', '.', trim((string)($poslateCene[$tip->id] ?? '')));
            if (!is_numeric($vrednost) || (float)$vrednost < NAJNIZA_CENA || (float)$vrednost > NAJVISA_CENA) {
                $greske[] = $tip->naziv . ': cena mora biti broj od ' . NAJNIZA_CENA . ' do ' . NAJVISA_CENA . ' RSD.';
                continue;
            }
            $noveCene[$tip->id] = round((float)$vrednost, 2);
        }

        if (empty($greske)) {
            foreach ($tipoviObroka as $tip) {
                if ($noveCene[$tip->id] !== $tip->cena) {
                    $tipRepo->update($tip->id, [
                        'naziv' => $tip->naziv,
                        'cena' => $noveCene[$tip->id],
                        'vreme_od' => $tip->vreme_od,
                        'vreme_do' => $tip->vreme_do,
                    ]);
                }
            }
            postaviPoruku('success', 'Cene su sačuvane i važe od sada. Već kupljeni obroci zadržavaju staru cenu.');
            header('Location: ' . $nazad);
            exit;
        }
    }
}

// =====================================================================
// Podaci za prikaz
// =====================================================================

// Poruka iz prethodnog zahteva (posle preusmeravanja) – prikazuje se samo jednom
$poruka = $_SESSION['poruka'] ?? null;
unset($_SESSION['poruka']);

// Dugmad za menze: link zadržava izabrani dan
foreach ($menze as $i => $menza) {
    $menze[$i]['izabrana'] = (int)$menza['id'] === $izabranaMenzaId;
    $menze[$i]['url'] = adminJelovnikUrl((int)$menza['id'], $izabraniDan);
}

// Dani u nedelji sa statusom jelovnika
$ponedeljak = Kalendar::ponedeljak($izabraniDan);
$statusi = $jelovnikRepo->statusNedelje($izabranaMenzaId, $ponedeljak);
$dani = Kalendar::daniNedelje($izabraniDan);
foreach ($dani as $i => $dan) {
    $dani[$i]['status'] = $statusi[$dan['datum']];
    $dani[$i]['url'] = adminJelovnikUrl($izabranaMenzaId, $dan['datum']);
}
$statusDana = $statusi[$izabraniDan];

$nedeljaOpis = Format::nedelja($ponedeljak);
$prethodnaNedeljaUrl = adminJelovnikUrl($izabranaMenzaId, Kalendar::pomeri($izabraniDan, -7));
$sledecaNedeljaUrl = adminJelovnikUrl($izabranaMenzaId, Kalendar::pomeri($izabraniDan, 7));

// Forma: posle greške ostaju poslate vrednosti, inače jela iz baze
if ($forma === null) {
    $forma = formaIzBaze($tipoviObroka, $jelovnikRepo->zaDanSvi($izabranaMenzaId, $izabraniDan));
}

// Cene: posle greške u ceni ostaju poslate vrednosti
$ceneUFormi = [];
foreach ($tipoviObroka as $tip) {
    $ceneUFormi[$tip->id] = (($_POST['akcija'] ?? '') === 'cene')
        ? (string)($_POST['cena'][$tip->id] ?? '')
        : ($tip->cena == floor($tip->cena) ? (string)(int)$tip->cena : number_format($tip->cena, 2, ',', ''));
}

$csrfToken = $adminAuth->generateCsrfToken();

require __DIR__ . '/views/admin_jelovnik.view.php';
