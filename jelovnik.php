<?php
/**
 * LOGIKA: dnevni i nedeljni jelovnik.
 * Izgled je u views/jelovnik.view.php.
 *
 * Adresa: jelovnik.php?menza=2&dan=2026-11-05
 *   menza – id menze (ako nije zadat ili ne postoji: prva menza)
 *   dan   – izabrani dan 'Y-m-d' (ako nije zadat ili nije ispravan: danas)
 *
 * Prikaz dobija:
 *   $student            Student      za krug sa inicijalima i ime u zaglavlju
 *   $menze              array        [['id', 'naziv', ..., 'izabrana' (bool), 'url' (link za dugme)], ...]
 *   $izabranaMenza      array        menza čiji se jelovnik prikazuje
 *   $izabraniDan        string       'Y-m-d'
 *   $danas              string       'Y-m-d'
 *   $dani               array        7 dana nedelje: ['datum', 'dan' (Pon), 'broj' (5), 'izabran' (bool), 'danas' (bool), 'url']
 *   $nedeljaOpis        string       npr. "2–8. novembar 2026."
 *   $prethodnaNedeljaUrl, $sledecaNedeljaUrl  string  linkovi za strelice levo/desno
 *   $tipoviObroka       TipObroka[]  doručak, ručak, večera: naziv, cena, radnoVreme()
 *   $jelovnik           array        [tip_obroka_id => [StavkaJelovnika, ...]] za izabrani dan
 *   $imaJelovnika       bool         false = za taj dan još nije objavljen jelovnik
 */
require_once __DIR__ . '/includes/provera_prijave.php';
require_once __DIR__ . '/src/StudentRepository.php';
require_once __DIR__ . '/src/MenzaRepository.php';
require_once __DIR__ . '/src/TipObrokaRepository.php';
require_once __DIR__ . '/src/JelovnikRepository.php';
require_once __DIR__ . '/src/Format.php';
require_once __DIR__ . '/src/Kalendar.php';

$student = (new StudentRepository())->read($studentId);
$menze = (new MenzaRepository())->list();
$tipoviObroka = (new TipObrokaRepository())->list();
$danas = date('Y-m-d');

// ---------- Izabrana menza (iz adrese, inače prva) ----------
$izabranaMenza = $menze[0];
$menzaId = (int)($_GET['menza'] ?? 0);
foreach ($menze as $menza) {
    if ((int)$menza['id'] === $menzaId) {
        $izabranaMenza = $menza;
    }
}
$izabranaMenzaId = (int)$izabranaMenza['id'];

// ---------- Izabrani dan (iz adrese, inače danas) ----------
$izabraniDan = Kalendar::datumIzAdrese($_GET['dan'] ?? null, $danas);

/** Link ka jelovniku za datu menzu i dan */
function jelovnikUrl(int $menzaId, string $dan): string
{
    return 'jelovnik.php?' . http_build_query(['menza' => $menzaId, 'dan' => $dan]);
}

// Dugmad za menze: link zadržava izabrani dan
foreach ($menze as $i => $menza) {
    $menze[$i]['izabrana'] = (int)$menza['id'] === $izabranaMenzaId;
    $menze[$i]['url'] = jelovnikUrl((int)$menza['id'], $izabraniDan);
}

// ---------- Nedelja u kojoj je izabrani dan (ponedeljak–nedelja) ----------
$dani = Kalendar::daniNedelje($izabraniDan);
foreach ($dani as $i => $dan) {
    $dani[$i]['url'] = jelovnikUrl($izabranaMenzaId, $dan['datum']);
}

$nedeljaOpis = Format::nedelja(Kalendar::ponedeljak($izabraniDan));
// Strelice vode na isti dan u prethodnoj / sledećoj nedelji
$prethodnaNedeljaUrl = jelovnikUrl($izabranaMenzaId, Kalendar::pomeri($izabraniDan, -7));
$sledecaNedeljaUrl = jelovnikUrl($izabranaMenzaId, Kalendar::pomeri($izabraniDan, 7));

// ---------- Jelovnik za izabrani dan ----------
$jelovnik = (new JelovnikRepository())->zaDan($izabranaMenzaId, $izabraniDan);
$imaJelovnika = !empty($jelovnik);

require __DIR__ . '/views/jelovnik.view.php';
