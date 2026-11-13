<?php
/**
 * PRIKAZ: dopuna kartice – iznos i način plaćanja (izgled po Figmi – ekran "Dopuna").
 *
 * Spisak promenljivih i imena polja forme su na vrhu dopuna.php.
 * Samo prikaz, bez upita u bazu; tekst iz baze se ispisuje kroz htmlspecialchars().
 */
$naslovStrane = 'Dopuna kartice';
$aktivnaStrana = 'dopuna.php';
$stilovi = ['css/dopuna.css'];
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/meni.php';

$naciniPlacanja = [
    'platna_kartica' => [
        'naziv' => 'Platnom karticom', 'ikonica' => 'kartica', 'boja' => 'tint-blue', 'oznaka' => '',
        'opis' => 'Visa, Mastercard ili DinaCard — unos podataka kartice na stranici banke',
    ],
    'ips_qr' => [
        'naziv' => 'IPS QR kodom', 'ikonica' => 'qr', 'boja' => 'tint-green', 'oznaka' => 'Instant 0–24',
        'opis' => 'Skeniraš kod aplikacijom svoje banke — bez unosa podataka kartice',
    ],
];
?>
  <main class="app-main app-main-wide">

    <header class="page-header d-flex justify-content-between align-items-start gap-3">
      <div>
        <a href="pocetna.php" class="back-link d-none d-lg-inline-flex"><?php echo ikonica('nazad'); ?> Početna</a>
        <h1 class="page-title">Dopuna kartice</h1>
        <p class="page-date">
          <span class="d-none d-lg-inline">Trenutno stanje:</span><span class="d-lg-none">Stanje:</span>
          <strong class="text-body"><?php echo Format::novac($kartica->stanje); ?></strong>
          <span class="d-none d-lg-inline">· kartica <?php echo htmlspecialchars($kartica->formatiranBroj()); ?></span>
        </p>
      </div>
      <?php require __DIR__ . '/delovi/korisnik.php'; ?>
    </header>

    <?php if ($poruka): ?>
      <div class="alert app-alert alert-<?php echo $poruka['tip'] === 'success' ? 'success' : 'danger'; ?> mb-3 mb-lg-4" role="alert"><?php echo htmlspecialchars($poruka['tekst']); ?></div>
    <?php endif; ?>
    <?php if (!empty($greske)): ?>
      <div class="alert app-alert alert-danger mb-3 mb-lg-4" role="alert">
        <?php foreach ($greske as $g): ?><div><?php echo htmlspecialchars($g); ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" action="dopuna.php" id="formaDopune">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />

      <div class="row g-3 g-lg-4 align-items-start">
        <div class="col-lg-7">
          <section class="panel pay-panel">

            <!-- 1. Iznos -->
            <h2 class="step-title" id="naslovIznos"><span class="step-num">1</span> Iznos dopune</h2>
            <div class="amount-grid" role="radiogroup" aria-labelledby="naslovIznos">
              <?php foreach ($ponudjeniIznosi as $ponudjen): ?>
                <input type="radio" class="btn-check" name="iznos" id="iznos-<?php echo $ponudjen; ?>" value="<?php echo $ponudjen; ?>"
                       <?php echo $forma['iznos'] === $ponudjen ? 'checked' : ''; ?> />
                <label class="amount-btn" for="iznos-<?php echo $ponudjen; ?>"><?php echo number_format($ponudjen, 0, ',', '.'); ?></label>
              <?php endforeach; ?>
              <!-- "Drugi iznos" se bira kad student klikne u polje ispod -->
              <input type="radio" class="btn-check" name="iznos" id="iznos-drugi" value="drugi"
                     <?php echo $forma['iznos'] === 'drugi' ? 'checked' : ''; ?> />
            </div>

            <label for="drugiIznos" class="form-label mt-3">Drugi iznos (RSD)</label>
            <div class="field mb-0">
              <?php echo ikonica('novac', 'field-icon'); ?>
              <input type="text" inputmode="numeric" class="form-control" id="drugiIznos" name="drugi_iznos"
                     value="<?php echo htmlspecialchars($forma['drugi_iznos']); ?>"
                     placeholder="od <?php echo number_format($najmanjiIznos, 0, ',', '.'); ?> do <?php echo number_format($najveciIznos, 0, ',', '.'); ?> RSD" />
            </div>

            <!-- 2. Način plaćanja -->
            <h2 class="step-title mt-4" id="naslovNacin"><span class="step-num">2</span> Način plaćanja</h2>
            <div class="d-flex flex-column gap-3" role="radiogroup" aria-labelledby="naslovNacin">
              <?php foreach ($naciniPlacanja as $vrednost => $n): ?>
                <label class="pay-option d-flex align-items-center" for="nacin-<?php echo $vrednost; ?>">
                  <span class="tint-box tint-box-lg <?php echo $n['boja']; ?>"><?php echo ikonica($n['ikonica']); ?></span>
                  <span class="flex-fill">
                    <span class="pay-name">
                      <?php echo $n['naziv']; ?>
                      <?php if ($n['oznaka'] !== ''): ?><span class="tag tag-green"><?php echo $n['oznaka']; ?></span><?php endif; ?>
                    </span>
                    <span class="pay-desc"><?php echo $n['opis']; ?></span>
                  </span>
                  <input class="form-check-input pay-radio" type="radio" name="nacin" id="nacin-<?php echo $vrednost; ?>"
                         value="<?php echo $vrednost; ?>" data-naziv="<?php echo $n['naziv']; ?>"
                         <?php echo $forma['nacin'] === $vrednost ? 'checked' : ''; ?> />
                </label>
              <?php endforeach; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-app btn-app-lg w-100 mt-4">
              Nastavi na plaćanje <?php echo ikonica('napred'); ?>
            </button>
          </section>
        </div>

        <div class="col-lg-5">
          <section class="panel mb-3 mb-lg-4">
            <h2 class="panel-title mb-1">Pregled uplate</h2>
            <div class="summary-row"><span>Iznos dopune</span><strong id="pregledIznos">–</strong></div>
            <div class="summary-row"><span>Način plaćanja</span><strong id="pregledNacin">–</strong></div>
            <div class="summary-row"><span>Provizija</span><strong>0,00 RSD</strong></div>
            <div class="summary-total">
              <span>Novo stanje <span class="d-none d-lg-inline">posle uplate</span></span>
              <span class="amount amount-green" id="pregledStanje">–</span>
            </div>
            <div class="note-box mt-3">
              <?php echo ikonica('stit'); ?>
              <p>Podaci tvoje kartice se ne čuvaju na sajtu. Novac je na kartici čim banka potvrdi uplatu.</p>
            </div>
          </section>

          <section class="panel">
            <div class="panel-head mb-2">
              <h2>Poslednje uplate</h2>
              <?php if (file_exists(__DIR__ . '/../istorija.php')): ?>
                <a href="istorija.php" class="panel-link">Istorija <?php echo ikonica('desno'); ?></a>
              <?php endif; ?>
            </div>
            <?php if (empty($poslednjeUplate)): ?>
              <p class="message-text mb-0">Još nema uplata.</p>
            <?php endif; ?>
            <?php foreach ($poslednjeUplate as $u): ?>
              <?php
                $refundirana = !empty($u['refundirano']);
                $uspesna = $u['status'] === 'uspesno' && !$refundirana;
              ?>
              <div class="recent-payment d-flex align-items-center gap-3">
                <span class="tint-box <?php echo $uspesna ? 'tint-green' : 'tint-orange'; ?>"><?php echo ikonica('uplata'); ?></span>
                <div class="flex-fill">
                  <div class="recent-amount<?php echo $uspesna ? '' : ' text-muted'; ?>"><?php echo Format::novac($u['iznos'], true); ?></div>
                  <div class="recent-meta"><?php echo Format::datum($u['kreirano']); ?> · <?php echo Format::nacinPlacanja($u['nacin']); ?></div>
                </div>
                <?php if ($refundirana): ?>
                  <span class="tag tag-gray">Refundirano</span>
                <?php elseif (!$uspesna): ?>
                  <span class="tag <?php echo $u['status'] === 'odbijeno' ? 'tag-red' : 'tag-orange'; ?>"><?php echo Format::statusTransakcije($u['status']); ?></span>
                <?php elseif (isset($refundabilne[$u['id']])): ?>
                  <a href="dopuna_uspesno.php?id=<?php echo (int)$u['id']; ?>" class="panel-link"
                     title="Refundacija je moguća do <?php echo date('H:i', strtotime($refundabilne[$u['id']])); ?>">Refundiraj</a>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </section>
        </div>
      </div>
    </form>
  </main>

  <script>
    // Pregled uplate se osvežava dok student bira iznos i način (samo prikaz – server sve ponovo proverava)
    (function () {
      const stanje = <?php echo json_encode((float)$kartica->stanje); ?>;
      const forma = document.getElementById('formaDopune');
      const drugi = document.getElementById('drugiIznos');
      const novac = (x) => x.toLocaleString('sr-RS', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' RSD';

      function osvezi() {
        const izabran = forma.querySelector('input[name="iznos"]:checked');
        let iznos = 0;
        if (izabran && izabran.value === 'drugi') {
          iznos = parseInt(drugi.value.replace(/\D/g, ''), 10) || 0;
        } else if (izabran) {
          iznos = parseInt(izabran.value, 10);
        }
        const nacin = forma.querySelector('input[name="nacin"]:checked');
        document.getElementById('pregledIznos').textContent = iznos ? novac(iznos) : '–';
        document.getElementById('pregledNacin').textContent = nacin ? nacin.dataset.naziv : '–';
        document.getElementById('pregledStanje').textContent = iznos ? novac(stanje + iznos) : '–';
      }

      // Kucanje u "Drugi iznos" bira tu opciju (gornja dugmad se odznače)
      ['focus', 'input'].forEach((dogadjaj) => drugi.addEventListener(dogadjaj, () => {
        document.getElementById('iznos-drugi').checked = true;
        osvezi();
      }));
      // Klik na ponuđeni iznos briše upisani drugi iznos
      forma.querySelectorAll('input[name="iznos"]:not(#iznos-drugi)').forEach((dugme) => dugme.addEventListener('change', () => {
        drugi.value = '';
      }));
      forma.addEventListener('change', osvezi);
      osvezi();
    })();
  </script>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
