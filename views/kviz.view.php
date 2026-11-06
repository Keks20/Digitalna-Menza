<?php
/**
 * PRIKAZ: mini kviz o aplikaciji (pitanja, rezultat sa objašnjenjima i 10 najboljih).
 *
 * Spisak promenljivih i imena polja forme su na vrhu kviz.php.
 * Prijavljeni student vidi meni, a gost (bez prijave) samo traku sa linkom na prijavu.
 * Samo prikaz, bez upita u bazu; tekst iz baze se ispisuje kroz htmlspecialchars().
 * Klase panel, btn-choice, tag i note-box su Pavletove (style.css i jelovnik.css), a css/kviz.css dodaje samo ono što fali.
 */
$naslovStrane = 'Kviz';
$aktivnaStrana = 'kviz.php';
$stilovi = ['css/jelovnik.css', 'css/kviz.css'];   // .btn-choice je iz jelovnik.css
require __DIR__ . '/delovi/zaglavlje.php';
require_once __DIR__ . '/delovi/ikonice.php';
if ($prijavljen) {
    require __DIR__ . '/delovi/meni.php';
}
?>
<?php if (!$prijavljen): ?>
  <!-- Traka za gosta: logo i prijava -->
  <div class="kviz-gost-traka d-flex align-items-center justify-content-between gap-3">
    <a href="login.php" class="kviz-brand d-flex align-items-center gap-2">
      <span class="kviz-logo"><?php echo ikonica('solja'); ?></span>
      <span>Digitalna menza</span>
    </a>
    <a href="login.php" class="btn btn-app btn-app-outline"><?php echo ikonica('korisnik'); ?> Prijava</a>
  </div>
<?php endif; ?>

  <main class="app-main app-main-wide<?php echo $prijavljen ? '' : ' kviz-main-gost'; ?>">

    <header class="page-header d-flex justify-content-between align-items-start gap-3">
      <div>
        <h1 class="page-title">Kviz o aplikaciji</h1>
        <p class="page-date">
          <?php if ($rezultat): ?>
            Rezultat je sačuvan na listi najboljih.
          <?php else: ?>
            <?php echo $brojPitanja; ?> nasumičnih pitanja o Digitalnoj menzi · posle slanja vidiš tačne odgovore i objašnjenja
          <?php endif; ?>
        </p>
      </div>
      <?php if ($prijavljen): ?>
        <?php require __DIR__ . '/delovi/korisnik.php'; ?>
      <?php endif; ?>
    </header>

    <?php if (!empty($greske)): ?>
      <div class="alert app-alert alert-danger mb-3 mb-lg-4" role="alert">
        <?php foreach ($greske as $g): ?><div><?php echo htmlspecialchars($g); ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="row g-3 g-lg-4 align-items-start">
      <div class="col-lg-8">

        <?php if ($rezultat): ?>
          <!-- ===== REZULTAT ===== -->
          <section class="panel kviz-rezultat d-flex flex-wrap align-items-center gap-3 gap-lg-4 mb-3">
            <div class="kviz-skor">
              <strong><?php echo $rezultat['broj_tacnih']; ?></strong><span>/ <?php echo $rezultat['ukupno']; ?></span>
            </div>
            <div class="flex-grow-1">
              <h2 class="panel-title mb-1"><?php echo htmlspecialchars($rezultat['ocena']); ?></h2>
              <p class="kviz-sitno">
                <?php echo htmlspecialchars($rezultat['ime']); ?> · <?php echo $rezultat['procenat']; ?>% tačnih ·
                vreme <?php echo $rezultat['trajanje']; ?>
              </p>
            </div>
            <a href="kviz.php?novi=1" class="btn btn-primary btn-app"><?php echo ikonica('istorija'); ?> Novi kviz</a>
          </section>

          <?php foreach ($rezultat['stavke'] as $i => $stavka): ?>
            <?php $p = $stavka['pitanje']; ?>
            <section class="panel kviz-pitanje mb-3">
              <div class="kviz-meta d-flex flex-wrap align-items-center gap-1 mb-2">
                <span class="kviz-broj">Pitanje <?php echo $i + 1; ?></span>
                <span class="tag <?php echo $stavka['tacno'] ? 'tag-green' : 'tag-red'; ?>"><?php echo $stavka['tacno'] ? 'Tačno' : 'Netačno'; ?></span>
                <span class="tag tag-gray"><?php echo htmlspecialchars($p->tema); ?></span>
              </div>
              <h2 class="kviz-tekst"><?php echo htmlspecialchars($p->pitanje); ?></h2>

              <ul class="kviz-pregled list-unstyled d-grid gap-2">
                <?php foreach ($p->odgovori() as $slovo => $tekst): ?>
                  <?php
                    $tacan = $slovo === $p->tacan;
                    $izabran = $slovo === $stavka['odgovor'];
                    $klasa = $tacan ? ' is-correct' : ($izabran ? ' is-wrong' : '');
                  ?>
                  <li class="kviz-odgovor<?php echo $klasa; ?>">
                    <span class="kviz-slovo"><?php echo strtoupper($slovo); ?></span>
                    <span class="flex-grow-1"><?php echo htmlspecialchars($tekst); ?></span>
                    <?php if ($izabran): ?><span class="kviz-oznaka">tvoj odgovor</span><?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>

              <div class="note-box mt-3">
                <?php echo ikonica('info'); ?>
                <p><?php echo htmlspecialchars($p->objasnjenje); ?></p>
              </div>
            </section>
          <?php endforeach; ?>

          <a href="kviz.php?novi=1" class="btn btn-primary btn-app btn-app-lg w-100"><?php echo ikonica('istorija'); ?> Reši novi kviz</a>

        <?php elseif (empty($pitanja)): ?>
          <section class="panel">
            <p>Kviz još nema pitanja. Administrator treba da uveze <code>db/kviz.sql</code>.</p>
          </section>

        <?php else: ?>
          <!-- ===== PITANJA ===== -->
          <form method="post" action="kviz.php" id="formaKviza">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
            <input type="hidden" name="akcija" value="posalji" />

            <?php if (!$prijavljen): ?>
              <section class="panel mb-3">
                <label for="ime" class="form-label">Tvoje ime</label>
                <input type="text" class="form-control app-input" id="ime" name="ime" required
                       minlength="2" maxlength="50" autocomplete="given-name" placeholder="npr. Ana"
                       value="<?php echo htmlspecialchars($forma['ime']); ?>" />
                <p class="kviz-sitno mt-2">Ime se prikazuje na listi najboljih. Ako imaš studentsku karticu,
                  <a href="login.php">prijavi se</a> i rezultat se upisuje tebi.</p>
              </section>
            <?php endif; ?>

            <?php $redniBroj = 0; ?>
            <?php foreach ($pitanja as $id => $p): ?>
              <?php $redniBroj++; ?>
              <section class="panel kviz-pitanje mb-3">
                <div class="kviz-meta d-flex flex-wrap align-items-center gap-1 mb-2">
                  <span class="kviz-broj">Pitanje <?php echo $redniBroj; ?> od <?php echo $brojPitanja; ?></span>
                  <span class="tag tag-blue"><?php echo htmlspecialchars($p->tema); ?></span>
                  <span class="tag tag-gray"><?php echo htmlspecialchars($p->nazivTezine()); ?></span>
                </div>
                <h2 class="kviz-tekst" id="pitanje-<?php echo $id; ?>"><?php echo htmlspecialchars($p->pitanje); ?></h2>

                <div class="d-grid gap-2" role="radiogroup" aria-labelledby="pitanje-<?php echo $id; ?>">
                  <?php $prvi = true; ?>
                  <?php foreach ($p->odgovori() as $slovo => $tekst): ?>
                    <?php $poljeId = 'odgovor-' . $id . '-' . $slovo; ?>
                    <input type="radio" class="btn-check" name="odgovor[<?php echo $id; ?>]" id="<?php echo $poljeId; ?>"
                           value="<?php echo $slovo; ?>" autocomplete="off"
                           <?php echo ($forma['odgovori'][$id] ?? '') === $slovo ? 'checked' : ''; ?>
                           <?php echo $prvi ? 'required' : ''; ?> />
                    <label class="btn-choice kviz-odgovor" for="<?php echo $poljeId; ?>">
                      <span class="kviz-slovo"><?php echo strtoupper($slovo); ?></span>
                      <span><?php echo htmlspecialchars($tekst); ?></span>
                    </label>
                    <?php $prvi = false; ?>
                  <?php endforeach; ?>
                </div>
              </section>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-primary btn-app btn-app-lg w-100" id="dugmePosalji">
              <?php echo ikonica('posalji'); ?> Pošalji odgovore
            </button>
          </form>
        <?php endif; ?>
      </div>

      <!-- ===== 10 NAJBOLJIH ===== -->
      <div class="col-lg-4">
        <section class="panel kviz-najbolji">
          <h2 class="panel-title">10 najboljih</h2>
          <?php if (empty($najbolji)): ?>
            <p class="kviz-sitno">Još niko nije rešio kviz. Budi prvi!</p>
          <?php else: ?>
            <ol class="list-unstyled mb-0">
              <?php foreach ($najbolji as $red): ?>
                <li class="kviz-red d-flex align-items-center gap-3<?php echo $red['moj'] ? ' is-mine' : ''; ?>">
                  <span class="kviz-mesto"><?php echo $red['mesto']; ?></span>
                  <div class="flex-grow-1 min-w-0">
                    <div class="kviz-ime text-truncate"><?php echo htmlspecialchars($red['ime']); ?></div>
                    <div class="kviz-sitno"><?php echo $red['trajanje']; ?> · <?php echo $red['datum']; ?></div>
                  </div>
                  <strong class="kviz-poeni"><?php echo $red['broj_tacnih']; ?>/<?php echo $red['ukupno']; ?></strong>
                </li>
              <?php endforeach; ?>
            </ol>
          <?php endif; ?>
        </section>
      </div>
    </div>
  </main>

<?php if (!$rezultat && !empty($pitanja)): ?>
  <script>
    // Dugme se isključuje posle slanja, da dupli klik ne pošalje formu dva puta
    document.getElementById('formaKviza').addEventListener('submit', function () {
      document.getElementById('dugmePosalji').disabled = true;
    });
  </script>
<?php endif; ?>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
