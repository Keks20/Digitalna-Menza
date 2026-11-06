<?php
/**
 * PRIKAZ: provera QR bonova na kasi (izgled po Figmi – ekran "Kasir-provera-bonova").
 *
 * Spisak promenljivih i imena polja su na vrhu kasir_bonovi.php.
 *
 * Kamera: js/kasa_skener.js čita QR iz #kameraVideo (biblioteka jsQR) i šalje formu #formaSkeniranja.
 * Polje #bonTekst služi i za USB čitač QR koda (on "kuca" tekst bona i Enter).
 * Skripta koristi i #dugmeKamera, #kameraStatus i #dugmeIzdaj – ti id-jevi moraju da ostanu.
 */
$naslovStrane = 'Provera bonova';
$aktivnaStrana = 'kasir_bonovi.php';
// .scanner je iz admin.css, .btn-choice iz jelovnik.css
$stilovi = ['css/jelovnik.css', 'css/admin.css', 'css/kasir.css'];
$skripte = ['https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js', 'js/kasa_skener.js'];
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/admin_meni.php';
require_once __DIR__ . '/../src/Format.php';

// Izgled kartice rezultata po ishodu
$IZGLED_ISHODA = [
    'moze' => ['naslov' => 'Bon važi — izdaj obrok', 'boja' => 'green', 'ikonica' => 'kvacica'],
    'izdato' => ['naslov' => 'Obrok je izdat', 'boja' => 'green', 'ikonica' => 'kvacica'],
    'vec_izdato' => ['naslov' => 'Već izdato danas', 'boja' => 'orange', 'ikonica' => 'uzvik'],
    'odbijeno' => ['naslov' => 'Obrok se ne izdaje', 'boja' => 'red', 'ikonica' => 'iks'],
];
// Oznaka i ikonica u spisku poslednjih skeniranja
$OZNAKA_ISHODA = [
    'izdato' => ['naziv' => 'Izdato', 'oznaka' => 'tag-green', 'boja' => 'tint-green', 'ikonica' => 'kvacica'],
    'vec_izdato' => ['naziv' => 'Već izdato', 'oznaka' => 'tag-orange', 'boja' => 'tint-orange', 'ikonica' => 'uzvik'],
    'odbijeno' => ['naziv' => 'Odbijeno', 'oznaka' => 'tag-red', 'boja' => 'tint-red', 'ikonica' => 'iks'],
];
// Ikonica i boja za svaki obrok (po id-ju iz tabele tipovi_obroka) – isto kao kod studenta
$izgledObroka = [
    1 => ['ikonica' => 'solja', 'boja' => 'tint-orange'],  // Doručak
    2 => ['ikonica' => 'jaje',  'boja' => 'tint-blue'],    // Ručak
    3 => ['ikonica' => 'mesec', 'boja' => 'tint-green'],   // Večera
];

$izdatoDanas = array_sum(array_column($danasBrojevi, 'izdato'));
$cekaPotvrdu = ($rezultat['ishod'] ?? '') === 'moze';
?>
  <main class="app-main app-main-wide">

    <header class="page-header d-flex justify-content-between align-items-start gap-3">
      <div>
        <h1 class="page-title">Provera bonova</h1>
        <p class="page-date">
          <?php echo htmlspecialchars($menza['naziv']); ?> ·
          <?php echo $trenutniObrok ? htmlspecialchars($trenutniObrok->naziv) . ' ' . $trenutniObrok->radnoVreme() : 'trenutno se ne služi nijedan obrok'; ?>
        </p>
      </div>
      <?php require __DIR__ . '/delovi/korisnik.php'; ?>
    </header>

    <div class="row g-3 g-lg-4 align-items-start">
      <div class="col-lg-7">
        <section class="panel kasa-panel">
          <div class="panel-head">
            <h2>Skeniraj QR bon</h2>
            <span class="tag tag-gray" id="kameraStatus">Kamera isključena</span>
          </div>

          <!-- Kamera -->
          <div class="scanner">
            <video id="kameraVideo" class="scanner-video" playsinline muted></video>
            <span class="scanner-corner tl"></span>
            <span class="scanner-corner tr"></span>
            <span class="scanner-corner bl"></span>
            <span class="scanner-corner br"></span>
            <span class="scanner-line"></span>
            <p class="scanner-text">Usmeri kameru na QR kod sa telefona studenta</p>
          </div>
          <button type="button" class="btn btn-app btn-app-outline w-100 mt-3" id="dugmeKamera">Uključi kameru</button>

          <!-- Rezultat poslednje provere -->
          <?php if ($rezultat !== null): ?>
            <?php $iz = $IZGLED_ISHODA[$rezultat['ishod']]; ?>
            <div class="scan-result scan-<?php echo $iz['boja']; ?> mt-3" role="status" id="rezultatProvere">
              <div class="d-flex align-items-center gap-3">
                <span class="scan-icon"><?php echo ikonica($iz['ikonica']); ?></span>
                <div>
                  <p class="scan-title"><?php echo $iz['naslov']; ?></p>
                  <p class="scan-time">Skenirano u <?php echo htmlspecialchars($rezultat['skenirano'] ?? ''); ?></p>
                </div>
              </div>

              <?php if (!empty($rezultat['razlog'])): ?>
                <p class="scan-reason"><?php echo htmlspecialchars($rezultat['razlog']); ?></p>
              <?php endif; ?>

              <?php if (isset($rezultat['student'])): ?>
                <div class="scan-rows">
                  <div><span>Student</span><strong><?php echo htmlspecialchars($rezultat['student']); ?></strong></div>
                  <div><span>Kartica</span><strong><?php echo htmlspecialchars(Format::brojKartice($rezultat['broj_kartice'])); ?></strong></div>
                  <div><span>Obrok</span><strong><?php echo htmlspecialchars($rezultat['obrok']); ?></strong></div>
                  <?php if (in_array($rezultat['ishod'], ['moze', 'izdato'], true)): ?>
                    <div>
                      <span><?php echo $rezultat['ishod'] === 'moze' ? 'Preostalo posle ovog' : 'Preostalo'; ?></span>
                      <strong><?php echo (int)$rezultat['preostalo_posle']; ?> / <?php echo (int)$rezultat['kupljeno']; ?></strong>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endif; ?>

              <?php if ($cekaPotvrdu): ?>
                <div class="scan-actions d-flex flex-wrap gap-2 mt-3">
                  <form method="post" action="kasir_bonovi.php" class="flex-fill">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
                    <input type="hidden" name="akcija" value="izdaj" />
                    <button type="submit" class="btn btn-primary btn-app btn-app-lg w-100" id="dugmeIzdaj" autofocus>
                      <?php echo ikonica('kvacica-krug'); ?> Potvrdi izdavanje
                    </button>
                  </form>
                  <form method="post" action="kasir_bonovi.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
                    <input type="hidden" name="akcija" value="odustani" />
                    <button type="submit" class="btn btn-app btn-app-outline btn-app-lg w-100">Odustani</button>
                  </form>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <!-- Tekst bona: popunjava ga kamera ili USB čitač (on "kuca" tekst i Enter) -->
          <form method="post" action="kasir_bonovi.php" id="formaSkeniranja" class="mt-4">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
            <input type="hidden" name="akcija" value="skeniraj" />
            <label for="bonTekst" class="form-label kasa-label">Čitač QR koda</label>
            <div class="field mb-0">
              <?php echo ikonica('qr', 'field-icon'); ?>
              <input type="text" class="form-control" id="bonTekst" name="bon" autocomplete="off"
                     placeholder="Skeniraj čitačem (DM1…)"
                     <?php echo $cekaPotvrdu ? '' : 'autofocus'; ?> />
            </div>
          </form>

          <!-- Rezervni put: broj kartice -->
          <form method="post" action="kasir_bonovi.php" class="mt-3">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
            <input type="hidden" name="akcija" value="kartica" />
            <label for="brojKartice" class="form-label kasa-label">Kamera ne radi? Unesi broj kartice</label>
            <div class="card-check d-flex flex-wrap gap-2">
              <div class="field mb-0 flex-fill">
                <?php echo ikonica('kartica', 'field-icon'); ?>
                <input type="text" inputmode="numeric" class="form-control" id="brojKartice"
                       name="broj_kartice" maxlength="14" placeholder="0012 3456 7890" />
              </div>
              <select class="form-select app-input meal-select" name="tip_obroka" aria-label="Obrok">
                <?php foreach ($tipovi as $tip): ?>
                  <option value="<?php echo (int)$tip->id; ?>" <?php echo $trenutniObrok && $trenutniObrok->id == $tip->id ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($tip->naziv); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn btn-primary btn-app">Proveri</button>
            </div>
          </form>
        </section>
      </div>

      <div class="col-lg-5">
        <section class="panel kasa-panel mb-3 mb-lg-4">
          <div class="panel-head">
            <h2>Danas na ovoj kasi</h2>
            <span class="tag tag-blue"><?php echo $izdatoDanas; ?> <?php echo Format::mnozina($izdatoDanas, 'obrok', 'obroka', 'obroka'); ?></span>
          </div>
          <?php foreach ($tipovi as $tip): ?>
            <?php
              $b = $danasBrojevi[(int)$tip->id] ?? ['izdato' => 0, 'ima_obrok' => 0];
              $izg = $izgledObroka[(int)$tip->id] ?? ['ikonica' => 'solja', 'boja' => 'tint-blue'];
              $sada = $trenutniObrok && $trenutniObrok->id == $tip->id;
            ?>
            <div class="today-row d-flex align-items-center gap-3<?php echo $sada ? ' is-now' : ''; ?>">
              <span class="tint-box <?php echo $izg['boja']; ?>"><?php echo ikonica($izg['ikonica']); ?></span>
              <div class="flex-fill">
                <div class="d-flex justify-content-between align-items-baseline">
                  <span class="today-name">
                    <?php echo htmlspecialchars($tip->naziv); ?>
                    <?php if ($sada): ?><span class="tag tag-green">Sada</span><?php endif; ?>
                  </span>
                  <strong><?php echo (int)$b['izdato']; ?> / <?php echo (int)$b['ima_obrok']; ?></strong>
                </div>
                <div class="progress today-progress" role="progressbar" aria-label="<?php echo htmlspecialchars($tip->naziv); ?>"
                     aria-valuenow="<?php echo (int)$b['izdato']; ?>" aria-valuemin="0" aria-valuemax="<?php echo (int)$b['ima_obrok']; ?>">
                  <div class="progress-bar" style="width: <?php echo $b['ima_obrok'] > 0 ? min(100, round($b['izdato'] / $b['ima_obrok'] * 100)) : 0; ?>%"></div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
          <p class="today-note mb-0">Izdato danas u ovoj menzi / koliko studenata ima taj obrok ovog meseca.</p>
        </section>

        <section class="panel kasa-panel mb-3 mb-lg-4">
          <h2 class="panel-title">Poslednja skeniranja</h2>
          <?php if (empty($skeniranja)): ?>
            <p class="message-text mb-0">Još nema skeniranja na ovoj kasi.</p>
          <?php endif; ?>
          <?php foreach ($skeniranja as $s): ?>
            <?php $o = $OZNAKA_ISHODA[$s['ishod']] ?? ['naziv' => '', 'oznaka' => 'tag-gray', 'boja' => 'tint-blue', 'ikonica' => 'qr']; ?>
            <div class="scan-item d-flex align-items-center gap-3">
              <span class="tint-box <?php echo $o['boja']; ?>"><?php echo ikonica($o['ikonica']); ?></span>
              <div class="flex-fill min-w-0">
                <div class="scan-name"><?php echo htmlspecialchars($s['student']); ?><?php echo $s['obrok'] !== '' ? ' · ' . htmlspecialchars($s['obrok']) : ''; ?></div>
                <div class="scan-meta"><?php echo htmlspecialchars($s['vreme']); ?><?php echo $s['razlog'] !== '' ? ' · ' . htmlspecialchars($s['razlog']) : ''; ?></div>
              </div>
              <span class="tag <?php echo $o['oznaka']; ?>"><?php echo $o['naziv']; ?></span>
            </div>
          <?php endforeach; ?>
        </section>

        <section class="panel kasa-panel">
          <h2 class="panel-title">Menza na ovoj kasi</h2>
          <nav class="d-flex flex-wrap gap-2" aria-label="Izbor menze">
            <?php foreach ($menze as $m): ?>
              <?php $ova = (int)$m['id'] === (int)$menza['id']; ?>
              <a href="kasir_bonovi.php?menza=<?php echo (int)$m['id']; ?>"
                 class="btn-choice btn-choice-kasa<?php echo $ova ? ' active' : ''; ?>"<?php echo $ova ? ' aria-current="true"' : ''; ?>>
                <?php echo htmlspecialchars($m['naziv']); ?>
              </a>
            <?php endforeach; ?>
          </nav>
        </section>
      </div>
    </div>

  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
