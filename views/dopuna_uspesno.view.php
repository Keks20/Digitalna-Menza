<?php
/**
 * PRIKAZ: potvrda uplate posle dopune (izgled po Figmi – ekran "Dopuna-uspesno").
 *
 * Isti ekran za sva stanja uplate: uspešna, čeka potvrdu (IPS), odbijena i refundirana.
 * Uspešna uplata ima dugme "Refundiraj uplatu" dok važi rok i dok novac nije potrošen.
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
if ($uplata->refundirano !== null) {
    $s = [
        'naslov' => 'Uplata je refundirana', 'strana' => 'Uplata refundirana', 'ikonica' => 'nazad', 'boja' => 'result-orange',
        'tekst' => Format::novac($uplata->iznos) . ' je skinuto sa studentske kartice '
            . date('d.m.Y. \u H:i', strtotime($uplata->refundirano)) . ' i vraća se na način na koji je uplaćeno.',
    ];
}
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

    <?php if ($poruka): ?>
      <div class="alert app-alert alert-<?php echo $poruka['tip'] === 'success' ? 'success' : 'danger'; ?> mb-3 mb-lg-4 no-print" role="alert"><?php echo htmlspecialchars($poruka['tekst']); ?></div>
    <?php endif; ?>

    <section class="panel result-panel text-center mx-auto">
      <span class="result-icon <?php echo $s['boja']; ?>"><span><?php echo ikonica($s['ikonica']); ?></span></span>
      <h2 class="result-title"><?php echo $s['naslov']; ?></h2>
      <p class="result-text"><?php echo $s['tekst']; ?></p>

      <div class="text-start mt-3">
        <div class="summary-row"><span>Datum i vreme</span><strong><?php echo date('d.m.Y. \u H:i', strtotime($uplata->kreirano)); ?></strong></div>
        <div class="summary-row"><span>Način plaćanja</span><strong><?php echo Format::nacinPlacanja($uplata->nacin); ?></strong></div>
        <div class="summary-row"><span>Broj transakcije</span><strong><?php echo $brojTransakcije; ?></strong></div>
        <div class="summary-row"><span>Iznos</span><strong><?php echo Format::novac($uplata->iznos); ?></strong></div>
        <?php if ($novoStanje !== null && $uplata->refundirano === null): ?>
          <div class="summary-row"><span>Prethodno stanje</span><strong><?php echo Format::novac($prethodnoStanje); ?></strong></div>
          <div class="summary-total">
            <span>Novo stanje</span>
            <span class="amount amount-green"><?php echo Format::novac($novoStanje); ?></span>
          </div>
        <?php endif; ?>
      </div>

      <div class="d-flex flex-wrap gap-2 gap-lg-3 mt-4 no-print">
        <a href="pocetna.php" class="btn btn-primary btn-app btn-app-lg flex-fill">Nazad na početnu</a>
        <?php if ($uplata->status === 'odbijeno' || $uplata->refundirano !== null): ?>
          <a href="dopuna.php" class="btn btn-app btn-app-outline btn-app-lg flex-fill"><?php echo $uplata->refundirano !== null ? 'Nova dopuna' : 'Pokušaj ponovo'; ?></a>
        <?php else: ?>
          <button type="button" class="btn btn-app btn-app-outline btn-app-lg flex-fill" onclick="window.print()">
            <?php echo ikonica('preuzmi'); ?> Preuzmi potvrdu (PDF)
          </button>
        <?php endif; ?>
      </div>

      <?php if ($rokRefundacije !== null): ?>
        <!-- Refundacija: važi sat vremena i gasi se čim se sa kartice nešto plati -->
        <form method="post" action="refundacija.php" class="refund-box text-start mt-4 no-print"
              onsubmit="return confirm('Refundirati ovu uplatu? Iznos će biti skinut sa studentske kartice.');">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
          <input type="hidden" name="id" value="<?php echo (int)$uplata->id; ?>" />
          <input type="hidden" name="nazad" value="dopuna" />
          <div class="note-box">
            <?php echo ikonica('sat'); ?>
            <p>
              Ovu uplatu možeš da refundiraš do <strong><?php echo $rokRefundacije; ?></strong>
              (<?php echo (int)$rokMinuta; ?> minuta od uplate). Čim sa kartice kupiš obroke ili platiš stanarinu,
              refundacija više nije moguća.
            </p>
          </div>
          <button type="submit" class="btn btn-app btn-app-outline btn-refund w-100 mt-3"><?php echo ikonica('nazad'); ?> Refundiraj uplatu</button>
        </form>
      <?php endif; ?>
    </section>

  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
