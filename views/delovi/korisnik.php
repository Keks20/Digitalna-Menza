<?php
/**
 * Desna strana zaglavlja stranice: zvonce, krug sa inicijalima (ili profilnom slikom) i ime.
 * Radi i za studenta i za zaposlene (admin, kasir).
 *
 * Stranica pre uključivanja ima JEDNO od:
 *   $student         Student  (studentske stranice)
 *   $admin           Admin    (stranice za zaposlene)
 *
 * Student: klik na zvonce otvara poslednja obaveštenja, a na zvoncetu je broj nepročitanih (više od 3 = "3+").
 * Podaci stižu u $zvonce iz includes/provera_prijave.php:
 *   ['neprocitano' => int, 'poruke' => [[naslov, tekst, kreirano, novo, ...], ...], 'csrf' => string]
 * Kad se lista otvori, js/zvonce.js javlja stranici obavestenja_procitano.php da su obaveštenja pročitana.
 * Na stranici bez $zvonce (kviz) zvonce je običan link na obavestenja.php.
 * Krug sa inicijalima i ime vode na profil.
 *
 * Zaposleni: zvonce bez liste; $imaObavestenja (bool, podrazumevano true) određuje narandžastu tačkicu.
 *
 * Upotreba u view fajlu (unutar <header class="page-header ...">):
 *   <?php require __DIR__ . '/delovi/korisnik.php'; ?>
 */
require_once __DIR__ . '/ikonice.php';
require_once __DIR__ . '/obavestenja_izgled.php';

// Najveći broj koji se ispisuje na zvoncetu; preko toga "3+"
if (!defined('ZVONCE_NAJVISE')) {
    define('ZVONCE_NAJVISE', 3);
}

$jeStudent = isset($student);

if ($jeStudent) {
    $imeKorisnika = $student->punoIme();
    $inicijaliKorisnika = $student->inicijali();
    $podnaslovKorisnika = trim($student->ustanova . ' · ' . $student->broj_indeksa, ' ·');
    $slikaKorisnika = $student->putanjaSlike();
} else {
    $imeKorisnika = $admin->ime_prezime;
    $inicijaliKorisnika = $admin->inicijali();
    $podnaslovKorisnika = $admin->nazivUloge() . ' · SC Kragujevac';
    $slikaKorisnika = null;
    $imaObavestenja = $imaObavestenja ?? true;
}

$imaZvonce = $jeStudent && isset($zvonce);
if ($imaZvonce) {
    $neprocitano = (int)$zvonce['neprocitano'];
    $brojNaZvoncetu = $neprocitano > ZVONCE_NAJVISE ? ZVONCE_NAJVISE . '+' : (string)$neprocitano;

    // Skripta zvonceta se učitava u podnožju, posle Bootstrap-a
    $skripte = $skripte ?? [];
    if (!in_array('js/zvonce.js', $skripte, true)) {
        $skripte[] = 'js/zvonce.js';
    }
}
?>
      <div class="d-flex align-items-center gap-3 flex-shrink-0">
        <?php if ($imaZvonce): ?>
          <div class="dropdown bell-wrap">
            <button type="button" class="btn-bell" id="zvonce" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                    data-bs-offset="0,10" aria-expanded="false"
                    data-csrf="<?php echo htmlspecialchars($zvonce['csrf']); ?>"
                    data-neprocitano="<?php echo $neprocitano; ?>"
                    aria-label="Obaveštenja<?php echo $neprocitano > 0 ? ' (' . $neprocitano . ' ' . Format::mnozina($neprocitano, 'nepročitano', 'nepročitana', 'nepročitanih') . ')' : ''; ?>">
              <?php echo ikonica('zvono'); ?>
              <?php if ($neprocitano > 0): ?><span class="bell-count" id="zvonceBroj"><?php echo $brojNaZvoncetu; ?></span><?php endif; ?>
            </button>

            <div class="dropdown-menu dropdown-menu-end bell-menu" aria-labelledby="zvonce">
              <div class="bell-head d-flex justify-content-between align-items-center">
                <strong>Obaveštenja</strong>
                <?php if ($neprocitano > 0): ?>
                  <span class="tag tag-orange"><?php echo $neprocitano; ?> <?php echo Format::mnozina($neprocitano, 'novo', 'nova', 'novih'); ?></span>
                <?php endif; ?>
              </div>

              <div class="bell-list">
                <?php foreach ($zvonce['poruke'] as $o): ?>
                  <?php $izgledO = izgledObavestenja($o); ?>
                  <article class="bell-item d-flex<?php echo !empty($o['novo']) ? ' is-new' : ''; ?>">
                    <span class="tint-box <?php echo $izgledO['boja']; ?>"><?php echo ikonica($izgledO['ikonica']); ?></span>
                    <div class="min-w-0">
                      <h3 class="bell-title"><?php echo htmlspecialchars($o['naslov']); ?></h3>
                      <p class="bell-text"><?php echo htmlspecialchars($o['tekst']); ?></p>
                      <p class="bell-meta">
                        <?php echo Format::datumVreme($o['kreirano']); ?> · <?php echo htmlspecialchars($izgledO['kome']); ?>
                      </p>
                    </div>
                  </article>
                <?php endforeach; ?>

                <?php if (empty($zvonce['poruke'])): ?>
                  <p class="bell-empty mb-0">Trenutno nema obaveštenja.</p>
                <?php endif; ?>
              </div>

              <a href="obavestenja.php" class="bell-all">Sva obaveštenja <?php echo ikonica('desno'); ?></a>
            </div>
          </div>
        <?php elseif ($jeStudent): ?>
          <a href="obavestenja.php" class="btn-bell" aria-label="Obaveštenja"><?php echo ikonica('zvono'); ?></a>
        <?php else: ?>
          <button type="button" class="btn-bell" aria-label="Obaveštenja">
            <?php echo ikonica('zvono'); ?>
            <?php if ($imaObavestenja): ?><span class="bell-dot"></span><?php endif; ?>
          </button>
        <?php endif; ?>

        <?php if ($jeStudent): ?>
          <a href="profil.php" class="user-link d-flex align-items-center gap-3" aria-label="Profil: <?php echo htmlspecialchars($imeKorisnika); ?>">
            <?php if ($slikaKorisnika !== null): ?>
              <img src="<?php echo htmlspecialchars($slikaKorisnika); ?>" alt="" class="avatar avatar-img" width="42" height="42" />
            <?php else: ?>
              <span class="avatar"><?php echo htmlspecialchars($inicijaliKorisnika); ?></span>
            <?php endif; ?>
            <span class="d-none d-lg-block">
              <span class="user-name d-block"><?php echo htmlspecialchars($imeKorisnika); ?></span>
              <span class="user-sub d-block"><?php echo htmlspecialchars($podnaslovKorisnika); ?></span>
            </span>
          </a>
        <?php else: ?>
          <span class="avatar"><?php echo htmlspecialchars($inicijaliKorisnika); ?></span>
          <div class="d-none d-lg-block">
            <div class="user-name"><?php echo htmlspecialchars($imeKorisnika); ?></div>
            <div class="user-sub"><?php echo htmlspecialchars($podnaslovKorisnika); ?></div>
          </div>
        <?php endif; ?>
      </div>
