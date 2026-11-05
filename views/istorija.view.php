<?php
/**
 * PRIKAZ: istorija transakcija (izgled po Figmi – ekran "Istorija").
 *
 * Gore filter (tip, period) i izvoz, ispod zbirovi za period i tabela sa stranama.
 * Kod izvoza u PDF ($stampa) nema filtera ni strana – prikazuje se sve i otvara se štampa.
 * Spisak promenljivih je na vrhu istorija.php.
 * Samo prikaz, bez upita u bazu; tekst iz baze se ispisuje kroz htmlspecialchars().
 */
$naslovStrane = 'Istorija transakcija';
$aktivnaStrana = 'istorija.php';
$stilovi = ['css/jelovnik.css', 'css/istorija.css'];   // .btn-choice (dugmad za izbor) je iz jelovnik.css
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/meni.php';

// Izgled kartice sa zbirom i oznake u tabeli za svaki tip
$IZGLED_TIPA = [
    'uplata' => ['ikonica' => 'uplata', 'boja' => 'tint-green', 'oznaka' => 'tag-green', 'zbir' => 'Uplaćeno u periodu'],
    'obroci' => ['ikonica' => 'korpa', 'boja' => 'tint-blue', 'oznaka' => 'tag-blue', 'zbir' => 'Kupovina obroka'],
    'smestaj' => ['ikonica' => 'kuca', 'boja' => 'tint-orange', 'oznaka' => 'tag-orange', 'zbir' => 'Smeštaj'],
];
$OZNAKA_STATUSA = [
    'ceka_potvrdu' => 'tag-orange',
    'odbijeno' => 'tag-red',
];

$period = Format::datum($od) . ' – ' . Format::datum($do);
$prvaNaStrani = $ukupno === 0 ? 0 : ($strana - 1) * $poStrani + 1;
$poslednjaNaStrani = ($strana - 1) * $poStrani + count($transakcije);
?>
  <main class="app-main app-main-wide">

    <header class="page-header d-flex justify-content-between align-items-start gap-3">
      <div>
        <h1 class="page-title">Istorija transakcija</h1>
        <p class="page-date">
          <?php if ($stampa): ?>
            <?php echo htmlspecialchars($student->punoIme()); ?> · kartica <?php echo htmlspecialchars($kartica->formatiranBroj()); ?>
            · period <?php echo $period; ?><?php echo $tip !== '' ? ' · ' . $TIPOVI[$tip] : ''; ?>
          <?php else: ?>
            Sve uplate, kupovine obroka i plaćanja smeštaja
          <?php endif; ?>
        </p>
      </div>
      <div class="no-print">
        <?php require __DIR__ . '/delovi/korisnik.php'; ?>
      </div>
    </header>

    <?php if ($greska !== ''): ?>
      <div class="alert app-alert alert-danger mb-3 mb-lg-4 no-print" role="alert"><?php echo htmlspecialchars($greska); ?></div>
    <?php endif; ?>

    <?php if ($stampa): ?>
      <!-- Traka za štampu: pregledač sam otvara prozor za štampu, a odatle "Sačuvaj kao PDF" -->
      <div class="panel print-bar d-flex flex-wrap align-items-center gap-2 mb-3 mb-lg-4 no-print">
        <p class="mb-0 me-auto">U prozoru za štampu izaberi <strong>„Sačuvaj kao PDF"</strong>.</p>
        <a href="<?php echo htmlspecialchars(istorijaUrl()); ?>" class="btn btn-app btn-app-outline"><?php echo ikonica('levo'); ?> Nazad</a>
        <button type="button" class="btn btn-primary btn-app" onclick="window.print()"><?php echo ikonica('preuzmi'); ?> Sačuvaj kao PDF</button>
      </div>
    <?php else: ?>
      <!-- FILTER: tip, period i izvoz -->
      <section class="panel history-filter mb-3 mb-lg-4 no-print">
        <div class="d-flex flex-wrap align-items-end gap-3">
          <nav class="d-flex flex-wrap gap-2" aria-label="Vrsta transakcija">
            <?php foreach ($TIPOVI as $vrednost => $naziv): ?>
              <?php $aktivan = $tip === $vrednost; ?>
              <a href="<?php echo htmlspecialchars(istorijaUrl(['tip' => $vrednost, 'strana' => 1])); ?>"
                 class="btn-choice<?php echo $aktivan ? ' active' : ''; ?>"<?php echo $aktivan ? ' aria-current="page"' : ''; ?>><?php echo $naziv; ?></a>
            <?php endforeach; ?>
          </nav>

          <form method="get" action="istorija.php" class="history-period flex-fill">
            <?php if ($tip !== ''): ?>
              <input type="hidden" name="tip" value="<?php echo htmlspecialchars($tip); ?>" />
            <?php endif; ?>
            <p class="form-label mb-2" id="periodOznaka">Period</p>
            <div class="d-flex flex-wrap gap-2" role="group" aria-labelledby="periodOznaka">
              <div class="period-dates">
                <input type="date" class="form-control app-input" name="od" value="<?php echo htmlspecialchars($od); ?>"
                       max="<?php echo date('Y-m-d'); ?>" aria-label="Od datuma" required />
                <span class="text-muted" aria-hidden="true">–</span>
                <input type="date" class="form-control app-input" name="do" value="<?php echo htmlspecialchars($do); ?>"
                       max="<?php echo date('Y-m-d'); ?>" aria-label="Do datuma" required />
              </div>
              <button type="submit" class="btn btn-primary btn-app period-btn">Prikaži</button>
            </div>
          </form>

          <div class="d-flex gap-2">
            <a href="<?php echo htmlspecialchars(istorijaUrl(['izvoz' => 'csv', 'strana' => 1])); ?>" class="btn btn-app btn-app-outline">
              <?php echo ikonica('preuzmi'); ?> Izvezi CSV
            </a>
            <a href="<?php echo htmlspecialchars(istorijaUrl(['izvoz' => 'pdf', 'strana' => 1])); ?>" class="btn btn-app btn-app-outline">
              <?php echo ikonica('preuzmi'); ?> Izvezi PDF
            </a>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <!-- ZBIROVI za period (samo uspešne transakcije) -->
    <div class="row g-3 g-lg-4 mb-3 mb-lg-4">
      <?php foreach ($IZGLED_TIPA as $vrsta => $izgled): ?>
        <div class="col-md-4">
          <section class="stat-card sum-card h-100">
            <span class="tint-box <?php echo $izgled['boja']; ?>"><?php echo ikonica($izgled['ikonica']); ?></span>
            <p class="sum-value"><?php echo Format::novac($zbirovi[$vrsta], true); ?></p>
            <p class="sum-label"><?php echo $izgled['zbir']; ?></p>
          </section>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- TABELA -->
    <section class="panel tx-panel">
      <?php if (empty($transakcije)): ?>
        <div class="empty-state text-center">
          <span class="tint-box tint-blue mx-auto"><?php echo ikonica('istorija'); ?></span>
          <p class="empty-title">Nema transakcija u ovom periodu</p>
          <p class="empty-text">Izaberi duži period ili drugu vrstu transakcija.</p>
        </div>
      <?php else: ?>
        <div class="tx-head" aria-hidden="true">
          <span>Datum</span><span>Opis</span><span class="tx-col-tip">Tip</span><span class="text-end">Iznos</span><span class="tx-col-stanje text-end">Stanje posle</span>
        </div>

        <?php foreach ($transakcije as $t): ?>
          <?php
            $uspesna = $t->status === 'uspesno';
            $klasaIznosa = !$uspesna ? 'tx-muted' : ($t->iznos > 0 ? 'tx-plus' : '');
          ?>
          <article class="tx-row" title="<?php echo Format::brojTransakcije((int)$t->id, $t->kreirano); ?>">
            <div class="tx-date"><?php echo Format::datumVreme($t->kreirano); ?></div>
            <div class="tx-desc min-w-0">
              <?php echo htmlspecialchars($t->opis); ?>
              <?php if (!$uspesna): ?>
                <span class="tag <?php echo $OZNAKA_STATUSA[$t->status] ?? 'tag-gray'; ?>"><?php echo Format::statusTransakcije($t->status); ?></span>
              <?php endif; ?>
            </div>
            <div class="tx-col-tip">
              <span class="tag ms-0 <?php echo $IZGLED_TIPA[$t->tip]['oznaka'] ?? 'tag-gray'; ?>"><?php echo Format::tipTransakcije($t->tip); ?></span>
            </div>
            <div class="tx-amount <?php echo $klasaIznosa; ?><?php echo $t->status === 'odbijeno' ? ' tx-strike' : ''; ?>">
              <?php echo Format::novac($t->iznos, true); ?>
            </div>
            <div class="tx-col-stanje tx-balance">
              <?php echo $t->stanje_posle !== null ? Format::novac($t->stanje_posle) : '–'; ?>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>

      <?php if (!$stampa): ?>
        <!-- Strane -->
        <div class="tx-foot d-flex flex-wrap justify-content-between align-items-center gap-3 no-print">
          <p class="mb-0">
            <?php if ($ukupno > 0): ?>
              Prikazano <?php echo $prvaNaStrani; ?>–<?php echo $poslednjaNaStrani; ?> od <?php echo $ukupno; ?>
              <?php echo Format::mnozina($ukupno, 'transakcije', 'transakcije', 'transakcija'); ?>
            <?php endif; ?>
          </p>
          <?php if ($brojStrana > 1): ?>
            <nav class="d-flex align-items-center gap-2" aria-label="Strane">
              <?php if ($strana > 1): ?>
                <a href="<?php echo htmlspecialchars(istorijaUrl(['strana' => $strana - 1])); ?>" class="btn btn-app btn-app-outline" rel="prev">Prethodna</a>
              <?php else: ?>
                <span class="btn btn-app btn-app-outline disabled" aria-disabled="true">Prethodna</span>
              <?php endif; ?>
              <span class="tx-page"><?php echo $strana; ?> / <?php echo $brojStrana; ?></span>
              <?php if ($strana < $brojStrana): ?>
                <a href="<?php echo htmlspecialchars(istorijaUrl(['strana' => $strana + 1])); ?>" class="btn btn-app btn-app-outline" rel="next">Sledeća</a>
              <?php else: ?>
                <span class="btn btn-app btn-app-outline disabled" aria-disabled="true">Sledeća</span>
              <?php endif; ?>
            </nav>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <p class="tx-foot mb-0">
          Ukupno <?php echo $ukupno; ?> <?php echo Format::mnozina($ukupno, 'transakcija', 'transakcije', 'transakcija'); ?>
          · trenutno stanje <?php echo Format::novac($kartica->stanje); ?> · izvezeno <?php echo date('d.m.Y. \u H:i'); ?>
        </p>
      <?php endif; ?>
    </section>

  </main>
<?php if ($stampa): ?>
  <script>
    // Prozor za štampu se otvara sam čim se strana učita
    window.addEventListener('load', function () { window.print(); });
  </script>
<?php endif; ?>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
