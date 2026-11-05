<?php
/**
 * Desna strana zaglavlja stranice: zvonce, krug sa inicijalima i ime.
 * Radi i za studenta i za zaposlene (admin, kasir).
 *
 * Stranica pre uključivanja ima JEDNO od:
 *   $student         Student  (studentske stranice)
 *   $admin           Admin    (stranice za zaposlene)
 * i opciono:
 *   $imaObavestenja  bool     narandžasta tačkica na zvoncu (podrazumevano true)
 *
 * Upotreba u view fajlu (unutar <header class="page-header ...">):
 *   <?php require __DIR__ . '/delovi/korisnik.php'; ?>
 */
require_once __DIR__ . '/ikonice.php';

$imaObavestenja = $imaObavestenja ?? true;

if (isset($student)) {
    $imeKorisnika = $student->punoIme();
    $inicijaliKorisnika = $student->inicijali();
    $podnaslovKorisnika = trim($student->ustanova . ' · ' . $student->broj_indeksa, ' ·');
} else {
    $imeKorisnika = $admin->ime_prezime;
    $inicijaliKorisnika = $admin->inicijali();
    $podnaslovKorisnika = $admin->nazivUloge() . ' · SC Kragujevac';
}
?>
      <div class="d-flex align-items-center gap-3 flex-shrink-0">
        <button type="button" class="btn-bell" aria-label="Obaveštenja">
          <?php echo ikonica('zvono'); ?>
          <?php if ($imaObavestenja): ?><span class="bell-dot"></span><?php endif; ?>
        </button>
        <span class="avatar"><?php echo htmlspecialchars($inicijaliKorisnika); ?></span>
        <div class="d-none d-lg-block">
          <div class="user-name"><?php echo htmlspecialchars($imeKorisnika); ?></div>
          <div class="user-sub"><?php echo htmlspecialchars($podnaslovKorisnika); ?></div>
        </div>
      </div>
