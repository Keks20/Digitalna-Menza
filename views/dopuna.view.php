<?php
/**
 * PRIKAZ: dopuna kartice – iznos i način plaćanja.
 *
 * Privremeni raspored – konačni izgled je ekran "Dopuna" u Figmi.
 * Spisak promenljivih i imena polja forme su na vrhu dopuna.php.
 * Samo prikaz, bez upita u bazu; tekst iz baze se ispisuje kroz htmlspecialchars().
 */
$naslovStrane = 'Dopuna kartice';
$klasaTela = 'bg-light';
$aktivnaStrana = 'dopuna.php';
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/meni.php';
?>
  <main class="container py-4" style="max-width: 1000px;">
    <h1 class="h3 mb-1">Dopuna kartice</h1>
    <p class="text-muted">
      Trenutno stanje: <strong><?php echo Format::novac($kartica->stanje); ?></strong>
      · kartica <?php echo htmlspecialchars($kartica->formatiranBroj()); ?>
    </p>

    <?php if ($poruka): ?>
      <div class="alert alert-<?php echo $poruka['tip'] === 'success' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($poruka['tekst']); ?></div>
    <?php endif; ?>
    <?php if (!empty($greske)): ?>
      <div class="alert alert-danger"><?php foreach ($greske as $g): ?><div><?php echo htmlspecialchars($g); ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <form method="post" action="dopuna.php" id="formaDopune">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
      <div class="row g-3">
        <div class="col-lg-7">
          <div class="card mb-3">
            <div class="card-body">
              <p class="fw-bold">1. Iznos dopune</p>
              <div class="d-flex flex-wrap gap-2 mb-3">
                <?php foreach ($ponudjeniIznosi as $ponudjen): ?>
                  <input type="radio" class="btn-check" name="iznos" id="iznos-<?php echo $ponudjen; ?>" value="<?php echo $ponudjen; ?>"
                         data-iznos="<?php echo $ponudjen; ?>" <?php echo $forma['iznos'] === $ponudjen ? 'checked' : ''; ?> />
                  <label class="btn btn-outline-primary" for="iznos-<?php echo $ponudjen; ?>"><?php echo number_format($ponudjen, 0, ',', '.'); ?></label>
                <?php endforeach; ?>
                <input type="radio" class="btn-check" name="iznos" id="iznos-drugi" value="drugi" <?php echo $forma['iznos'] === 'drugi' ? 'checked' : ''; ?> />
                <label class="btn btn-outline-primary" for="iznos-drugi">Drugi iznos</label>
              </div>
              <label for="drugiIznos" class="form-label small fw-semibold">
                Drugi iznos (RSD, od <?php echo number_format($najmanjiIznos, 0, ',', '.'); ?> do <?php echo number_format($najveciIznos, 0, ',', '.'); ?>)
              </label>
              <input type="text" inputmode="numeric" class="form-control" id="drugiIznos" name="drugi_iznos"
                     value="<?php echo htmlspecialchars($forma['drugi_iznos']); ?>" placeholder="npr. 1500" />
            </div>
          </div>

          <div class="card">
            <div class="card-body">
              <p class="fw-bold">2. Način plaćanja</p>
              <div class="form-check border rounded p-3 ps-5 mb-2">
                <input class="form-check-input" type="radio" name="nacin" id="nacin-kartica" value="platna_kartica"
                       <?php echo $forma['nacin'] === 'platna_kartica' ? 'checked' : ''; ?> />
                <label class="form-check-label" for="nacin-kartica">
                  <strong>Platnom karticom</strong><br />
                  <span class="small text-muted">Visa, Mastercard ili DinaCard</span>
                </label>
              </div>
              <div class="form-check border rounded p-3 ps-5">
                <input class="form-check-input" type="radio" name="nacin" id="nacin-ips" value="ips_qr"
                       <?php echo $forma['nacin'] === 'ips_qr' ? 'checked' : ''; ?> />
                <label class="form-check-label" for="nacin-ips">
                  <strong>IPS QR kodom</strong> <span class="badge text-bg-success">Instant 0–24</span><br />
                  <span class="small text-muted">Skeniraš kod aplikacijom svoje banke – bez unosa podataka kartice</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="card mb-3">
            <div class="card-body">
              <p class="fw-bold">Pregled uplate</p>
              <div class="d-flex justify-content-between"><span class="text-muted">Iznos dopune</span><strong id="pregledIznos">–</strong></div>
              <div class="d-flex justify-content-between"><span class="text-muted">Provizija</span><span>0,00 RSD</span></div>
              <hr />
              <div class="d-flex justify-content-between"><span>Novo stanje posle uplate</span><strong id="pregledStanje">–</strong></div>
              <button type="submit" class="btn btn-primary w-100 mt-3">Nastavi na plaćanje</button>
              <p class="small text-muted mt-2 mb-0">Podaci platne kartice se ne čuvaju na sajtu.</p>
            </div>
          </div>

          <div class="card">
            <div class="card-body">
              <div class="d-flex justify-content-between">
                <p class="fw-bold">Poslednje uplate</p>
                <?php if (file_exists(__DIR__ . '/../istorija.php')): ?><a href="istorija.php" class="small">Istorija</a><?php endif; ?>
              </div>
              <?php if (empty($poslednjeUplate)): ?>
                <p class="text-muted small mb-0">Još nema uplata.</p>
              <?php endif; ?>
              <?php foreach ($poslednjeUplate as $u): ?>
                <div class="d-flex justify-content-between small mb-1">
                  <span><?php echo Format::datum($u['kreirano']); ?> · <?php echo Format::nacinPlacanja($u['nacin']); ?></span>
                  <span class="<?php echo $u['status'] === 'uspesno' ? 'text-success fw-semibold' : 'text-muted'; ?>">
                    <?php echo Format::novac($u['iznos'], true); ?>
                    <?php if ($u['status'] !== 'uspesno'): ?>(<?php echo Format::statusTransakcije($u['status']); ?>)<?php endif; ?>
                  </span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </form>
  </main>

  <script>
    // Pregled uplate se osvežava dok student bira iznos (samo prikaz – server sve ponovo proverava)
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
        document.getElementById('pregledIznos').textContent = iznos ? novac(iznos) : '–';
        document.getElementById('pregledStanje').textContent = iznos ? novac(stanje + iznos) : '–';
      }

      drugi.addEventListener('focus', () => { document.getElementById('iznos-drugi').checked = true; osvezi(); });
      forma.addEventListener('input', osvezi);
      forma.addEventListener('change', osvezi);
      osvezi();
    })();
  </script>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
