<?php
/**
 * PRIKAZ: administratorski pregled (izgled po Figmi – ekran "Admin").
 *
 * Spisak promenljivih je na vrhu admin_pocetna.php ($admin, $danas).
 * Statistika iz Figme (uplate danas, prodati/izdati obroci, po menzama, poslednje uplate)
 * se prikazuje kad je backend doda; do tada stranica nudi brz pristup sekcijama.
 */
$naslovStrane = 'Pregled';
$aktivnaStrana = 'admin_pocetna.php';
$stilovi = ['css/admin.css'];
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/admin_meni.php';

// Sekcije administracije (isti fajlovi kao u meniju)
$sekcije = [
    ['fajl' => 'admin_jelovnik.php', 'naziv' => 'Jelovnik i cene',      'opis' => 'Unos jelovnika po danu i menzi, nacrt i objava, cene obroka.', 'ikonica' => 'kalendar', 'boja' => 'tint-blue'],
    ['fajl' => 'kasir_bonovi.php',   'naziv' => 'Provera bonova',       'opis' => 'Provera QR bona studenta i izdavanje obroka na kasi.',          'ikonica' => 'qr',       'boja' => 'tint-orange'],
    ['fajl' => 'admin_uplate.php',   'naziv' => 'Uplate i obaveštenja', 'opis' => 'Pregled uplata i slanje obaveštenja studentima.',            'ikonica' => 'novac',    'boja' => 'tint-green'],
    ['fajl' => 'admin_smestaj.php',  'naziv' => 'Smeštaj',              'opis' => 'Ugovori o smeštaju i mesečna zaduženja za stanarinu.',          'ikonica' => 'kuca',     'boja' => 'tint-blue'],
    ['fajl' => 'admin_studenti.php', 'naziv' => 'Studenti',             'opis' => 'Pregled studenata, kartica i stanja.',                          'ikonica' => 'studenti', 'boja' => 'tint-green'],
];
?>
  <main class="app-main app-main-wide">

    <header class="page-header d-flex justify-content-between align-items-start gap-3">
      <div>
        <h1 class="page-title">Pregled</h1>
        <p class="page-date"><?php echo Format::danSaDatumom($danas); ?></p>
      </div>
      <?php require __DIR__ . '/delovi/korisnik.php'; ?>
    </header>

    <section class="panel welcome-panel d-flex align-items-center gap-3 mb-3 mb-lg-4">
      <span class="avatar avatar-lg"><?php echo htmlspecialchars($admin->inicijali()); ?></span>
      <div>
        <h2 class="welcome-title">Zdravo, <?php echo htmlspecialchars($admin->ime_prezime); ?> <span aria-hidden="true">👋</span></h2>
        <p class="welcome-text">Izaberi sekciju ispod ili iz menija levo.</p>
      </div>
    </section>

    <div class="row g-3 g-lg-4 mb-3 mb-lg-4">
      <?php foreach ($sekcije as $sekcija): ?>
        <?php $postoji = file_exists(__DIR__ . '/../' . $sekcija['fajl']); ?>
        <div class="col-sm-6 col-xl-4">
          <?php if ($postoji): ?>
            <a href="<?php echo $sekcija['fajl']; ?>" class="stat-card section-card h-100 d-block">
          <?php else: ?>
            <div class="stat-card section-card is-disabled h-100" title="Stranica još nije napravljena">
          <?php endif; ?>
              <div class="d-flex justify-content-between align-items-start">
                <span class="tint-box <?php echo $sekcija['boja']; ?>"><?php echo ikonica($sekcija['ikonica']); ?></span>
                <?php if ($postoji): ?>
                  <span class="section-arrow"><?php echo ikonica('napred'); ?></span>
                <?php else: ?>
                  <span class="tag tag-gray">Uskoro</span>
                <?php endif; ?>
              </div>
              <h3 class="section-title"><?php echo htmlspecialchars($sekcija['naziv']); ?></h3>
              <p class="section-text"><?php echo htmlspecialchars($sekcija['opis']); ?></p>
          <?php echo $postoji ? '</a>' : '</div>'; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="note-box">
      <?php echo ikonica('info'); ?>
      <p>
        Ovde će biti dnevna statistika: uplate danas, prodati i izdati obroci po menzama i poslednje uplate.
        Pojaviće se kad se doda u sistem.
      </p>
    </div>

  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
