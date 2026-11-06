<?php
/**
 * LOGIKA: mini kviz o aplikaciji – 10 nasumičnih pitanja od 40.
 * Izgled je u views/kviz.view.php.
 *
 * Kviz može da reši svako, i bez prijave:
 *   – prijavljenom studentu se rezultat upisuje sa njegovim imenom i student_id,
 *   – neprijavljeni korisnik pre slanja upisuje ime.
 *
 * Adresa: kviz.php          – kviz u toku (ista pitanja i posle osvežavanja) ili rezultat poslednjeg kviza
 *         kviz.php?novi=1   – briše kviz/rezultat iz sesije i počinje novi kviz
 *
 * Izabrana pitanja i vreme početka čuvaju se u sesiji ($_SESSION['kviz']), pa se boduje samo
 * prema tim pitanjima – ne može da se pošalje drugo pitanje ili isti kviz dva puta.
 * Posle slanja: upis u kviz_rezultati, pa preusmeravanje na kviz.php (Post/Redirect/Get).
 *
 * Prikaz dobija:
 *   $prijavljen    bool             da li je student prijavljen (sa menijem) ili je gost (bez menija)
 *   $student       Student|null     za zaglavlje i meni (null za gosta)
 *   $pitanja       KvizPitanje[]    pitanja kviza u toku, [id => KvizPitanje] (prazno kad se prikazuje rezultat)
 *   $brojPitanja   int              koliko pitanja ima kviz (10)
 *   $rezultat      array|null       posle slanja, inače null:
 *                    ['id' => 15, 'broj_tacnih' => 7, 'ukupno' => 10, 'procenat' => 70, 'trajanje' => '3 min 12 s',
 *                     'ocena' => 'Odlično ...', 'ime' => 'Ana',
 *                     'stavke' => [['pitanje' => KvizPitanje, 'odgovor' => 'b' | null, 'tacno' => bool], ...]]
 *   $najbolji      array            10 najboljih: [['mesto' => 1, 'ime', 'broj_tacnih', 'ukupno', 'trajanje' => '1 min 05 s',
 *                                   'datum' => '06.11.2026.', 'moj' => bool], ...]
 *   $forma         array            ['ime' => '', 'odgovori' => [id pitanja => 'a'|'b'|'c'|'d']] – posle greške ostaje uneto
 *   $greske        string[]
 *   $csrfToken     string
 *
 * Imena polja (POST): akcija=posalji, odgovor[<id pitanja>] = a|b|c|d, ime (samo za gosta), csrf_token
 */
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/StudentRepository.php';
require_once __DIR__ . '/src/KvizRepository.php';
require_once __DIR__ . '/src/Format.php';

const BROJ_PITANJA = 10;
const NAJKRACE_IME = 2;
const NAJDUZE_IME = 50;

/** 192 -> "3 min 12 s", 45 -> "45 s" */
function trajanjeTekst(int $sekunde): string
{
    if ($sekunde < 60) {
        return $sekunde . ' s';
    }
    if ($sekunde >= 3600) {
        return intdiv($sekunde, 3600) . ' h ' . intdiv($sekunde % 3600, 60) . ' min';
    }
    return intdiv($sekunde, 60) . ' min ' . str_pad((string)($sekunde % 60), 2, '0', STR_PAD_LEFT) . ' s';
}

/** Kratka poruka uz rezultat */
function ocenaKviza(int $tacnih, int $ukupno): string
{
    $procenat = $ukupno > 0 ? $tacnih / $ukupno : 0;
    if ($procenat >= 1) {
        return 'Svaka čast – znaš aplikaciju u dušu!';
    }
    if ($procenat >= 0.8) {
        return 'Odlično, skoro sve tačno.';
    }
    if ($procenat >= 0.5) {
        return 'Dobro je, a objašnjenja ispod pomažu za sledeći put.';
    }
    return 'Pogledaj objašnjenja ispod i probaj ponovo.';
}

// Session se pokreće u Auth (BaseAuth), a tu je i CSRF zaštita
$auth = new Auth();
$prijavljen = $auth->check();
$student = $prijavljen ? (new StudentRepository())->read($auth->studentId()) : null;
$prijavljen = $student !== null;   // nalog obrisan u međuvremenu -> kao gost
$kvizRepo = new KvizRepository();

// ---------- Novi kviz ----------
if (isset($_GET['novi'])) {
    unset($_SESSION['kviz'], $_SESSION['kviz_rezultat']);
    header('Location: kviz.php');
    exit;
}

$greske = [];
$forma = ['ime' => (string)($_SESSION['kviz_ime'] ?? ''), 'odgovori' => []];

// ---------- Slanje odgovora ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['akcija'] ?? '') === 'posalji') {
    $kviz = $_SESSION['kviz'] ?? null;
    $poslato = (array)($_POST['odgovor'] ?? []);

    if (!$auth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $greske[] = 'Sesija je istekla. Osveži stranicu i pokušaj ponovo.';
    } elseif ($kviz === null) {
        // Isti kviz poslat dva puta (npr. dupli klik) – rezultat je već upisan i prikazuje se
        if (isset($_SESSION['kviz_rezultat'])) {
            header('Location: kviz.php');
            exit;
        }
        $greske[] = 'Kviz je istekao. Pitanja ispod su nova – odgovori na njih i pošalji ponovo.';
    } else {
        // Boduje se samo prema pitanjima iz sesije; ostalo iz forme se ne gleda
        $neodgovoreno = 0;
        foreach ($kviz['pitanja'] as $id) {
            $slovo = $poslato[$id] ?? null;
            if (is_string($slovo) && in_array($slovo, KvizPitanje::SLOVA, true)) {
                $forma['odgovori'][$id] = $slovo;
            } else {
                $neodgovoreno++;
            }
        }

        // Ime: prijavljeni student se upisuje svojim imenom, gost ga upisuje sam
        if ($prijavljen) {
            $ime = mb_substr($student->punoIme(), 0, NAJDUZE_IME);
        } else {
            $ime = trim(preg_replace('/\s+/u', ' ', (string)($_POST['ime'] ?? '')));
            $forma['ime'] = $ime;
            if (mb_strlen($ime) < NAJKRACE_IME || mb_strlen($ime) > NAJDUZE_IME) {
                $greske[] = 'Upiši ime (od ' . NAJKRACE_IME . ' do ' . NAJDUZE_IME . ' znakova) – ono se vidi na listi najboljih.';
            }
        }

        if ($neodgovoreno > 0) {
            $greske[] = 'Odgovori na sva pitanja – bez odgovora je ostalo još ' . $neodgovoreno . '.';
        }

        if (empty($greske)) {
            $pitanjaKviza = $kvizRepo->poIdjevima($kviz['pitanja']);
            $brojTacnih = 0;
            foreach ($pitanjaKviza as $id => $pitanje) {
                if ($pitanje->jeTacan($forma['odgovori'][$id] ?? null)) {
                    $brojTacnih++;
                }
            }
            $ukupno = count($pitanjaKviza);
            $trajanje = max(0, time() - (int)$kviz['pocetak']);

            try {
                $rezultatId = $kvizRepo->sacuvajRezultat($prijavljen ? $auth->studentId() : null, $ime, $brojTacnih, $ukupno, $trajanje);

                $_SESSION['kviz_rezultat'] = [
                    'id' => $rezultatId,
                    'pitanja' => array_keys($pitanjaKviza),
                    'odgovori' => $forma['odgovori'],
                    'broj_tacnih' => $brojTacnih,
                    'ukupno' => $ukupno,
                    'trajanje_sek' => $trajanje,
                    'ime' => $ime,
                ];
                if (!$prijavljen) {
                    $_SESSION['kviz_ime'] = $ime;   // gost ne kuca ime ponovo za sledeći kviz
                }
                unset($_SESSION['kviz']);

                header('Location: kviz.php');
                exit;
            } catch (mysqli_sql_exception $e) {
                $greske[] = 'Rezultat trenutno ne može da se sačuva. Pokušaj ponovo za par minuta.';
            }
        }
    }
}

// ---------- Rezultat poslednjeg kviza ----------
$rezultat = null;
$pitanja = [];

if (isset($_SESSION['kviz_rezultat'])) {
    $r = $_SESSION['kviz_rezultat'];
    $stavke = [];
    foreach ($kvizRepo->poIdjevima($r['pitanja']) as $id => $pitanje) {
        $odgovor = $r['odgovori'][$id] ?? null;
        $stavke[] = ['pitanje' => $pitanje, 'odgovor' => $odgovor, 'tacno' => $pitanje->jeTacan($odgovor)];
    }
    $rezultat = [
        'id' => (int)$r['id'],
        'broj_tacnih' => (int)$r['broj_tacnih'],
        'ukupno' => (int)$r['ukupno'],
        'procenat' => $r['ukupno'] > 0 ? (int)round($r['broj_tacnih'] * 100 / $r['ukupno']) : 0,
        'trajanje' => trajanjeTekst((int)$r['trajanje_sek']),
        'ocena' => ocenaKviza((int)$r['broj_tacnih'], (int)$r['ukupno']),
        'ime' => $r['ime'],
        'stavke' => $stavke,
    ];
} else {
    // ---------- Kviz u toku (ili novi) ----------
    $kviz = $_SESSION['kviz'] ?? null;
    if ($kviz !== null) {
        $pitanja = $kvizRepo->poIdjevima($kviz['pitanja']);
    }
    // Nema kviza u sesiji (ili su mu pitanja u međuvremenu obrisana) -> biraju se nova
    if (empty($pitanja)) {
        $ids = $kvizRepo->nasumicnaPitanja(BROJ_PITANJA);
        if (!empty($ids)) {
            $_SESSION['kviz'] = ['pitanja' => $ids, 'pocetak' => time()];
            $pitanja = $kvizRepo->poIdjevima($ids);
        }
    }
}
$brojPitanja = $rezultat ? $rezultat['ukupno'] : count($pitanja);

// ---------- Lista najboljih ----------
$najbolji = [];
foreach ($kvizRepo->najboljiRezultati(10) as $i => $red) {
    $najbolji[] = [
        'mesto' => $i + 1,
        'ime' => $red['ime'],
        'broj_tacnih' => (int)$red['broj_tacnih'],
        'ukupno' => (int)$red['ukupno'],
        'trajanje' => trajanjeTekst((int)$red['trajanje_sek']),
        'datum' => Format::datum($red['kreirano']),
        'moj' => $rezultat !== null && (int)$red['id'] === $rezultat['id'],
    ];
}

$csrfToken = $auth->generateCsrfToken();

require __DIR__ . '/views/kviz.view.php';
