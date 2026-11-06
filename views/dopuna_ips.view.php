<?php
/**
 * PRIKAZ: dopuna kartice – IPS QR uplata (izgled po Figmi – ekran "Dopuna-IPS").
 *
 * QR kod crta js/ips_qr.js u pregledaču iz $ipsQrTekst (biblioteka qrcodejs sa cdnjs);
 * element #ipsQr mora da ima data-tekst.
 * Spisak promenljivih je na vrhu dopuna_ips.php.
 */
$naslovStrane = 'IPS QR uplata';
$aktivnaStrana = 'dopuna.php';
$stilovi = ['css/dopuna.css'];
$skripte = ['https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js', 'js/ips_qr.js'];
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/meni.php';

$redovi = [
    'Primalac' => $podaciZaUplatu['primalac'],
    'Račun primaoca' => $podaciZaUplatu['racun'],
    'Iznos' => $podaciZaUplatu['iznos'],
    'Šifra plaćanja' => $podaciZaUplatu['sifra'],
    'Poziv na broj' => $podaciZaUplatu['poziv_na_broj'],
    'Svrha' => $podaciZaUplatu['svrha'],
];
$koraci = [
    'Otvori mobilno bankarstvo i izaberi opciju „IPS skeniraj"',
    'Skeniraj kod sa ekrana — iznos i podaci se popune sami',
    'Proveri podatke i potvrdi plaćanje u aplikaciji banke',
];
?>
  <main class="app-main app-main-wide">

    <header class="page-header d-flex justify-content-between align-items-start gap-3">
      <div>
        <a href="dopuna.php" class="back-link"><?php echo ikonica('nazad'); ?> Dopuna kartice</a>
        <h1 class="page-title">IPS QR uplata</h1>
        <p class="page-date">Skeniraj kod aplikacijom svoje banke · dopuna <?php echo Format::novac($iznos); ?></p>
      </div>
      <?php require __DIR__ . '/delovi/korisnik.php'; ?>
    </header>

    <?php if ($greska !== ''): ?>
      <div class="alert app-alert alert-danger mb-3 mb-lg-4" role="alert"><?php echo htmlspecialchars($greska); ?></div>
    <?php endif; ?>

    <div class="row g-3 g-lg-4 align-items-start">
      <div class="col-lg-7">
        <section class="panel pay-panel">
          <div class="panel-head">
            <h2>Skeniraj QR kod</h2>
            <span class="tag tag-green">NBS IPS standard</span>
          </div>

          <div class="d-flex flex-column flex-md-row align-items-center align-items-md-start gap-4">
            <div class="qr-box flex-shrink-0">
              <div id="ipsQr" class="qr-code" role="img" aria-label="IPS QR kod za uplatu"
                   data-tekst="<?php echo htmlspecialchars($ipsQrTekst); ?>"></div>
            </div>

            <div class="flex-fill">
              <ol class="ips-steps">
                <?php foreach ($koraci as $i => $korak): ?>
                  <li><span class="step-num"><?php echo $i + 1; ?></span><span><?php echo $korak; ?></span></li>
                <?php endforeach; ?>
              </ol>
              <p class="ips-status d-flex flex-wrap align-items-center gap-2 mb-0">
                <span class="tag tag-orange">Čeka se uplata</span>
                <span>Uplata br. <?php echo (int)$transakcijaId; ?> · osveži stranicu da proveriš da li je stigla</span>
              </p>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-2 gap-lg-3 mt-4">
            <form method="post" action="dopuna_ips.php" class="flex-fill">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
              <input type="hidden" name="akcija" value="simuliraj" />
              <button type="submit" class="btn btn-app btn-app-soft btn-app-lg w-100">Simuliraj prijem uplate (test)</button>
            </form>
            <form method="post" action="dopuna_ips.php" class="pay-change">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
              <input type="hidden" name="akcija" value="promeni" />
              <button type="submit" class="btn btn-app btn-app-outline btn-app-lg w-100">Promeni način plaćanja</button>
            </form>
          </div>
        </section>
      </div>

      <div class="col-lg-5">
        <section class="panel">
          <h2 class="panel-title mb-1">Podaci za uplatu</h2>
          <?php foreach ($redovi as $naziv => $vrednost): ?>
            <div class="summary-row"><span><?php echo $naziv; ?></span><strong><?php echo htmlspecialchars($vrednost); ?></strong></div>
          <?php endforeach; ?>
          <div class="note-box mt-3">
            <?php echo ikonica('info'); ?>
            <p>
              Poziv na broj je broj tvoje studentske kartice — po njemu sistem zna kome da doda novac.
              IPS radi 0–24, svakog dana. Račun primaoca je izmišljen za prototip.
            </p>
          </div>
        </section>
      </div>
    </div>
  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
