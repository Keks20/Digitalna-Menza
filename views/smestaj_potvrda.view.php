<?php
/**
 * PRIKAZ: potvrda o plaćenoj stanarini (isti izgled kao potvrda dopune – ekran "Dopuna-uspesno").
 *
 * Strana je napravljena i za štampu: "Preuzmi potvrdu (PDF)" otvara štampu, a odatle "Sačuvaj kao PDF".
 * Spisak promenljivih je na vrhu smestaj_potvrda.php.
 */
$naslovStrane = 'Potvrda o plaćanju stanarine';
$aktivnaStrana = 'smestaj.php';
$stilovi = ['css/dopuna.css'];   // .result-panel, .result-icon
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/meni.php';

$mesecZaduzenja = ucfirst(Format::mesecGodina($zaduzenje->mesec));
?>
  <main class="app-main app-main-wide">

    <header class="page-header d-flex justify-content-between align-items-start gap-3">
      <div>
        <a href="smestaj.php" class="back-link no-print"><?php echo ikonica('nazad'); ?> Smeštaj</a>
        <h1 class="page-title">Potvrda o plaćanju</h1>
        <p class="page-date">Stanarina za studentski dom · <?php echo $mesecZaduzenja; ?></p>
      </div>
      <div class="no-print">
        <?php require __DIR__ . '/delovi/korisnik.php'; ?>
      </div>
    </header>

    <section class="panel result-panel text-center mx-auto">
      <span class="result-icon result-green"><span><?php echo ikonica('kvacica'); ?></span></span>
      <h2 class="result-title">Stanarina je plaćena</h2>
      <p class="result-text"><?php echo Format::novac($zaduzenje->iznos); ?> za <?php echo lcfirst($mesecZaduzenja); ?>.</p>

      <div class="text-start mt-3">
        <div class="summary-row"><span>Student</span><strong><?php echo htmlspecialchars($student->punoIme()); ?></strong></div>
        <div class="summary-row"><span>Broj indeksa</span><strong><?php echo htmlspecialchars($student->broj_indeksa); ?></strong></div>
        <div class="summary-row"><span>Kartica</span><strong><?php echo htmlspecialchars($kartica->formatiranBroj()); ?></strong></div>
        <div class="summary-row">
          <span>Smeštaj</span>
          <strong>
            <?php echo htmlspecialchars($zaduzenje->dom); ?> · Paviljon <?php echo htmlspecialchars($zaduzenje->paviljon); ?>
            · soba <?php echo htmlspecialchars($zaduzenje->soba); ?>
          </strong>
        </div>
        <?php if ($ugovor !== null): ?>
          <div class="summary-row"><span>Školska godina</span><strong><?php echo htmlspecialchars($ugovor['skolska_godina']); ?></strong></div>
        <?php endif; ?>
        <div class="summary-row"><span>Mesec</span><strong><?php echo $mesecZaduzenja; ?></strong></div>
        <div class="summary-row"><span>Datum plaćanja</span><strong><?php echo $zaduzenje->datum_placanja ? Format::datum($zaduzenje->datum_placanja) : '–'; ?></strong></div>
        <div class="summary-row"><span>Način plaćanja</span><strong><?php echo htmlspecialchars($nacinPlacanja); ?></strong></div>
        <div class="summary-row"><span>Broj transakcije</span><strong><?php echo htmlspecialchars($brojTransakcije); ?></strong></div>
        <div class="summary-total">
          <span>Plaćeno</span>
          <span class="amount amount-green"><?php echo Format::novac($zaduzenje->iznos); ?></span>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 gap-lg-3 mt-4 no-print">
        <a href="smestaj.php" class="btn btn-primary btn-app btn-app-lg flex-fill">Nazad na smeštaj</a>
        <button type="button" class="btn btn-app btn-app-outline btn-app-lg flex-fill" onclick="window.print()">
          <?php echo ikonica('preuzmi'); ?> Preuzmi potvrdu (PDF)
        </button>
      </div>
    </section>

  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
