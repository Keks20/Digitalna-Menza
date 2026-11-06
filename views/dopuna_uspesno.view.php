<?php
/**
 * PRIKAZ: potvrda uplate posle dopune (izgled po Figmi – ekran "Dopuna-uspesno").
 *
 * Isti ekran za sva tri stanja uplate: uspešna, čeka potvrdu (IPS) i odbijena.
 * "Preuzmi potvrdu (PDF)" otvara štampu, a odatle se bira "Sačuvaj kao PDF".
 * Spisak promenljivih je na vrhu dopuna_uspesno.php.
 */
$naslovStrane = 'Uplata';
$aktivnaStrana = 'dopuna.php';
$stilovi = ['css/dopuna.css'];
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/meni.php';

$stanja = [
    'uspesno' => [
        'naslov' => 'Uplata je uspešna', 'strana' => 'Uplata uspešna', 'ikonica' => 'kvacica', 'boja' => 'result-green',
        'tekst' => Format::novac($uplata->iznos) . ' je dodato na tvoju studentsku karticu.',
    ],
    'ceka_potvrdu' => [
        'naslov' => 'Uplata čeka potvrdu', 'strana' => 'Uplata čeka potvrdu', 'ikonica' => 'pescani-sat', 'boja' => 'result-orange',
        'tekst' => 'Novac će biti na kartici čim banka potvrdi IPS uplatu.',
    ],
    'odbijeno' => [
        'naslov' => 'Uplata nije prošla', 'strana' => 'Uplata nije prošla', 'ikonica' => 'iks', 'boja' => 'result-red',
        'tekst' => 'Novac nije dodat na karticu. Možeš da pokušaš ponovo.',
    ],
];
$s = $stanja[$uplata->status];
?>
  <main class="app-main app-main-wide">

    <header class="page-header d-flex justify-content-between align-items-start gap-3">
      <div>
        <h1 class="page-title"><?php echo $s['strana']; ?></h1>
        <p class="page-date">Potvrda o dopuni studentske kartice</p>
      </div>
      <div class="no-print">
        <?php require __DIR__ . '/delovi/korisnik.php'; ?>
      </div>
    </header>

    <section class="panel result-panel text-center mx-auto">
      <span class="result-icon <?php echo $s['boja']; ?>"><span><?php echo ikonica($s['ikonica']); ?></span></span>
      <h2 class="result-title"><?php echo $s['naslov']; ?></h2>
      <p class="result-text"><?php echo $s['tekst']; ?></p>

      <div class="text-start mt-3">
        <div class="summary-row"><span>Datum i vreme</span><strong><?php echo date('d.m.Y. \u H:i', strtotime($uplata->kreirano)); ?></strong></div>
        <div class="summary-row"><span>Način plaćanja</span><strong><?php echo Format::nacinPlacanja($uplata->nacin); ?></strong></div>
        <div class="summary-row"><span>Broj transakcije</span><strong><?php echo $brojTransakcije; ?></strong></div>
        <div class="summary-row"><span>Iznos</span><strong><?php echo Format::novac($uplata->iznos); ?></strong></div>
        <?php if ($novoStanje !== null): ?>
          <div class="summary-row"><span>Prethodno stanje</span><strong><?php echo Format::novac($prethodnoStanje); ?></strong></div>
          <div class="summary-total">
            <span>Novo stanje</span>
            <span class="amount amount-green"><?php echo Format::novac($novoStanje); ?></span>
          </div>
        <?php endif; ?>
      </div>

      <div class="d-flex flex-wrap gap-2 gap-lg-3 mt-4 no-print">
        <a href="pocetna.php" class="btn btn-primary btn-app btn-app-lg flex-fill">Nazad na početnu</a>
        <?php if ($uplata->status === 'odbijeno'): ?>
          <a href="dopuna.php" class="btn btn-app btn-app-outline btn-app-lg flex-fill">Pokušaj ponovo</a>
        <?php else: ?>
          <button type="button" class="btn btn-app btn-app-outline btn-app-lg flex-fill" onclick="window.print()">
            <?php echo ikonica('preuzmi'); ?> Preuzmi potvrdu (PDF)
          </button>
        <?php endif; ?>
      </div>
    </section>

  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
