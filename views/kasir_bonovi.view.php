<?php
/**
 * PRIKAZ: provera QR bonova na kasi (izgled po Figmi – ekran "Kasir-provera-bonova").
 *
 * Privremeni raspored rezultata, broja izdatih obroka i skeniranja – konačni izgled je u Figmi.
 * Spisak promenljivih i imena polja su na vrhu kasir_bonovi.php.
 *
 * Kamera: js/kasa_skener.js čita QR iz #kameraVideo (biblioteka jsQR) i šalje formu #formaSkeniranja.
 * Polje #bonTekst služi i za USB čitač QR koda (on "kuca" tekst bona i Enter).
 */
$naslovStrane = 'Provera bonova';
$aktivnaStrana = 'kasir_bonovi.php';
$stilovi = ['css/admin.css'];
$skripte = ['https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js', 'js/kasa_skener.js'];
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/admin_meni.php';
require_once __DIR__ . '/../src/Format.php';

// Izgled kartice rezultata po ishodu: [naslov, Bootstrap boja]
$IZGLED_ISHODA = [
    'moze' => ['Bon važi — izdaj obrok', 'success'],
    'izdato' => ['Obrok je izdat', 'success'],
    'vec_izdato' => ['Već izdato danas', 'warning'],
    'odbijeno' => ['Obrok se ne izdaje', 'danger'],
];
$OZNAKA_ISHODA = [
    'izdato' => ['Izdato', 'tag-green'],
    'vec_izdato' => ['Već izdato', 'tag-orange'],
    'odbijeno' => ['Odbijeno', 'tag-red'],
];
?>
  <main class="app-main">

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

    <div class="row g-3 g-lg-4">
      <div class="col-lg-7">
        <section class="panel">
          <div class="panel-head">
            <h2>Skeniraj QR bon</h2>
            <span class="tag tag-gray" id="kameraStatus">Kamera isključena</span>
          </div>

          <!-- Kamera -->
          <div class="scanner mb-3">
            <video id="kameraVideo" playsinline muted style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover; display:none;"></video>
            <span class="scanner-corner tl"></span>
            <span class="scanner-corner tr"></span>
            <span class="scanner-corner bl"></span>
            <span class="scanner-corner br"></span>
            <span class="scanner-line"></span>
            <p class="scanner-text">Usmeri kameru na QR kod sa telefona studenta</p>
          </div>
          <button type="button" class="btn btn-outline-primary w-100 mb-3" id="dugmeKamera">Uključi kameru</button>

          <!-- Rezultat poslednje provere -->
          <?php if ($rezultat !== null): ?>
            <?php [$naslovRezultata, $boja] = $IZGLED_ISHODA[$rezultat['ishod']]; ?>
            <div class="alert alert-<?php echo $boja; ?> mb-3" role="status" id="rezultatProvere">
              <div class="fw-bold fs-5"><?php echo $naslovRezultata; ?></div>
              <div class="small mb-2">Skenirano u <?php echo htmlspecialchars($rezultat['skenirano'] ?? ''); ?></div>
              <?php if (!empty($rezultat['razlog'])): ?>
                <p class="mb-2"><?php echo htmlspecialchars($rezultat['razlog']); ?></p>
              <?php endif; ?>
              <?php if (isset($rezultat['student'])): ?>
                <div class="d-flex justify-content-between"><span>Student</span><strong><?php echo htmlspecialchars($rezultat['student']); ?></strong></div>
                <div class="d-flex justify-content-between"><span>Kartica</span><strong><?php echo htmlspecialchars(Format::brojKartice($rezultat['broj_kartice'])); ?></strong></div>
                <div class="d-flex justify-content-between"><span>Obrok</span><strong><?php echo htmlspecialchars($rezultat['obrok']); ?></strong></div>
                <?php if (in_array($rezultat['ishod'], ['moze', 'izdato'], true)): ?>
                  <div class="d-flex justify-content-between"><span><?php echo $rezultat['ishod'] === 'moze' ? 'Preostalo posle ovog' : 'Preostalo'; ?></span>
                    <strong><?php echo (int)$rezultat['preostalo_posle']; ?> / <?php echo (int)$rezultat['kupljeno']; ?></strong></div>
                <?php endif; ?>
              <?php endif; ?>

              <?php if ($rezultat['ishod'] === 'moze'): ?>
                <form method="post" action="kasir_bonovi.php" class="mt-3">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
                  <input type="hidden" name="akcija" value="izdaj" />
                  <button type="submit" class="btn btn-primary w-100" id="dugmeIzdaj" autofocus>Potvrdi izdavanje</button>
                </form>
                <form method="post" action="kasir_bonovi.php" class="mt-2">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
                  <input type="hidden" name="akcija" value="odustani" />
                  <button type="submit" class="btn btn-outline-secondary w-100">Odustani</button>
                </form>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <!-- Tekst bona: popunjava ga kamera ili USB čitač -->
          <form method="post" action="kasir_bonovi.php" id="formaSkeniranja" class="mb-3">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
            <input type="hidden" name="akcija" value="skeniraj" />
            <label for="bonTekst" class="form-label small fw-semibold">QR bon (čitač ili kamera)</label>
            <input type="text" class="form-control app-input" id="bonTekst" name="bon" autocomplete="off"
                   placeholder="DM1…" <?php echo ($rezultat['ishod'] ?? '') === 'moze' ? '' : 'autofocus'; ?> />
          </form>

          <!-- Rezervni put: broj kartice -->
          <form method="post" action="kasir_bonovi.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
            <input type="hidden" name="akcija" value="kartica" />
            <label for="brojKartice" class="form-label small fw-semibold">Kamera ne radi? Unesi broj kartice</label>
            <div class="d-flex flex-wrap gap-2">
              <input type="text" inputmode="numeric" class="form-control app-input flex-fill" style="min-width: 180px;" id="brojKartice"
                     name="broj_kartice" maxlength="14" placeholder="0012 3456 7890" />
              <select class="form-select app-input" style="width: auto;" name="tip_obroka" aria-label="Obrok">
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
        <section class="panel mb-3 mb-lg-4">
          <div class="panel-head">
            <h2>Danas na ovoj kasi</h2>
          </div>
          <?php foreach ($tipovi as $tip): ?>
            <?php $b = $danasBrojevi[(int)$tip->id] ?? ['izdato' => 0, 'ima_obrok' => 0]; ?>
            <div class="mb-3">
              <div class="d-flex justify-content-between">
                <span<?php echo $trenutniObrok && $trenutniObrok->id == $tip->id ? ' class="fw-bold"' : ''; ?>><?php echo htmlspecialchars($tip->naziv); ?></span>
                <strong><?php echo $b['izdato']; ?> / <?php echo $b['ima_obrok']; ?></strong>
              </div>
              <div class="progress" style="height: 6px;" role="progressbar" aria-label="<?php echo htmlspecialchars($tip->naziv); ?>"
                   aria-valuenow="<?php echo $b['izdato']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $b['ima_obrok']; ?>">
                <div class="progress-bar" style="width: <?php echo $b['ima_obrok'] > 0 ? min(100, round($b['izdato'] / $b['ima_obrok'] * 100)) : 0; ?>%"></div>
              </div>
            </div>
          <?php endforeach; ?>
          <p class="small text-muted mb-0">Izdato danas u ovoj menzi / broj studenata koji imaju taj obrok ovog meseca.</p>
        </section>

        <section class="panel mb-3 mb-lg-4">
          <div class="panel-head">
            <h2>Poslednja skeniranja</h2>
          </div>
          <?php if (empty($skeniranja)): ?>
            <p class="small text-muted mb-0">Još nema skeniranja na ovoj kasi.</p>
          <?php endif; ?>
          <?php foreach ($skeniranja as $s): ?>
            <?php [$nazivIshoda, $oznaka] = $OZNAKA_ISHODA[$s['ishod']] ?? ['', 'tag-gray']; ?>
            <div class="d-flex justify-content-between align-items-center border-top py-2 gap-2">
              <div class="min-w-0">
                <div class="fw-semibold"><?php echo htmlspecialchars($s['student']); ?><?php echo $s['obrok'] !== '' ? ' · ' . htmlspecialchars($s['obrok']) : ''; ?></div>
                <div class="small text-muted"><?php echo htmlspecialchars($s['vreme']); ?><?php echo $s['razlog'] !== '' ? ' · ' . htmlspecialchars($s['razlog']) : ''; ?></div>
              </div>
              <span class="tag <?php echo $oznaka; ?>"><?php echo $nazivIshoda; ?></span>
            </div>
          <?php endforeach; ?>
        </section>

        <section class="panel">
          <div class="panel-head">
            <h2>Menza</h2>
          </div>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($menze as $m): ?>
              <a href="kasir_bonovi.php?menza=<?php echo (int)$m['id']; ?>"
                 class="btn btn-sm <?php echo (int)$m['id'] === (int)$menza['id'] ? 'btn-primary' : 'btn-outline-secondary'; ?>"><?php echo htmlspecialchars($m['naziv']); ?></a>
            <?php endforeach; ?>
          </div>
        </section>
      </div>
    </div>

  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
