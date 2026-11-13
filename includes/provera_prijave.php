<?php
/**
 * Uključi na vrh svake stranice koja je samo za prijavljene studente:
 *   require_once __DIR__ . '/includes/provera_prijave.php';
 * Posle toga su dostupni $auth, $studentId, $karticaId i $zvonce.
 *
 * $zvonce su podaci za zvonce u zaglavlju svake stranice (views/delovi/korisnik.php):
 *   ['neprocitano' => 2,                 broj obaveštenja koja student još nije video
 *    'poruke' => [[naslov, tekst, kreirano, ciljna_grupa, menza, dom, novo], ...],   poslednjih 5, najnovije prvo
 *    'csrf' => '...']                    token kojim js/zvonce.js javlja da su obaveštenja pročitana
 *
 * Ako je administrator u međuvremenu blokirao karticu (admin_studenti.php), student se odjavljuje
 * na prvom sledećem kliku, pa ne može dalje da dopunjuje karticu, kupuje obroke ni plaća smeštaj.
 */
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/KarticaRepository.php';
require_once __DIR__ . '/../src/ObavestenjeRepository.php';

$auth = new Auth();
if (!$auth->check()) {
    header('Location: login.php');
    exit;
}

$studentId = $auth->studentId();
$karticaId = $auth->karticaId();

$karticaUSesiji = (new KarticaRepository())->read($karticaId);
if (!$karticaUSesiji || !$karticaUSesiji->jeAktivna()) {
    $auth->logout();
    header('Location: login.php?blokirana=1');
    exit;
}
unset($karticaUSesiji);

$obavestenjaZaZvonce = new ObavestenjeRepository();
$zvonce = [
    'neprocitano' => $obavestenjaZaZvonce->brojNeprocitanih($studentId),
    'poruke' => $obavestenjaZaZvonce->zaStudenta($studentId, 5),
    'csrf' => $auth->generateCsrfToken(),
];
unset($obavestenjaZaZvonce);
