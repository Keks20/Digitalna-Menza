<?php
/**
 * LOGIKA: refundacija uplate (student vraća svoju dopunu kartice).
 * Nema prikaza – stranica prima samo POST i preusmerava nazad sa porukom (Post/Redirect/Get).
 *
 * Uplata može da se refundira u roku od sat vremena (TransakcijaRepository::ROK_REFUNDACIJE_MIN),
 * dok se posle nje sa kartice ništa ne plati: čim student kupi obroke ili plati stanarinu, refundacija se gasi.
 * Sve provere su u TransakcijaRepository::refundirajUplatu(), u transakciji sa zaključanom karticom.
 * Refundacija je simulacija: iznos se skida sa studentske kartice, a prava banka se ne poziva.
 *
 * POST: id (id uplate), csrf_token, nazad (istorija | dopuna – gde se vraća; podrazumevano istorija)
 * Tuđa uplata se ne razlikuje od nepostojeće – ista poruka.
 */
require_once __DIR__ . '/includes/provera_prijave.php';
require_once __DIR__ . '/src/TransakcijaRepository.php';
require_once __DIR__ . '/src/Poruka.php';
require_once __DIR__ . '/src/Format.php';

$POVRATAK = [
    'istorija' => 'istorija.php',
    'dopuna' => 'dopuna.php',
];
$nazad = $POVRATAK[(string)($_POST['nazad'] ?? '')] ?? $POVRATAK['istorija'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $nazad);
    exit;
}

$uplataId = (int)($_POST['id'] ?? 0);

if (!$auth->validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
    Poruka::postavi('danger', 'Sesija je istekla. Osveži stranicu i pokušaj ponovo.');
} elseif ($uplataId <= 0) {
    Poruka::postavi('danger', 'Uplata ne postoji.');
} else {
    $transakcije = new TransakcijaRepository();
    try {
        $refundacijaId = $transakcije->refundirajUplatu($karticaId, $uplataId);
        $refundacija = $transakcije->read($refundacijaId);
        $kuda = $refundacija->nacin === 'ips_qr' ? 'na račun sa kog je poslata IPS uplata' : 'na platnu karticu kojom je uplaćeno';
        Poruka::postavi('success', 'Uplata je refundirana: ' . Format::novac(abs($refundacija->iznos))
            . ' je skinuto sa studentske kartice i vraća se ' . $kuda . '.');
    } catch (RuntimeException $e) {
        // Poruke iz refundirajUplatu() su pisane za studenta (istekao rok, novac potrošen, blokirana kartica)
        Poruka::postavi('danger', $e->getMessage());
    } catch (mysqli_sql_exception $e) {
        Poruka::postavi('danger', 'Refundacija trenutno nije uspela. Pokušaj ponovo za par minuta.');
    }
}

header('Location: ' . $nazad);
exit;
