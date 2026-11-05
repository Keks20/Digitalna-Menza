<?php
/**
 * LOGIKA: početna strana studenta (backend – Luka).
 * Izgled je u views/pocetna.view.php.
 *
 * Prikaz dobija:
 *   $danas            string            današnji datum, 'Y-m-d'
 *   $tekuciMesec      string            prvi dan tekućeg meseca, 'Y-m-01'
 *   $student          Student           ime, prezime, broj_indeksa, punoIme()
 *   $kartica          Kartica           stanje, formatiranBroj()
 *   $poslednjaUplata  Transakcija|null  poslednja uspešna uplata
 *   $obroci           array             po tipu obroka: naziv, kupljeno, iskorisceno, preostalo
 *   $preostaloUkupno  int               zbir preostalih obroka u mesecu
 *   $stanarina        SmestajZaduzenje|null  zaduženje za tekući mesec (null = nije u domu)
 *   $menze            array             [['id', 'naziv', 'adresa', ...], ...]
 *   $tipoviObroka     TipObroka[]       doručak, ručak, večera (naziv, cena, radnoVreme())
 *   $jelovnikDanas    array             [menza_id => [tip_obroka_id => [StavkaJelovnika, ...]]]
 *   $poruke           array             poslednja 3 obaveštenja: naslov, tekst, kreirano, menza
 */
require_once __DIR__ . '/includes/provera_prijave.php';
require_once __DIR__ . '/src/StudentRepository.php';
require_once __DIR__ . '/src/KarticaRepository.php';
require_once __DIR__ . '/src/TransakcijaRepository.php';
require_once __DIR__ . '/src/KupljeniObrokRepository.php';
require_once __DIR__ . '/src/SmestajRepository.php';
require_once __DIR__ . '/src/MenzaRepository.php';
require_once __DIR__ . '/src/TipObrokaRepository.php';
require_once __DIR__ . '/src/JelovnikRepository.php';
require_once __DIR__ . '/src/ObavestenjeRepository.php';
require_once __DIR__ . '/src/Format.php';

$danas = date('Y-m-d');
$tekuciMesec = date('Y-m-01');

$student = (new StudentRepository())->read($studentId);
$kartica = (new KarticaRepository())->read($karticaId);
$poslednjaUplata = (new TransakcijaRepository())->poslednjaUplata($karticaId);
$obroci = (new KupljeniObrokRepository())->stanjeZaMesec($karticaId, $tekuciMesec);
$stanarina = (new SmestajRepository())->tekuceZaduzenje($studentId);
$menze = (new MenzaRepository())->list();
$tipoviObroka = (new TipObrokaRepository())->list();
$poruke = (new ObavestenjeRepository())->list(['limit' => 3]);

$preostaloUkupno = array_sum(array_column($obroci, 'preostalo'));

// Današnji jelovnik za svaku menzu
$jelovnikRepo = new JelovnikRepository();
$jelovnikDanas = [];
foreach ($menze as $menza) {
    $jelovnikDanas[$menza['id']] = $jelovnikRepo->zaDan((int)$menza['id'], $danas);
}

require __DIR__ . '/views/pocetna.view.php';
