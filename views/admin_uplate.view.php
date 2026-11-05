<?php
/**
 * PRIKAZ: uplate i obaveštenja (izgled po Figmi – ekran "Admin-uplate").
 *
 * Levo: uplate studenata – filter po statusu, pretraga i potvrda IPS uplata koje čekaju.
 * Desno: novo obaveštenje / izmena i lista objavljenih.
 * Spisak promenljivih i imena polja forme su na vrhu admin_uplate.php.
 * Samo prikaz, bez upita u bazu; tekst iz baze se ispisuje kroz htmlspecialchars().
 */
$naslovStrane = 'Uplate i obaveštenja';
$aktivnaStrana = 'admin_uplate.php';
$stilovi = ['css/jelovnik.css', 'css/admin_uplate.css'];   // .btn-choice (dugmad za izbor) je iz jelovnik.css
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/admin_meni.php';
require_once __DIR__ . '/delovi/obavestenja_izgled.php';

$izmena = $forma['id'] !== null;

// Boja oznake za status uplate
$BOJE_STATUSA = [
    'uspesno' => 'tag-green',
    'ceka_potvrdu' => 'tag-orange',
    'odbijeno' => 'tag-red',
];

// Kratak naziv načina plaćanja za usku kolonu
$KRATAK_NACIN = [
    'platna_kartica' => 'Kartica',
    'ips_qr' => 'IPS QR',
];
?>
  <main class="app-main app-main-wide">

    <!-- ZAGLAVLJE -->
    <header class="page-header d-flex justify-content-between align-items-start gap-3">
      <div>
        <h1 class="page-title">Uplate i obaveštenja</h1>
        <p class="page-date">Pregled uplata studenata i poruke za aplikaciju</p>
      </div>
      <?php require __DIR__ . '/delovi/korisnik.php'; ?>
    </header>

    <?php if ($poruka): ?>
      <div class="alert app-alert alert-<?php echo $poruka['tip'] === 'success' ? 'success' : 'danger'; ?> mb-3 mb-lg-4" role="alert">
        <?php echo htmlspecialchars($poruka['tekst']); ?>
      </div>
    <?php endif; ?>
    <?php if (!empty($greske)): ?>
      <div class="alert app-alert alert-danger mb-3 mb-lg-4" role="alert">
        <?php foreach ($greske as $greska): ?>
          <div><?php echo htmlspecialchars($greska); ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="row g-3 g-lg-4 align-items-start">

      <!-- DESNO NA RAČUNARU: obaveštenja (na telefonu prva, jer rade) -->
      <div class="col-lg-5 order-lg-2">
        <section class="panel mb-3 mb-lg-4" id="forma-obavestenja">
          <h2 class="panel-title"><?php echo $izmena ? 'Izmena obaveštenja' : 'Novo obaveštenje'; ?></h2>

          <form method="post" action="admin_uplate.php" class="notice-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
            <input type="hidden" name="akcija" value="<?php echo $izmena ? 'izmeni' : 'objavi'; ?>" />
            <?php if ($izmena): ?>
              <input type="hidden" name="id" value="<?php echo (int)$forma['id']; ?>" />
            <?php endif; ?>

            <label for="naslov" class="form-label">Naslov</label>
            <input type="text" class="form-control app-input mb-3" id="naslov" name="naslov" maxlength="150"
                   value="<?php echo htmlspecialchars($forma['naslov']); ?>" placeholder="npr. Menza zatvorena u subotu" />

            <label for="tekst" class="form-label">Tekst poruke</label>
            <textarea class="form-control app-input app-textarea mb-3" id="tekst" name="tekst" rows="4" maxlength="2000"
                      placeholder="Šta studenti treba da znaju?"><?php echo htmlspecialchars($forma['tekst']); ?></textarea>

            <p class="form-label mb-2" id="komeOznaka">Kome se šalje</p>
            <div class="d-flex flex-wrap gap-2 mb-3" role="radiogroup" aria-labelledby="komeOznaka">
              <?php foreach ($NAZIVI_GRUPA as $vrednost => $naziv): ?>
                <input type="radio" class="btn-check" name="ciljna_grupa" id="grupa-<?php echo $vrednost; ?>"
                       value="<?php echo $vrednost; ?>" autocomplete="off"
                       <?php echo $forma['ciljna_grupa'] === $vrednost ? 'checked' : ''; ?> />
                <label class="btn-choice" for="grupa-<?php echo $vrednost; ?>"><?php echo $naziv; ?></label>
              <?php endforeach; ?>
            </div>

            <!-- Izbor menze / doma: vidi se samo kad je izabrana "Jedna menza" / "Jedan dom" -->
            <div class="target-pick target-menza mb-3">
              <label for="menzaId" class="form-label">Menza</label>
              <select class="form-select app-input" id="menzaId" name="menza_id">
                <?php foreach ($menze as $menza): ?>
                  <option value="<?php echo (int)$menza['id']; ?>" <?php echo (int)$forma['menza_id'] === (int)$menza['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($menza['naziv']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="target-pick target-dom mb-3">
              <label for="dom" class="form-label">Dom</label>
              <?php if (empty($domovi)): ?>
                <p class="message-text mb-0">U bazi još nema domova.</p>
              <?php else: ?>
                <select class="form-select app-input" id="dom" name="dom">
                  <?php foreach ($domovi as $dom): ?>
                    <option value="<?php echo htmlspecialchars($dom); ?>" <?php echo $forma['dom'] === $dom ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($dom); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              <?php endif; ?>
            </div>

            <div class="d-flex flex-wrap gap-2 pt-1">
              <button type="submit" class="btn btn-primary btn-app flex-fill">
                <?php echo ikonica($izmena ? 'sacuvaj' : 'posalji'); ?>
                <?php echo $izmena ? 'Sačuvaj izmene' : 'Objavi obaveštenje'; ?>
              </button>
              <?php if ($izmena): ?>
                <a href="admin_uplate.php" class="btn btn-app btn-app-outline">Odustani</a>
              <?php endif; ?>
            </div>
          </form>
        </section>

        <section class="panel">
          <h2 class="panel-title">Objavljeno</h2>

          <?php foreach ($obavestenja as $o): ?>
            <?php
              $izgled = izgledObavestenja($o);
              $uIzmeni = $izmena && (int)$o['id'] === (int)$forma['id'];
            ?>
            <article class="message notice-item d-flex<?php echo $uIzmeni ? ' is-editing' : ''; ?>">
              <span class="tint-box <?php echo $izgled['boja']; ?>"><?php echo ikonica($izgled['ikonica']); ?></span>
              <div class="flex-fill min-w-0">
                <h3 class="message-title"><?php echo htmlspecialchars($o['naslov']); ?></h3>
                <p class="message-text notice-text"><?php echo htmlspecialchars($o['tekst']); ?></p>
                <p class="message-meta">
                  <?php echo Format::datum($o['kreirano']); ?> · <?php echo htmlspecialchars($izgled['kome']); ?>
                  <?php if (!empty($o['autor'])): ?> · <?php echo htmlspecialchars($o['autor']); ?><?php endif; ?>
                </p>
              </div>
              <div class="notice-actions d-flex align-items-start gap-1">
                <a href="admin_uplate.php?izmeni=<?php echo (int)$o['id']; ?>#forma-obavestenja" class="icon-btn"
                   title="Izmeni" aria-label="Izmeni: <?php echo htmlspecialchars($o['naslov']); ?>"><?php echo ikonica('olovka'); ?></a>
                <form method="post" action="admin_uplate.php" onsubmit="return confirm('Obrisati ovo obaveštenje? Studenti ga više neće videti.');">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
                  <input type="hidden" name="akcija" value="obrisi" />
                  <input type="hidden" name="id" value="<?php echo (int)$o['id']; ?>" />
                  <button type="submit" class="icon-btn icon-btn-danger" title="Obriši"
                          aria-label="Obriši: <?php echo htmlspecialchars($o['naslov']); ?>"><?php echo ikonica('kanta'); ?></button>
                </form>
              </div>
            </article>
          <?php endforeach; ?>

          <?php if (empty($obavestenja)): ?>
            <p class="message-text mb-0">Još nema objavljenih obaveštenja.</p>
          <?php endif; ?>
        </section>
      </div>

      <!-- LEVO NA RAČUNARU: uplate studenata -->
      <div class="col-lg-7 order-lg-1">
        <section class="panel">
          <div class="panel-head">
            <h2>Uplate studenata</h2>
            <?php if ($brojNaCekanju > 0): ?>
              <span class="tag tag-orange"><?php echo $brojNaCekanju; ?> čeka potvrdu</span>
            <?php endif; ?>
          </div>

          <!-- Filter po statusu i pretraga -->
          <div class="payments-tools d-flex flex-wrap align-items-center gap-2 mb-3">
            <nav class="d-flex flex-wrap gap-2" aria-label="Filter uplata po statusu">
              <?php foreach ($FILTERI as $vrednost => $naziv): ?>
                <?php $aktivan = $filterStatus === $vrednost; ?>
                <a href="<?php echo htmlspecialchars(adminUplateUrl($vrednost, $pretraga)); ?>"
                   class="btn-choice btn-choice-sm<?php echo $aktivan ? ' active' : ''; ?>"<?php echo $aktivan ? ' aria-current="page"' : ''; ?>>
                  <?php echo $naziv; ?>
                  <?php if ($vrednost === 'ceka_potvrdu' && $brojNaCekanju > 0): ?>
                    <span class="choice-count"><?php echo $brojNaCekanju; ?></span>
                  <?php endif; ?>
                </a>
              <?php endforeach; ?>
            </nav>
            <form method="get" action="admin_uplate.php" class="payments-search ms-lg-auto" role="search">
              <?php if ($filterStatus !== ''): ?>
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($filterStatus); ?>" />
              <?php endif; ?>
              <?php echo ikonica('pretraga', 'search-icon'); ?>
              <input type="search" class="form-control app-input" name="q" value="<?php echo htmlspecialchars($pretraga); ?>"
                     placeholder="Ime, kartica ili broj transakcije" aria-label="Pretraga uplata" />
            </form>
          </div>

          <?php if (empty($uplate)): ?>
            <div class="empty-state text-center">
              <span class="tint-box tint-green mx-auto"><?php echo ikonica('novac'); ?></span>
              <?php if ($filterStatus === '' && $pretraga === ''): ?>
                <p class="empty-title">Još nema uplata</p>
                <p class="empty-text">Uplate se pojavljuju ovde čim student dopuni karticu.</p>
              <?php else: ?>
                <p class="empty-title">Nema uplata za izabrani filter</p>
                <p class="empty-text">Probaj drugi status ili obriši tekst iz pretrage.</p>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <div class="payments-head d-none d-md-grid" aria-hidden="true">
              <span>Vreme</span><span>Student</span><span>Način</span><span class="text-end">Iznos</span><span>Status</span>
            </div>

            <?php foreach ($uplate as $u): ?>
              <?php
                $kada = strtotime($u['kreirano']);
                $bojaStatusa = $BOJE_STATUSA[$u['status']] ?? 'tag-gray';
              ?>
              <article class="payment-row<?php echo $u['status'] === 'ceka_potvrdu' ? ' is-pending' : ''; ?>"
                       title="<?php echo Format::brojTransakcije((int)$u['id'], $u['kreirano']); ?>">
                <div class="pay-time">
                  <span class="pay-clock"><?php echo date('H:i', $kada); ?></span>
                  <?php if (date('Y-m-d', $kada) !== date('Y-m-d')): ?>
                    <span class="pay-date"><?php echo date('d.m.', $kada); ?></span>
                  <?php endif; ?>
                  <span class="d-md-none"> · <?php echo $KRATAK_NACIN[$u['nacin']] ?? Format::nacinPlacanja($u['nacin']); ?></span>
                </div>
                <div class="pay-student min-w-0">
                  <p class="pay-name"><?php echo htmlspecialchars($u['ime'] . ' ' . $u['prezime']); ?></p>
                  <p class="pay-sub"><?php echo htmlspecialchars(Format::brojKartice($u['broj_kartice'])); ?></p>
                </div>
                <div class="pay-method d-none d-md-block"><?php echo $KRATAK_NACIN[$u['nacin']] ?? Format::nacinPlacanja($u['nacin']); ?></div>
                <div class="pay-amount"><?php echo Format::novac($u['iznos']); ?></div>
                <div class="pay-status"><span class="tag ms-0 <?php echo $bojaStatusa; ?>"><?php echo Format::statusTransakcije($u['status']); ?></span></div>

                <?php if ($u['status'] === 'ceka_potvrdu'): ?>
                  <!-- IPS uplata čeka: admin potvrđuje kad novac stigne na račun -->
                  <div class="pay-actions d-flex flex-wrap align-items-center gap-2">
                    <span class="pay-tx"><?php echo Format::brojTransakcije((int)$u['id'], $u['kreirano']); ?></span>
                    <form method="post" action="admin_uplate.php">
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
                      <input type="hidden" name="akcija" value="potvrdi" />
                      <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>" />
                      <input type="hidden" name="filter_status" value="<?php echo htmlspecialchars($filterStatus); ?>" />
                      <input type="hidden" name="filter_q" value="<?php echo htmlspecialchars($pretraga); ?>" />
                      <button type="submit" class="btn-pay btn-pay-ok"><?php echo ikonica('kvacica'); ?> Potvrdi uplatu</button>
                    </form>
                    <form method="post" action="admin_uplate.php" onsubmit="return confirm('Odbiti ovu uplatu? Novac neće biti dodat na karticu.');">
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
                      <input type="hidden" name="akcija" value="odbij" />
                      <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>" />
                      <input type="hidden" name="filter_status" value="<?php echo htmlspecialchars($filterStatus); ?>" />
                      <input type="hidden" name="filter_q" value="<?php echo htmlspecialchars($pretraga); ?>" />
                      <button type="submit" class="btn-pay btn-pay-no">Odbij</button>
                    </form>
                  </div>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>

          <p class="payments-foot">
            <?php if (count($uplate) >= BROJ_UPLATA): ?>
              Prikazano je poslednjih <?php echo BROJ_UPLATA; ?> uplata – za starije koristi pretragu.<br />
            <?php endif; ?>
            Danas: <strong><?php echo $uplateDanas['broj']; ?> uspešnih uplata</strong> · <?php echo Format::novac($uplateDanas['zbir']); ?>
          </p>
        </section>
      </div>
    </div>

  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
