<?php
/**
 * PRIKAZ: administratorski pregled (izgled po Figmi – ekran "Admin").
 *
 * Gore četiri kartice sa brojevima za danas, zatim izdati obroci za 7 dana (stubići),
 * danas po menzama i poslednje uplate.
 * Spisak promenljivih je na vrhu admin_pocetna.php.
 * Samo prikaz, bez upita u bazu; tekst iz baze se ispisuje kroz htmlspecialchars().
 */
$naslovStrane = 'Pregled';
$aktivnaStrana = 'admin_pocetna.php';
$stilovi = ['css/admin.css'];
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/admin_meni.php';

// Boja oznake za status uplate
$BOJE_STATUSA = [
    'uspesno' => 'tag-green',
    'ceka_potvrdu' => 'tag-orange',
    'odbijeno' => 'tag-red',
    'isteklo' => 'tag-gray',
];

/** "+12% u odnosu na juče" sa bojom; null = juče nije bilo ničega */
$promena = function (?float $procenat): array {
    if ($procenat === null) {
        return ['tekst' => 'Juče nije bilo podataka', 'klasa' => ''];
    }
    $broj = number_format(abs($procenat), fmod($procenat, 1.0) == 0.0 ? 0 : 1, ',', '.');
    if ($procenat == 0.0) {
        return ['tekst' => 'Isto kao juče', 'klasa' => ''];
    }
    return [
        'tekst' => ($procenat > 0 ? '+' : '−') . $broj . '% u odnosu na juče',
        'klasa' => $procenat > 0 ? 'is-up' : 'is-down',
    ];
};
$uplateTrend = $promena($uplatePromena);
$prodatoTrend = $promena($prodatoPromena);

// Sekcije kojih nema u donjoj traci na telefonu
$ostaleSekcije = [
    ['fajl' => 'admin_smestaj.php',  'naziv' => 'Smeštaj',  'ikonica' => 'kuca',     'boja' => 'tint-blue'],
    ['fajl' => 'admin_studenti.php', 'naziv' => 'Studenti', 'ikonica' => 'studenti', 'boja' => 'tint-green'],
];

// Najviši stubić zauzima celu visinu grafikona; ostali srazmerno
$najviseIzdato = max(1, max(array_column($poslednjihSedam, 'izdato') ?: [0]));
?>
  <main class="app-main app-main-wide">

    <header class="page-header d-flex justify-content-between align-items-start gap-3">
      <div>
        <h1 class="page-title">Pregled</h1>
        <p class="page-date"><?php echo Format::danSaDatumom($danas); ?></p>
      </div>
      <?php require __DIR__ . '/delovi/korisnik.php'; ?>
    </header>

    <!-- KARTICE SA BROJEVIMA ZA DANAS -->
    <div class="row g-3 g-lg-4 mb-3 mb-lg-4">
      <div class="col-6 col-xl-3">
        <div class="stat-card overview-card h-100">
          <span class="tint-box tint-green"><?php echo ikonica('novac'); ?></span>
          <p class="overview-value"><?php echo number_format($uplateDanas['zbir'], 0, ',', '.'); ?> <span>RSD</span></p>
          <p class="overview-label">Uplate danas · <?php echo (int)$uplateDanas['broj']; ?> <?php echo Format::mnozina((int)$uplateDanas['broj'], 'uplata', 'uplate', 'uplata'); ?></p>
          <p class="overview-note <?php echo $uplateTrend['klasa']; ?>"><?php echo $uplateTrend['tekst']; ?></p>
        </div>
      </div>
      <div class="col-6 col-xl-3">
        <div class="stat-card overview-card h-100">
          <span class="tint-box tint-blue"><?php echo ikonica('korpa'); ?></span>
          <p class="overview-value"><?php echo number_format($prodatoDanas, 0, ',', '.'); ?></p>
          <p class="overview-label">Prodatih obroka danas</p>
          <p class="overview-note <?php echo $prodatoTrend['klasa']; ?>"><?php echo $prodatoTrend['tekst']; ?></p>
        </div>
      </div>
      <div class="col-6 col-xl-3">
        <div class="stat-card overview-card h-100">
          <span class="tint-box tint-orange"><?php echo ikonica('qr'); ?></span>
          <p class="overview-value"><?php echo number_format($izdatoDanas, 0, ',', '.'); ?> / <?php echo number_format($mogucihDanas, 0, ',', '.'); ?></p>
          <p class="overview-label">Iskorišćenih bonova</p>
          <p class="overview-note"><?php echo $procenatIzdatih !== null ? (int)$procenatIzdatih . '% izdato' : 'Danas niko nema kupljen obrok'; ?></p>
        </div>
      </div>
      <div class="col-6 col-xl-3">
        <div class="stat-card overview-card h-100">
          <span class="tint-box tint-red"><?php echo ikonica('upozorenje'); ?></span>
          <p class="overview-value"><?php echo number_format($niskoStanje, 0, ',', '.'); ?></p>
          <p class="overview-label">Studenata sa stanjem &lt; <?php echo Format::cena($pragStanja); ?></p>
          <p class="overview-note">Aktivne kartice</p>
        </div>
      </div>
    </div>

    <div class="row g-3 g-lg-4 mb-3 mb-lg-4 align-items-start">
      <!-- IZDATI OBROCI ZA 7 DANA -->
      <div class="col-lg-7">
        <section class="panel">
          <div class="panel-head">
            <h2>Izdati obroci — poslednjih 7 dana</h2>
            <span class="tag tag-blue overview-total">Ukupno <?php echo number_format($ukupnoSedam, 0, ',', '.'); ?></span>
          </div>

          <div class="bar-chart" role="img"
               aria-label="Izdati obroci po danima: <?php echo htmlspecialchars(implode(', ', array_map(fn($d) => $d['dan'] . ' ' . $d['izdato'], $poslednjihSedam))); ?>">
            <?php foreach ($poslednjihSedam as $dan): ?>
              <div class="bar-col<?php echo $dan['danas'] ? ' is-today' : ''; ?>" title="<?php echo Format::datum($dan['datum']); ?>">
                <span class="bar-value"><?php echo number_format($dan['izdato'], 0, ',', '.'); ?></span>
                <span class="bar" style="height: <?php echo round($dan['izdato'] / $najviseIzdato * 100, 1); ?>%"></span>
                <span class="bar-day"><?php echo htmlspecialchars($dan['dan']); ?></span>
              </div>
            <?php endforeach; ?>
          </div>
          <?php if ($ukupnoSedam === 0): ?>
            <p class="message-text mt-3">U poslednjih 7 dana nije izdat nijedan obrok.</p>
          <?php endif; ?>
        </section>
      </div>

      <!-- DANAS PO MENZAMA -->
      <div class="col-lg-5">
        <section class="panel">
          <h2 class="panel-title">Danas po menzama</h2>
          <?php foreach ($poMenzama as $menza): ?>
            <div class="canteen-row">
              <div class="d-flex justify-content-between align-items-baseline gap-3">
                <span class="canteen-name"><?php echo htmlspecialchars($menza['naziv']); ?></span>
                <span class="canteen-count"><?php echo (int)$menza['izdato']; ?> / <?php echo (int)$menza['kapacitet']; ?> obroka</span>
              </div>
              <div class="progress canteen-progress" role="progressbar" aria-label="<?php echo htmlspecialchars($menza['naziv']); ?>"
                   aria-valuenow="<?php echo (int)$menza['izdato']; ?>" aria-valuemin="0" aria-valuemax="<?php echo (int)$menza['kapacitet']; ?>">
                <div class="progress-bar" style="width: <?php echo (int)$menza['procenat']; ?>%"></div>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($poMenzama)): ?>
            <p class="message-text mb-0">U bazi još nema menzi.</p>
          <?php endif; ?>
        </section>
      </div>
    </div>

    <!-- POSLEDNJE UPLATE -->
    <section class="panel">
      <div class="panel-head">
        <h2>
          Poslednje uplate
          <?php if ($brojNaCekanju > 0): ?>
            <span class="tag tag-orange"><?php echo (int)$brojNaCekanju; ?> čeka potvrdu</span>
          <?php endif; ?>
        </h2>
        <a href="admin_uplate.php" class="panel-link">Sve uplate <?php echo ikonica('desno'); ?></a>
      </div>

      <?php if (empty($poslednjeUplate)): ?>
        <p class="message-text mb-0">Još nema uplata.</p>
      <?php else: ?>
        <div class="recent-head d-none d-md-grid" aria-hidden="true">
          <span>Vreme</span><span>Student</span><span>Kartica</span><span>Način</span><span class="text-end">Iznos</span><span>Status</span>
        </div>
        <?php foreach ($poslednjeUplate as $u): ?>
          <?php
            $kada = strtotime($u['kreirano']);
            $refundirana = !empty($u['refundirano']);
            $bojaStatusa = $refundirana ? 'tag-gray' : ($BOJE_STATUSA[$u['status']] ?? 'tag-gray');
          ?>
          <article class="recent-row">
            <span class="recent-time">
              <?php echo date('H:i', $kada); ?>
              <?php if (date('Y-m-d', $kada) !== $danas): ?><span class="recent-date"><?php echo date('d.m.', $kada); ?></span><?php endif; ?>
            </span>
            <span class="recent-student"><?php echo htmlspecialchars($u['ime'] . ' ' . $u['prezime']); ?></span>
            <span class="recent-card d-none d-md-block"><?php echo htmlspecialchars(Format::brojKartice($u['broj_kartice'])); ?></span>
            <span class="recent-method d-none d-md-block"><?php echo Format::nacinPlacanja($u['nacin']); ?></span>
            <span class="recent-amount d-none d-md-block"><?php echo Format::novac($u['iznos']); ?></span>
            <span class="recent-status"><span class="tag ms-0 <?php echo $bojaStatusa; ?>"><?php echo $refundirana ? 'Refundirano' : Format::statusTransakcije($u['status']); ?></span></span>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

    <!-- Na telefonu donja traka nema Smeštaj i Studente, pa su ovde -->
    <nav class="row g-3 mt-0 d-lg-none" aria-label="Ostale sekcije">
      <?php foreach ($ostaleSekcije as $sekcija): ?>
        <div class="col-6">
          <a href="<?php echo $sekcija['fajl']; ?>" class="stat-card quick-link d-flex align-items-center gap-3">
            <span class="tint-box <?php echo $sekcija['boja']; ?>"><?php echo ikonica($sekcija['ikonica']); ?></span>
            <span><?php echo htmlspecialchars($sekcija['naziv']); ?></span>
          </a>
        </div>
      <?php endforeach; ?>
    </nav>

  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
