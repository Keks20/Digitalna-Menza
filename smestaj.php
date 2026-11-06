<?php
/**
 * LOGIKA: smeštaj – ugovor, mesečna zaduženja i plaćanje stanarine.
 * Izgled je u views/smestaj.view.php.
 *
 * Adresa: smestaj.php                 – za plaćanje je izabrano najstarije neplaćeno zaduženje
 *         smestaj.php?zaduzenje=12    – izabrano drugo neplaćeno zaduženje (dugme "Plati" u tabeli)
 *
 * Forma šalje POST: akcija=plati, zaduzenje (id), nacin ('kartica' | 'ips_qr'), csrf_token.
 *   kartica – novac se odmah skida sa studentske kartice
 *   ips_qr  – otvara se IPS dopuna za iznos stanarine (dopuna_ips.php); čim uplata stigne,
 *             stanarina se plaća sama i vraća se ovde sa porukom
 *
 * Prikaz dobija:
 *   $student           Student
 *   $kartica           Kartica                 stanje, formatiranBroj()
 *   $ugovor            array|null              null = student nema aktivan ugovor:
 *                                              ['skolska_godina' => '2026/27', 'mesecna_cena' => 2800.0, 'vazi_od', 'vazi_do',
 *                                               'dom' => 'Dom 1', 'paviljon' => 'A', 'soba' => '214', 'broj_kreveta' => 2]
 *   $zaduzenja         SmestajZaduzenje[]      najnovija prva; placeno, kasni(), iznos, mesec, rok_placanja
 *   $brojNeplacenih    int
 *   $izabrano          SmestajZaduzenje|null   zaduženje koje se plaća (null = sve je plaćeno)
 *   $stanjePosle       float|null              stanje kartice posle plaćanja sa kartice
 *   $nacini            array                   ['kartica' => 'Sa studentske kartice', 'ips_qr' => 'IPS QR kodom']
 *   $nacin             string                  izabran način (posle greške ostaje izabran)
 *   $poruka            array|null              ['tip' => 'success'|'danger', 'tekst' => '...']
 *   $greske            string[]
 *   $csrfToken         string
 *
 * Potvrda plaćenog zaduženja: smestaj_potvrda.php?id=<id zaduženja>
 */
require_once __DIR__ . '/includes/provera_prijave.php';
require_once __DIR__ . '/src/StudentRepository.php';
require_once __DIR__ . '/src/KarticaRepository.php';
require_once __DIR__ . '/src/SmestajRepository.php';
require_once __DIR__ . '/src/TransakcijaRepository.php';
require_once __DIR__ . '/src/Dopuna.php';
require_once __DIR__ . '/src/Poruka.php';
require_once __DIR__ . '/src/Format.php';

$nacini = [
    'kartica' => 'Sa studentske kartice',
    'ips_qr' => 'IPS QR kodom',
];

$smestajRepo = new SmestajRepository();
$nacin = 'kartica';
$greske = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['akcija'] ?? '') === 'plati') {
    $zaduzenjeId = (int)($_POST['zaduzenje'] ?? 0);
    $nacin = isset($nacini[$_POST['nacin'] ?? '']) ? $_POST['nacin'] : 'kartica';
    $zaduzenje = $smestajRepo->zaduzenjeStudenta($studentId, $zaduzenjeId);

    if (!$auth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $greske[] = 'Sesija je istekla. Osveži stranicu i pokušaj ponovo.';
    } elseif ($zaduzenje === null) {
        $greske[] = 'Zaduženje ne postoji.';
    } elseif ($zaduzenje->placeno) {
        $greske[] = 'Stanarina za ' . Format::mesecGodina($zaduzenje->mesec) . ' je već plaćena.';
    } elseif ($nacin === 'kartica') {
        try {
            (new TransakcijaRepository())->platiStanarinu($karticaId, $studentId, $zaduzenjeId);
            Poruka::postavi('success', 'Stanarina za ' . Format::mesecGodina($zaduzenje->mesec) . ' je plaćena. Sa kartice je skinuto '
                . Format::novac($zaduzenje->iznos) . '.');
            header('Location: smestaj.php');
            exit;
        } catch (RuntimeException $e) {
            // Poruke iz platiStanarinu() su pisane za studenta (nema novca, blokirana kartica...)
            $greske[] = $e->getMessage();
        } catch (mysqli_sql_exception $e) {
            $greske[] = 'Plaćanje trenutno nije uspelo. Pokušaj ponovo za par minuta.';
        }
    } else {
        // IPS: dopuna kartice za iznos stanarine, pa se stanarina plaća čim uplata stigne (dopuna_uspesno.php)
        $iznos = (int)ceil($zaduzenje->iznos);
        $greske = Dopuna::proveri($iznos, 'ips_qr');
        if (empty($greske)) {
            Dopuna::zapocni($iznos, 'ips_qr', $zaduzenjeId);
            header('Location: dopuna_ips.php');
            exit;
        }
    }
}

$student = (new StudentRepository())->read($studentId);
$kartica = (new KarticaRepository())->read($karticaId);
$ugovor = $smestajRepo->ugovorStudenta($studentId);

$zaduzenja = array_map(fn(array $red) => new SmestajZaduzenje($red), $smestajRepo->list(['student_id' => $studentId]));
$neplacena = array_values(array_filter($zaduzenja, fn(SmestajZaduzenje $z) => !$z->placeno));
$brojNeplacenih = count($neplacena);

// Za plaćanje: zaduženje iz adrese (ako je neplaćeno), inače najstarije neplaćeno
$izabrano = $brojNeplacenih > 0 ? end($neplacena) : null;
$trazeno = (int)($_POST['zaduzenje'] ?? $_GET['zaduzenje'] ?? 0);
foreach ($neplacena as $z) {
    if ((int)$z->id === $trazeno) {
        $izabrano = $z;
    }
}
$stanjePosle = $izabrano !== null ? $kartica->stanje - $izabrano->iznos : null;

$poruka = Poruka::uzmi();
$csrfToken = $auth->generateCsrfToken();

require __DIR__ . '/views/smestaj.view.php';
