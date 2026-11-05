<?php
/**
 * PRIKAZ: provera QR bonova na kasi (izgled po Figmi – ekran "Kasir-provera-bonova").
 *
 * Spisak promenljivih je na vrhu kasir_bonovi.php ($admin).
 * Skeniranje QR bona i izdavanje obroka backend dodaje u sledećoj fazi;
 * do tada stranica prikazuje okvir skenera sa oznakom "Uskoro".
 */
$naslovStrane = 'Provera bonova';
$aktivnaStrana = 'kasir_bonovi.php';
$stilovi = ['css/admin.css'];
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/admin_meni.php';
require_once __DIR__ . '/../src/Format.php';
?>
  <main class="app-main">

    <header class="page-header d-flex justify-content-between align-items-start gap-3">
      <div>
        <h1 class="page-title">Provera bonova</h1>
        <p class="page-date"><?php echo Format::danSaDatumom(date('Y-m-d')); ?></p>
      </div>
      <?php require __DIR__ . '/delovi/korisnik.php'; ?>
    </header>

    <div class="row">
      <div class="col-lg-8 col-xl-7">
        <section class="panel mb-3 mb-lg-4">
          <div class="panel-head">
            <h2>Skeniraj QR bon</h2>
            <span class="tag tag-gray">Uskoro</span>
          </div>

          <div class="scanner" aria-hidden="true">
            <span class="scanner-corner tl"></span>
            <span class="scanner-corner tr"></span>
            <span class="scanner-corner bl"></span>
            <span class="scanner-corner br"></span>
            <span class="scanner-line"></span>
            <p class="scanner-text">Usmeri kameru na QR kod sa telefona studenta</p>
          </div>

          <div class="note-box mt-3">
            <?php echo ikonica('info'); ?>
            <p>
              Skeniranje QR bona i izdavanje obroka biće dostupni u sledećoj fazi projekta.
              Tada će se ovde videti i podaci o studentu, broj preostalih obroka i poslednja skeniranja.
            </p>
          </div>
        </section>
      </div>
    </div>

  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
