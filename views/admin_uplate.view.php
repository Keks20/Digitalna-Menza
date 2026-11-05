<?php
/**
 * PRIKAZ: uplate i obaveštenja (izgled po Figmi – ekran "Admin-uplate").
 *
 * Levo: uplate studenata – backend ih dodaje uz dopunu kartice, do tada okvir sa oznakom "Uskoro".
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
            <span class="tag tag-gray">Uskoro</span>
          </div>

          <div class="payments-head d-none d-md-grid" aria-hidden="true">
            <span>Vreme</span><span>Student</span><span>Način</span><span class="text-end">Iznos</span><span>Status</span>
          </div>

          <div class="empty-state text-center">
            <span class="tint-box tint-green mx-auto"><?php echo ikonica('novac'); ?></span>
            <p class="empty-title">Pregled uplata stiže uz dopunu kartice</p>
            <p class="empty-text">
              Ovde će biti današnje uplate studenata, pretraga po imenu ili broju kartice
              i potvrda IPS uplata koje čekaju.
            </p>
          </div>
        </section>
      </div>
    </div>

  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
