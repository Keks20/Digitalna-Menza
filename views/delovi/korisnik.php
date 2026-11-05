<?php
/**
 * Desna strana zaglavlja stranice: zvonce, krug sa inicijalima i ime studenta.
 *
 * Stranica pre uključivanja ima:
 *   $student         Student  (obavezno)
 *   $imaObavestenja  bool     narandžasta tačkica na zvoncu (nije obavezno, podrazumevano true)
 *
 * Upotreba u view fajlu (unutar <header class="page-header ...">):
 *   <?php require __DIR__ . '/delovi/korisnik.php'; ?>
 */
require_once __DIR__ . '/ikonice.php';

$imaObavestenja = $imaObavestenja ?? true;
$podnaslovKorisnika = trim($student->ustanova . ' · ' . $student->broj_indeksa, ' ·');
?>
      <div class="d-flex align-items-center gap-3 flex-shrink-0">
        <button type="button" class="btn-bell" aria-label="Obaveštenja">
          <?php echo ikonica('zvono'); ?>
          <?php if ($imaObavestenja): ?><span class="bell-dot"></span><?php endif; ?>
        </button>
        <span class="avatar"><?php echo htmlspecialchars($student->inicijali()); ?></span>
        <div class="d-none d-lg-block">
          <div class="user-name"><?php echo htmlspecialchars($student->punoIme()); ?></div>
          <div class="user-sub"><?php echo htmlspecialchars($podnaslovKorisnika); ?></div>
        </div>
      </div>
