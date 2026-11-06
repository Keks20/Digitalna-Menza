<?php
/**
 * PRIKAZ: studenti i kartice – pretraga i blokiranje kartice (samo administrator).
 *
 * Spisak promenljivih i imena polja forme su na vrhu admin_studenti.php.
 * Samo prikaz, bez upita u bazu; tekst iz baze se ispisuje kroz htmlspecialchars().
 * Pretraga i dugmad filtera su iste klase kao na Uplatama (admin_uplate.css), a css/admin_studenti.css dodaje redove spiska.
 */
$naslovStrane = 'Studenti';
$aktivnaStrana = 'admin_studenti.php';
$stilovi = ['css/jelovnik.css', 'css/admin_uplate.css', 'css/admin_studenti.css'];   // .btn-choice, .empty-state, pretraga
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/admin_meni.php';

$prvaNaStrani = $ukupno === 0 ? 0 : ($strana - 1) * $poStrani + 1;
$poslednjaNaStrani = ($strana - 1) * $poStrani + count($studenti);
$ukupnoKartica = array_sum($brojPoStatusu);
?>
  <main class="app-main app-main-wide">

    <!-- ZAGLAVLJE -->
    <header class="page-header d-flex justify-content-between align-items-start gap-3">
      <div>
        <h1 class="page-title">Studenti</h1>
        <p class="page-date">Pretraga studenata i blokiranje studentske kartice</p>
      </div>
      <?php require __DIR__ . '/delovi/korisnik.php'; ?>
    </header>

    <?php if ($poruka): ?>
      <div class="alert app-alert alert-<?php echo $poruka['tip'] === 'success' ? 'success' : 'danger'; ?> mb-3 mb-lg-4" role="alert">
        <?php echo htmlspecialchars($poruka['tekst']); ?>
      </div>
    <?php endif; ?>

    <section class="panel">
      <!-- Filter po statusu kartice i pretraga -->
      <div class="payments-tools d-flex flex-wrap align-items-center gap-2 mb-3">
        <nav class="d-flex flex-wrap gap-2" aria-label="Filter po statusu kartice">
          <?php foreach ($FILTERI as $vrednost => $naziv): ?>
            <?php
              $aktivan = $filterStatus === $vrednost;
              $broj = $vrednost === '' ? $ukupnoKartica : ($brojPoStatusu[$vrednost] ?? 0);
            ?>
            <a href="<?php echo htmlspecialchars(adminStudentiUrl(['status' => $vrednost, 'strana' => 1])); ?>"
               class="btn-choice btn-choice-sm<?php echo $aktivan ? ' active' : ''; ?>"<?php echo $aktivan ? ' aria-current="page"' : ''; ?>>
              <?php echo $naziv; ?>
              <span class="choice-count<?php echo $vrednost === 'blokirana' ? '' : ' choice-count-gray'; ?>"><?php echo $broj; ?></span>
            </a>
          <?php endforeach; ?>
        </nav>
        <form method="get" action="admin_studenti.php" class="payments-search students-search ms-lg-auto" role="search">
          <?php if ($filterStatus !== ''): ?>
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($filterStatus); ?>" />
          <?php endif; ?>
          <?php echo ikonica('pretraga', 'search-icon'); ?>
          <input type="search" class="form-control app-input" name="q" value="<?php echo htmlspecialchars($q); ?>"
                 maxlength="100" placeholder="Ime, indeks ili broj kartice" aria-label="Pretraga studenata" />
        </form>
      </div>

      <?php if (empty($studenti)): ?>
        <div class="empty-state text-center">
          <span class="tint-box tint-blue mx-auto"><?php echo ikonica('studenti'); ?></span>
          <p class="empty-title">Nema studenata za ovu pretragu</p>
          <p class="empty-text">Proveri ime ili broj, ili izaberi „Svi“.</p>
        </div>
      <?php else: ?>
        <div class="students-head d-none d-lg-grid" aria-hidden="true">
          <span>Student</span><span>Indeks i ustanova</span><span>Kartica</span><span class="text-end">Stanje</span><span>Status</span><span></span>
        </div>

        <?php foreach ($studenti as $s): ?>
          <?php
            $blokirana = $s['status'] === 'blokirana';
            $imePrezime = $s['ime'] . ' ' . $s['prezime'];
          ?>
          <article class="student-row<?php echo $blokirana ? ' is-blocked' : ''; ?>">
            <div class="st-name min-w-0">
              <p class="pay-name"><?php echo htmlspecialchars($imePrezime); ?></p>
              <p class="pay-sub text-truncate"><?php echo htmlspecialchars($s['email']); ?></p>
            </div>
            <div class="st-school min-w-0">
              <p class="st-index"><?php echo htmlspecialchars($s['broj_indeksa']); ?></p>
              <p class="pay-sub text-truncate"><?php echo htmlspecialchars($s['ustanova']); ?></p>
            </div>
            <div class="st-card"><?php echo htmlspecialchars(Format::brojKartice($s['broj_kartice'])); ?></div>
            <div class="st-balance"><?php echo Format::novac($s['stanje']); ?></div>
            <div class="st-status">
              <span class="tag ms-0 <?php echo $blokirana ? 'tag-red' : 'tag-green'; ?>"><?php echo $blokirana ? 'Blokirana' : 'Aktivna'; ?></span>
            </div>
            <form method="post" action="admin_studenti.php" class="st-action"
                  <?php if (!$blokirana): ?>onsubmit="return confirm(<?php echo htmlspecialchars(json_encode('Blokirati karticu za ' . $imePrezime . '? Student više neće moći da se prijavi, dopuni karticu, kupi obroke ni dobije obrok na kasi.')); ?>);"<?php endif; ?>>
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
              <input type="hidden" name="akcija" value="<?php echo $blokirana ? 'odblokiraj' : 'blokiraj'; ?>" />
              <input type="hidden" name="kartica_id" value="<?php echo (int)$s['kartica_id']; ?>" />
              <input type="hidden" name="filter_q" value="<?php echo htmlspecialchars($q); ?>" />
              <input type="hidden" name="filter_status" value="<?php echo htmlspecialchars($filterStatus); ?>" />
              <input type="hidden" name="filter_strana" value="<?php echo $strana; ?>" />
              <?php if ($blokirana): ?>
                <button type="submit" class="btn-pay btn-pay-ok"
                        aria-label="Odblokiraj karticu: <?php echo htmlspecialchars($imePrezime); ?>"><?php echo ikonica('kvacica'); ?> Odblokiraj</button>
              <?php else: ?>
                <button type="submit" class="btn-pay btn-pay-no"
                        aria-label="Blokiraj karticu: <?php echo htmlspecialchars($imePrezime); ?>"><?php echo ikonica('brava'); ?> Blokiraj</button>
              <?php endif; ?>
            </form>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>

      <!-- Strane -->
      <div class="payments-foot d-flex flex-wrap justify-content-between align-items-center gap-3">
        <p class="mb-0">
          <?php if ($ukupno > 0): ?>
            Prikazano <?php echo $prvaNaStrani; ?>–<?php echo $poslednjaNaStrani; ?> od <strong><?php echo $ukupno; ?></strong>
            <?php echo Format::mnozina($ukupno, 'studenta', 'studenta', 'studenata'); ?>
          <?php else: ?>
            Prikazano 0 studenata
          <?php endif; ?>
        </p>
        <?php if ($brojStrana > 1): ?>
          <nav class="d-flex align-items-center gap-2" aria-label="Strane">
            <?php if ($strana > 1): ?>
              <a href="<?php echo htmlspecialchars(adminStudentiUrl(['strana' => $strana - 1])); ?>" class="btn btn-app btn-app-outline" rel="prev">Prethodna</a>
            <?php else: ?>
              <span class="btn btn-app btn-app-outline disabled" aria-disabled="true">Prethodna</span>
            <?php endif; ?>
            <span class="st-page"><?php echo $strana; ?> / <?php echo $brojStrana; ?></span>
            <?php if ($strana < $brojStrana): ?>
              <a href="<?php echo htmlspecialchars(adminStudentiUrl(['strana' => $strana + 1])); ?>" class="btn btn-app btn-app-outline" rel="next">Sledeća</a>
            <?php else: ?>
              <span class="btn btn-app btn-app-outline disabled" aria-disabled="true">Sledeća</span>
            <?php endif; ?>
          </nav>
        <?php endif; ?>
      </div>
    </section>
  </main>

<?php require __DIR__ . '/delovi/podnozje.php'; ?>
