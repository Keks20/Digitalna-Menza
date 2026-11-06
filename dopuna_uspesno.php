<?php
/**
 * LOGIKA: dopuna kartice – korak 3: potvrda uplate.
 * Izgled je u views/dopuna_uspesno.view.php.
 *
 * Adresa: dopuna_uspesno.php?id=123 – student vidi samo uplate sa svoje kartice.
 * Ako je dopuna pokrenuta sa stranice Smeštaj (IPS), posle uspešne uplate odmah se plaća stanarina
 * i preusmerava se na smestaj.php sa porukom.
 *
 * Prikaz dobija:
 *   $student           Student
 *   $uplata            Transakcija   iznos, nacin, status, kreirano
 *   $brojTransakcije   string        npr. "TX-2026-11-000123"
 *   $prethodnoStanje   float|null    stanje pre uplate (null dok uplata nije uspešna)
 *   $novoStanje        float|null    stanje posle uplate (null dok uplata nije uspešna)
 */
require_once __DIR__ . '/includes/provera_prijave.php';
require_once __DIR__ . '/src/StudentRepository.php';
require_once __DIR__ . '/src/TransakcijaRepository.php';
require_once __DIR__ . '/src/Dopuna.php';
require_once __DIR__ . '/src/Poruka.php';
require_once __DIR__ . '/src/Format.php';

$uplata = (new TransakcijaRepository())->read((int)($_GET['id'] ?? 0));

// Tuđa ili nepostojeća uplata -> nazad na dopunu
if (!$uplata || (int)$uplata->kartica_id !== $karticaId || $uplata->tip !== 'uplata') {
    header('Location: dopuna.php');
    exit;
}

// Dopuna pokrenuta sa stranice Smeštaj: čim novac legne na karticu, plaća se stanarina
$zaduzenjeId = Dopuna::stanarinaPosleDopune($uplata);
if ($zaduzenjeId !== null) {
    try {
        (new TransakcijaRepository())->platiStanarinu($karticaId, $studentId, $zaduzenjeId);
        Poruka::postavi('success', 'Uplata od ' . Format::novac($uplata->iznos) . ' je stigla i stanarina je plaćena.');
    } catch (RuntimeException $e) {
        Poruka::postavi('danger', 'Uplata je stigla na karticu, ali stanarina nije plaćena: ' . $e->getMessage());
    }
    header('Location: smestaj.php');
    exit;
}

$student = (new StudentRepository())->read($studentId);
$brojTransakcije = Format::brojTransakcije((int)$uplata->id, $uplata->kreirano);
$novoStanje = $uplata->status === 'uspesno' ? $uplata->stanje_posle : null;
$prethodnoStanje = $novoStanje !== null ? $novoStanje - $uplata->iznos : null;

require __DIR__ . '/views/dopuna_uspesno.view.php';
