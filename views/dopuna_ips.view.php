<?php
/**
 * PRIKAZ: dopuna kartice – IPS QR uplata.
 *
 * Privremeni raspored – konačni izgled je ekran "Dopuna-IPS" u Figmi.
 * QR kod crta js/ips_qr.js u pregledaču iz $ipsQrTekst (biblioteka qrcodejs sa cdnjs).
 * Spisak promenljivih je na vrhu dopuna_ips.php.
 */
$naslovStrane = 'IPS QR uplata';
$klasaTela = 'bg-light';
$aktivnaStrana = 'dopuna.php';
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
?>
  <main class="container py-4" style="max-width: 1000px;">
    <a href="dopuna.php" class="small">&larr; Dopuna kartice</a>
    <h1 class="h3 mb-3 mt-1">IPS QR uplata</h1>

    <?php if ($greska !== ''): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($greska); ?></div>
    <?php endif; ?>

    <div class="row g-3">
      <div class="col-lg-6">
        <div class="card text-center">
          <div class="card-body">
            <p class="fw-bold mb-1">Skeniraj QR kod</p>
            <p class="small text-muted">Mobilno bankarstvo → „IPS skeniraj" → proveri podatke i potvrdi</p>
            <div id="ipsQr" class="d-inline-block p-2 bg-white border rounded" data-tekst="<?php echo htmlspecialchars($ipsQrTekst); ?>"></div>
            <p class="mt-3 mb-1"><span class="badge text-bg-warning">Čeka se uplata</span></p>
            <p class="small text-muted">Uplata br. <?php echo (int)$transakcijaId; ?> · stranica se može osvežiti da se proveri da li je uplata stigla</p>

            <form method="post" action="dopuna_ips.php" class="mb-2">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
              <input type="hidden" name="akcija" value="simuliraj" />
              <button type="submit" class="btn btn-primary w-100">Simuliraj prijem uplate (test)</button>
            </form>
            <form method="post" action="dopuna_ips.php">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
              <input type="hidden" name="akcija" value="promeni" />
              <button type="submit" class="btn btn-outline-secondary w-100">Promeni način plaćanja</button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card">
          <div class="card-body">
            <p class="fw-bold">Podaci za uplatu</p>
            <?php foreach ($redovi as $naziv => $vrednost): ?>
              <div class="d-flex justify-content-between border-bottom py-2">
                <span class="text-muted"><?php echo $naziv; ?></span>
                <strong class="text-end"><?php echo htmlspecialchars($vrednost); ?></strong>
              </div>
            <?php endforeach; ?>
            <p class="small text-muted mt-3 mb-0">
              Poziv na broj je broj studentske kartice – po njemu se zna kome se dodaje novac.
              Račun primaoca je izmišljen za projekat.
            </p>
          </div>
        </div>
      </div>
    </div>
  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
