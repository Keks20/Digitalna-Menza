<?php
/**
 * PRIKAZ: dnevnik akcija zaposlenih – ko je, kada i šta uradio (samo administrator).
 *
 * Spisak promenljivih je na vrhu admin_dnevnik.php.
 * Samo prikaz, bez upita u bazu; tekst iz baze se ispisuje kroz htmlspecialchars().
 * Filter, pretraga i strane su iste klase kao na Uplatama i Studentima (admin_uplate.css, admin_studenti.css),
 * a css/admin_dnevnik.css dodaje redove dnevnika.
 */
$naslovStrane = 'Dnevnik';
$aktivnaStrana = 'admin_dnevnik.php';
$stilovi = ['css/jelovnik.css', 'css/admin_uplate.css', 'css/admin_studenti.css', 'css/admin_dnevnik.css'];
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/admin_meni.php';

// Boja oznake po vrsti akcije
$BOJE_AKCIJA = [
    'prijava' => 'tag-gray',
    'kartica' => 'tag-red',
    'kategorija' => 'tag-blue',
    'cene' => 'tag-orange',
    'obavestenje' => 'tag-green',
    'zaduzenja' => 'tag-blue',
];

$prviNaStrani = $ukupno === 0 ? 0 : ($strana - 1) * $poStrani + 1;
$poslednjiNaStrani = ($strana - 1) * $poStrani + count($zapisi);
$danas = date('Y-m-d');
?>
  <main class="app-main app-main-wide">

    <!-- ZAGLAVLJE -->
    <header class="page-header d-flex justify-content-between align-items-start gap-3">
      <div>
        <h1 class="page-title">Dnevnik</h1>
        <p class="page-date">Ko je, kada i šta uradio u administraciji</p>
      </div>
      <?php require __DIR__ . '/delovi/korisnik.php'; ?>
    </header>

    <section class="panel">
      <!-- Filter po vrsti akcije i pretraga -->
      <div class="payments-tools d-flex flex-wrap align-items-center gap-2 mb-3">
        <nav class="d-flex flex-wrap gap-2" aria-label="Filter po vrsti akcije">
          <?php foreach ($FILTERI as $vrednost => $naziv): ?>
            <?php $aktivan = $filterAkcija === (string)$vrednost; ?>
            <a href="<?php echo htmlspecialchars(adminDnevnikUrl(['akcija' => $vrednost, 'strana' => 1])); ?>"
               class="btn-choice btn-choice-sm<?php echo $aktivan ? ' active' : ''; ?>"<?php echo $aktivan ? ' aria-current="page"' : ''; ?>>
              <?php echo htmlspecialchars($naziv); ?>
            </a>
          <?php endforeach; ?>
        </nav>
        <form method="get" action="admin_dnevnik.php" class="payments-search students-search ms-lg-auto" role="search">
          <?php if ($filterAkcija !== ''): ?>
            <input type="hidden" name="akcija" value="<?php echo htmlspecialchars($filterAkcija); ?>" />
          <?php endif; ?>
          <?php echo ikonica('pretraga', 'search-icon'); ?>
          <input type="search" class="form-control app-input" name="q" value="<?php echo htmlspecialchars($q); ?>"
                 maxlength="100" placeholder="Opis ili ime zaposlenog" aria-label="Pretraga dnevnika" />
        </form>
      </div>

      <?php if (empty($zapisi)): ?>
        <div class="empty-state text-center">
          <span class="tint-box tint-blue mx-auto"><?php echo ikonica('istorija'); ?></span>
          <p class="empty-title">Nema zapisa za ovaj filter</p>
          <p class="empty-text">Izaberi „Sve“ ili promeni pretragu.</p>
        </div>
      <?php else: ?>
        <div class="log-head d-none d-lg-grid" aria-hidden="true">
          <span>Vreme</span><span>Zaposleni</span><span>Akcija</span><span>Opis</span><span>Adresa</span>
        </div>

        <?php foreach ($zapisi as $z): ?>
          <?php $kada = strtotime($z['vreme']); ?>
          <article class="log-row">
            <div class="log-time">
              <span class="pay-clock"><?php echo date('H:i', $kada); ?></span>
              <span class="pay-date"><?php echo date('Y-m-d', $kada) === $danas ? 'danas' : date('d.m.Y.', $kada); ?></span>
            </div>
            <div class="log-admin min-w-0"><p class="pay-name"><?php echo htmlspecialchars($z['admin_ime']); ?></p></div>
            <div class="log-action">
              <span class="tag ms-0 <?php echo $BOJE_AKCIJA[$z['akcija']] ?? 'tag-gray'; ?>"><?php echo htmlspecialchars($z['naziv_akcije']); ?></span>
            </div>
            <div class="log-text"><?php echo htmlspecialchars($z['opis']); ?></div>
            <div class="log-ip"><?php echo htmlspecialchars($z['ip']); ?></div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>

      <!-- Strane -->
      <div class="payments-foot d-flex flex-wrap justify-content-between align-items-center gap-3">
        <p class="mb-0">
          <?php if ($ukupno > 0): ?>
            Prikazano <?php echo $prviNaStrani; ?>–<?php echo $poslednjiNaStrani; ?> od <strong><?php echo (int)$ukupno; ?></strong>
            <?php echo Format::mnozina($ukupno, 'zapisa', 'zapisa', 'zapisa'); ?>
          <?php else: ?>
            Prikazano 0 zapisa
          <?php endif; ?>
        </p>
        <?php if ($brojStrana > 1): ?>
          <nav class="d-flex align-items-center gap-2" aria-label="Strane">
            <?php if ($strana > 1): ?>
              <a href="<?php echo htmlspecialchars(adminDnevnikUrl(['strana' => $strana - 1])); ?>" class="btn btn-app btn-app-outline" rel="prev">Prethodna</a>
            <?php else: ?>
              <span class="btn btn-app btn-app-outline disabled" aria-disabled="true">Prethodna</span>
            <?php endif; ?>
            <span class="st-page"><?php echo (int)$strana; ?> / <?php echo (int)$brojStrana; ?></span>
            <?php if ($strana < $brojStrana): ?>
              <a href="<?php echo htmlspecialchars(adminDnevnikUrl(['strana' => $strana + 1])); ?>" class="btn btn-app btn-app-outline" rel="next">Sledeća</a>
            <?php else: ?>
              <span class="btn btn-app btn-app-outline disabled" aria-disabled="true">Sledeća</span>
            <?php endif; ?>
          </nav>
        <?php endif; ?>
      </div>
    </section>

  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
