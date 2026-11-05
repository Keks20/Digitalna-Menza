<?php
/**
 * PRIKAZ: Moji obroci – QR bon, preostali obroci u mesecu i poslednja iskorišćenja.
 *
 * Privremeni raspored – konačni izgled je ekran "Moji obroci" u Figmi.
 * QR kod crta js/bon_qr.js iz data-tekst i sam ga osvežava preko data-adresa (biblioteka qrcodejs sa cdnjs).
 * Element #bonQr mora da ima data-tekst, data-adresa i data-osvezi; #bonOdbrojavanje (nije obavezan) prikazuje sekunde do novog koda.
 * Spisak promenljivih je na vrhu obroci.php.
 * Samo prikaz, bez upita u bazu; tekst iz baze se ispisuje kroz htmlspecialchars().
 */
$naslovStrane = 'Moji obroci';
$aktivnaStrana = 'obroci.php';
$skripte = ['https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js', 'js/bon_qr.js'];
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/meni.php';

$nazivIzabranog = '';
foreach ($obroci as $o) {
    if ((int)$o['tip_obroka_id'] === $izabraniTip) {
        $nazivIzabranog = $o['naziv'];
    }
}
?>
  <main class="app-main app-main-wide">

    <header class="page-header d-flex justify-content-between align-items-start gap-3">
      <div>
        <h1 class="page-title">Moji obroci</h1>
        <p class="page-date">QR bon i preostali obroci za <?php echo htmlspecialchars($nazivMeseca); ?></p>
      </div>
      <?php require __DIR__ . '/delovi/korisnik.php'; ?>
    </header>

    <?php if ($poruka): ?>
      <div class="alert app-alert alert-<?php echo $poruka['tip'] === 'success' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($poruka['tekst']); ?></div>
    <?php endif; ?>

    <div class="row g-3 g-lg-4">
      <div class="col-lg-7">
        <section class="panel text-center">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="panel-title mb-0">QR bon za obrok</h2>
            <span class="badge text-bg-success">Važi do <?php echo $vaziDo; ?></span>
          </div>

          <div class="d-flex gap-2 mb-3">
            <?php foreach ($obroci as $o): ?>
              <?php $aktivan = (int)$o['tip_obroka_id'] === $izabraniTip; ?>
              <a href="obroci.php?tip=<?php echo (int)$o['tip_obroka_id']; ?>"
                 class="btn flex-fill <?php echo $aktivan ? 'btn-primary' : 'btn-outline-secondary'; ?>"<?php echo $aktivan ? ' aria-current="page"' : ''; ?>>
                <?php echo htmlspecialchars($o['naziv']); ?>
              </a>
            <?php endforeach; ?>
          </div>

          <?php if ($bonTekst !== null): ?>
            <div id="bonQr" class="d-inline-block p-3 bg-white border rounded"
                 data-tekst="<?php echo htmlspecialchars($bonTekst); ?>"
                 data-adresa="obroci.php?bon=<?php echo (int)$izabraniTip; ?>"
                 data-osvezi="<?php echo (int)$bonOsvezavanje; ?>"></div>
            <p class="fw-bold mt-3 mb-1">Pokaži kod radniku na kasi</p>
            <p class="text-muted mb-1">
              <?php echo htmlspecialchars($nazivIzabranog); ?> · <?php echo htmlspecialchars($student->punoIme()); ?>
              · kartica <?php echo htmlspecialchars($kartica->formatiranBroj()); ?>
            </p>
            <p class="small text-muted mb-0">Kod se osvežava za <span id="bonOdbrojavanje"><?php echo (int)$bonOsvezavanje; ?></span> s — snimak ekrana ne važi</p>
          <?php elseif ($ukupnoKupljeno === 0): ?>
            <p class="fw-bold mb-1">Nemaš kupljene obroke za <?php echo htmlspecialchars($nazivMeseca); ?></p>
            <a href="kupovina.php" class="btn btn-primary mt-2">Kupi obroke</a>
          <?php else: ?>
            <p class="fw-bold mb-1">Nemaš više <?php echo $izabraniTip !== null ? 'ovih obroka' : 'obroka'; ?> za <?php echo htmlspecialchars($nazivMeseca); ?></p>
            <a href="kupovina.php" class="btn btn-primary mt-2">Kupi još obroka</a>
          <?php endif; ?>
        </section>
      </div>

      <div class="col-lg-5">
        <section class="panel mb-3 mb-lg-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="panel-title mb-0">Preostalo u mesecu</h2>
            <span class="badge text-bg-primary"><?php echo $ukupnoPreostalo; ?> od <?php echo $ukupnoKupljeno; ?></span>
          </div>
          <?php foreach ($obroci as $o): ?>
            <div class="mb-3">
              <div class="d-flex justify-content-between">
                <span><?php echo htmlspecialchars($o['naziv']); ?></span>
                <strong><?php echo $o['preostalo']; ?> / <?php echo $o['kupljeno']; ?></strong>
              </div>
              <div class="progress" style="height: 6px;" role="progressbar" aria-label="<?php echo htmlspecialchars($o['naziv']); ?>"
                   aria-valuenow="<?php echo $o['preostalo']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $o['kupljeno']; ?>">
                <div class="progress-bar" style="width: <?php echo $o['kupljeno'] > 0 ? round($o['preostalo'] / $o['kupljeno'] * 100) : 0; ?>%"></div>
              </div>
            </div>
          <?php endforeach; ?>
        </section>

        <section class="panel">
          <h2 class="panel-title">Poslednja iskorišćenja</h2>
          <?php if (empty($iskoriscenja)): ?>
            <p class="text-muted small mb-0">Još nijedan obrok nije iskorišćen.</p>
          <?php endif; ?>
          <?php foreach ($iskoriscenja as $i): ?>
            <div class="d-flex justify-content-between align-items-center border-top py-2">
              <div>
                <div class="fw-semibold"><?php echo htmlspecialchars($i['obrok']); ?> · <?php echo htmlspecialchars($i['menza']); ?></div>
                <div class="small text-muted"><?php echo date('d.m. \u H:i', strtotime($i['iskorisceno'])); ?></div>
              </div>
              <span class="badge text-bg-light">Iskorišćen</span>
            </div>
          <?php endforeach; ?>
        </section>
      </div>
    </div>
  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
