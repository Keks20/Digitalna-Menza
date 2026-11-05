<?php
/**
 * PRIKAZ: potvrda uplate posle dopune.
 *
 * Privremeni raspored – konačni izgled je ekran "Dopuna-uspesno" u Figmi.
 * Spisak promenljivih je na vrhu dopuna_uspesno.php.
 */
$naslovStrane = 'Uplata';
$klasaTela = 'bg-light';
$aktivnaStrana = 'dopuna.php';
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/meni.php';

$naslovi = [
    'uspesno' => ['Uplata je uspešna', Format::novac($uplata->iznos) . ' je dodato na tvoju studentsku karticu.', 'success'],
    'ceka_potvrdu' => ['Uplata čeka potvrdu', 'Novac će biti na kartici čim banka potvrdi IPS uplatu.', 'warning'],
    'odbijeno' => ['Uplata nije prošla', 'Novac nije dodat na karticu.', 'danger'],
];
[$naslov, $podnaslov, $boja] = $naslovi[$uplata->status];
?>
  <main class="container py-4" style="max-width: 640px;">
    <div class="card">
      <div class="card-body text-center">
        <span class="badge text-bg-<?php echo $boja; ?> mb-2"><?php echo Format::statusTransakcije($uplata->status); ?></span>
        <h1 class="h4"><?php echo $naslov; ?></h1>
        <p class="text-muted"><?php echo $podnaslov; ?></p>

        <div class="text-start">
          <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Datum i vreme</span><span><?php echo date('d.m.Y. \u H:i', strtotime($uplata->kreirano)); ?></span></div>
          <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Način plaćanja</span><span><?php echo Format::nacinPlacanja($uplata->nacin); ?></span></div>
          <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Broj transakcije</span><span><?php echo $brojTransakcije; ?></span></div>
          <?php if ($novoStanje !== null): ?>
            <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Prethodno stanje</span><span><?php echo Format::novac($prethodnoStanje); ?></span></div>
            <div class="d-flex justify-content-between py-2"><span>Novo stanje</span><strong><?php echo Format::novac($novoStanje); ?></strong></div>
          <?php endif; ?>
        </div>

        <a href="pocetna.php" class="btn btn-primary w-100 mt-3">Nazad na početnu</a>
        <?php if ($uplata->status === 'odbijeno'): ?>
          <a href="dopuna.php" class="btn btn-outline-secondary w-100 mt-2">Pokušaj ponovo</a>
        <?php endif; ?>
      </div>
    </div>
  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
