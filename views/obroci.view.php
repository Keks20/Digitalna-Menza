<?php
/**
 * PRIKAZ: Moji obroci – QR bon, preostali obroci u mesecu i poslednja iskorišćenja
 * (izgled po Figmi – ekran "Moji obroci").
 *
 * QR kod crta js/bon_qr.js iz data-tekst i sam ga osvežava preko data-adresa (biblioteka qrcodejs sa cdnjs).
 * Element #bonQr mora da ima data-tekst, data-adresa i data-osvezi; #bonOdbrojavanje prikazuje sekunde do novog koda.
 * Spisak promenljivih je na vrhu obroci.php.
 * Samo prikaz, bez upita u bazu; tekst iz baze se ispisuje kroz htmlspecialchars().
 */
$naslovStrane = 'Moji obroci';
$aktivnaStrana = 'obroci.php';
$stilovi = ['css/jelovnik.css', 'css/obroci.css'];   // .btn-choice (izbor obroka) je iz jelovnik.css
$skripte = ['https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js', 'js/bon_qr.js'];
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/meni.php';

// Ikonica i boja za svaki obrok (po id-ju iz tabele tipovi_obroka) – isto kao na početnoj
$izgledObroka = [
    1 => ['ikonica' => 'solja', 'boja' => 'tint-orange'],  // Doručak
    2 => ['ikonica' => 'jaje',  'boja' => 'tint-blue'],    // Ručak
    3 => ['ikonica' => 'mesec', 'boja' => 'tint-green'],   // Večera
];
$izgled = fn($tipId) => $izgledObroka[(int)$tipId] ?? ['ikonica' => 'solja', 'boja' => 'tint-blue'];

// "novembar 2026" -> "novembru" (za "Preostalo u novembru")
$mesecU = [
    'januar' => 'januaru', 'februar' => 'februaru', 'mart' => 'martu', 'april' => 'aprilu',
    'maj' => 'maju', 'jun' => 'junu', 'jul' => 'julu', 'avgust' => 'avgustu',
    'septembar' => 'septembru', 'oktobar' => 'oktobru', 'novembar' => 'novembru', 'decembar' => 'decembru',
];
$imeMeseca = strtok($nazivMeseca, ' ');
$uMesecu = $mesecU[$imeMeseca] ?? $imeMeseca;

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
      <div class="alert app-alert alert-<?php echo $poruka['tip'] === 'success' ? 'success' : 'danger'; ?> mb-3 mb-lg-4" role="alert"><?php echo htmlspecialchars($poruka['tekst']); ?></div>
    <?php endif; ?>

    <div class="row g-3 g-lg-4 align-items-start">
      <div class="col-lg-7">
        <section class="panel bon-panel text-center">
          <div class="panel-head">
            <h2>QR bon za obrok</h2>
            <span class="tag tag-green">Važi do <?php echo $vaziDo; ?></span>
          </div>

          <?php if (!empty($obroci)): ?>
            <nav class="d-flex gap-2 gap-lg-3 mb-4" aria-label="Izbor obroka">
              <?php foreach ($obroci as $o): ?>
                <?php $aktivan = (int)$o['tip_obroka_id'] === $izabraniTip; ?>
                <a href="obroci.php?tip=<?php echo (int)$o['tip_obroka_id']; ?>"
                   class="btn-choice flex-fill<?php echo $aktivan ? ' active' : ''; ?><?php echo $o['preostalo'] < 1 ? ' is-empty' : ''; ?>"
                   <?php echo $aktivan ? 'aria-current="page"' : ''; ?>>
                  <?php echo htmlspecialchars($o['naziv']); ?>
                </a>
              <?php endforeach; ?>
            </nav>
          <?php endif; ?>

          <?php if ($bonTekst !== null): ?>
            <div class="qr-box">
              <div id="bonQr" class="bon-qr" role="img" aria-label="QR bon: <?php echo htmlspecialchars($nazivIzabranog); ?>"
                   data-tekst="<?php echo htmlspecialchars($bonTekst); ?>"
                   data-adresa="obroci.php?bon=<?php echo (int)$izabraniTip; ?>"
                   data-osvezi="<?php echo (int)$bonOsvezavanje; ?>"></div>
            </div>
            <p class="bon-title">Pokaži kod radniku na kasi</p>
            <p class="bon-meta">
              <?php echo htmlspecialchars($nazivIzabranog); ?> · <?php echo htmlspecialchars($student->punoIme()); ?>
              · kartica <?php echo htmlspecialchars($kartica->formatiranBroj()); ?>
            </p>
            <p class="bon-refresh">
              <?php echo ikonica('sat'); ?>
              <span>Kod se osvežava za <span id="bonOdbrojavanje"><?php echo (int)$bonOsvezavanje; ?></span> s — snimak ekrana ne važi</span>
            </p>
          <?php else: ?>
            <div class="empty-state">
              <span class="tint-box tint-box-lg tint-blue mx-auto"><?php echo ikonica('qr'); ?></span>
              <?php if ($ukupnoKupljeno === 0): ?>
                <p class="empty-title">Nemaš kupljene obroke za <?php echo htmlspecialchars($nazivMeseca); ?></p>
                <p class="empty-text">Kad kupiš obroke, ovde se pojavljuje QR bon koji pokazuješ na kasi.</p>
                <a href="kupovina.php" class="btn btn-primary btn-app mt-3">Kupi obroke</a>
              <?php else: ?>
                <p class="empty-title">Nemaš više <?php echo $nazivIzabranog !== '' ? 'obroka „' . htmlspecialchars($nazivIzabranog) . '"' : 'obroka'; ?> za <?php echo htmlspecialchars($nazivMeseca); ?></p>
                <p class="empty-text">Izaberi drugi obrok iznad ili dokupi još.</p>
                <a href="kupovina.php" class="btn btn-primary btn-app mt-3">Kupi još obroka</a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </section>
      </div>

      <div class="col-lg-5">
        <section class="panel mb-3 mb-lg-4">
          <div class="panel-head">
            <h2>Preostalo u <?php echo htmlspecialchars($uMesecu); ?></h2>
            <span class="tag tag-blue"><?php echo $ukupnoPreostalo; ?> od <?php echo $ukupnoKupljeno; ?></span>
          </div>
          <?php foreach ($obroci as $o): ?>
            <?php $iz = $izgled($o['tip_obroka_id']); ?>
            <div class="left-row d-flex align-items-center gap-3">
              <span class="tint-box <?php echo $iz['boja']; ?>"><?php echo ikonica($iz['ikonica']); ?></span>
              <div class="flex-fill">
                <div class="d-flex justify-content-between">
                  <span class="left-name"><?php echo htmlspecialchars($o['naziv']); ?></span>
                  <strong><?php echo (int)$o['preostalo']; ?> / <?php echo (int)$o['kupljeno']; ?></strong>
                </div>
                <div class="progress left-progress" role="progressbar" aria-label="<?php echo htmlspecialchars($o['naziv']); ?>"
                     aria-valuenow="<?php echo (int)$o['preostalo']; ?>" aria-valuemin="0" aria-valuemax="<?php echo (int)$o['kupljeno']; ?>">
                  <div class="progress-bar" style="width: <?php echo $o['kupljeno'] > 0 ? round($o['preostalo'] / $o['kupljeno'] * 100) : 0; ?>%"></div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($obroci)): ?>
            <p class="message-text mb-0">Još nema kupljenih obroka.</p>
          <?php endif; ?>
        </section>

        <section class="panel">
          <h2 class="panel-title">Poslednja iskorišćenja</h2>
          <?php if (empty($iskoriscenja)): ?>
            <p class="message-text mb-0">Još nijedan obrok nije iskorišćen.</p>
          <?php endif; ?>
          <?php foreach ($iskoriscenja as $i): ?>
            <?php $iz = $izgled($i['tip_obroka_id']); ?>
            <div class="used-row d-flex align-items-center gap-3">
              <span class="tint-box <?php echo $iz['boja']; ?>"><?php echo ikonica($iz['ikonica']); ?></span>
              <div class="flex-fill min-w-0">
                <div class="used-name"><?php echo htmlspecialchars($i['obrok']); ?> · <?php echo htmlspecialchars($i['menza']); ?></div>
                <div class="used-time"><?php echo date('d.m. \u H:i', strtotime($i['iskorisceno'])); ?></div>
              </div>
              <span class="tag tag-gray">Iskorišćen</span>
            </div>
          <?php endforeach; ?>
        </section>
      </div>
    </div>
  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
