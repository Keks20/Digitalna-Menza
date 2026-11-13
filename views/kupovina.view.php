<?php
/**
 * PRIKAZ: kupovina obroka (izgled po Figmi – ekran "Kupovina obroka").
 *
 * Spisak promenljivih i imena polja forme su na vrhu kupovina.php.
 * Iznosi u pregledu se računaju u pregledaču samo za prikaz – server sve ponovo računa.
 * Samo prikaz, bez upita u bazu; tekst iz baze se ispisuje kroz htmlspecialchars().
 */
$naslovStrane = 'Kupovina obroka';
$aktivnaStrana = 'kupovina.php';
$stilovi = ['css/jelovnik.css', 'css/kupovina.css'];   // .btn-choice (dugmad za mesec) je iz jelovnik.css
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/meni.php';

// Ikonica i boja za svaki obrok (po id-ju iz tabele tipovi_obroka) – isto kao na početnoj
$izgledObroka = [
    1 => ['ikonica' => 'solja', 'boja' => 'tint-orange'],  // Doručak
    2 => ['ikonica' => 'jaje',  'boja' => 'tint-blue'],    // Ručak
    3 => ['ikonica' => 'mesec', 'boja' => 'tint-green'],   // Večera
];
$naziviMenzi = array_map(fn($m) => preg_replace('/^Menza\s+/u', '', $m['naziv']), $menze);
?>
  <main class="app-main app-main-wide">

    <header class="page-header d-flex justify-content-between align-items-start gap-3">
      <div>
        <h1 class="page-title">Kupovina obroka</h1>
        <p class="page-date">
          <span class="d-none d-lg-inline">Kupljeni obroci važe do kraja izabranog meseca ·</span>
          stanje <strong class="text-body"><?php echo Format::novac($kartica->stanje); ?></strong>
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
        <?php if (str_contains(implode(' ', $greske), 'Nema dovoljno novca')): ?>
          <a href="dopuna.php" class="alert-link">Dopuni karticu</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <form method="post" action="kupovina.php" id="formaKupovine">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
      <input type="hidden" name="akcija" value="kupi" />
      <input type="hidden" name="mesec" value="<?php echo htmlspecialchars($mesec); ?>" />

      <div class="row g-3 g-lg-4 align-items-start">
        <div class="col-lg-7">
          <section class="panel buy-panel">
            <h2 class="panel-title">Mesec</h2>
            <div class="d-flex flex-wrap gap-2 gap-lg-3 mb-4">
              <?php foreach ($meseci as $m): ?>
                <a href="kupovina.php?mesec=<?php echo $m['datum']; ?>"
                   class="btn-choice<?php echo $m['datum'] === $mesec ? ' active' : ''; ?>"
                   <?php echo $m['datum'] === $mesec ? 'aria-current="page"' : ''; ?>><?php echo $m['naziv']; ?></a>
              <?php endforeach; ?>
            </div>

            <div class="note-box buy-category mb-4">
              <?php echo ikonica('korisnik'); ?>
              <p>
                <strong><?php echo htmlspecialchars($student->nazivKategorije()); ?></strong><br />
                <?php echo htmlspecialchars($student->opisKategorije()); ?>
              </p>
            </div>

            <h2 class="panel-title mb-2">Broj obroka</h2>
            <?php foreach ($obroci as $tipId => $o): ?>
              <?php
                $izgled = $izgledObroka[(int)$tipId] ?? ['ikonica' => 'solja', 'boja' => 'tint-blue'];
                $nema = $o['maks'] === 0;
              ?>
              <div class="buy-row d-flex flex-wrap align-items-center<?php echo $nema ? ' is-full' : ''; ?><?php echo !$o['dozvoljen'] ? ' is-locked' : ''; ?>">
                <span class="tint-box tint-box-lg <?php echo $izgled['boja']; ?>"><?php echo ikonica($izgled['ikonica']); ?></span>
                <div class="buy-info">
                  <label for="kolicina-<?php echo $tipId; ?>" class="buy-name"><?php echo htmlspecialchars($o['tip']->naziv); ?></label>
                  <div class="buy-meta">
                    <?php echo $o['tip']->radnoVreme(); ?> · <?php echo Format::cena($o['cena']); ?> po obroku
                  </div>
                  <?php if (!$o['dozvoljen']): ?>
                    <div class="buy-meta"><span class="tag tag-gray ms-0">Nije dostupno za tvoju kategoriju</span></div>
                  <?php elseif ($o['kupljeno'] > 0 || $nema): ?>
                    <div class="buy-meta">
                      <?php if ($o['kupljeno'] > 0): ?>već kupljeno <?php echo $o['kupljeno']; ?> · <?php endif; ?>
                      <?php echo $nema ? 'za ovaj mesec više ne može' : 'najviše još ' . $o['maks']; ?>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="stepper d-flex align-items-center">
                  <button type="button" class="stepper-btn" data-korak="-1" data-za="kolicina-<?php echo $tipId; ?>"
                          aria-label="Manje: <?php echo htmlspecialchars($o['tip']->naziv); ?>" <?php echo $nema ? 'disabled' : ''; ?>><?php echo ikonica('minus'); ?></button>
                  <input type="number" class="stepper-input" id="kolicina-<?php echo $tipId; ?>"
                         name="kolicina[<?php echo $tipId; ?>]" min="0" max="<?php echo $o['maks']; ?>" step="1" inputmode="numeric"
                         value="<?php echo htmlspecialchars((string)$forma['kolicine'][$tipId]); ?>"
                         data-cena="<?php echo $o['cena']; ?>" data-naziv="<?php echo htmlspecialchars($o['tip']->naziv); ?>"
                         <?php echo $nema ? 'readonly' : ''; ?> />
                  <button type="button" class="stepper-btn" data-korak="1" data-za="kolicina-<?php echo $tipId; ?>"
                          aria-label="Više: <?php echo htmlspecialchars($o['tip']->naziv); ?>" <?php echo $nema ? 'disabled' : ''; ?>><?php echo ikonica('plus'); ?></button>
                </div>
                <strong class="buy-amount" data-iznos-za="<?php echo $tipId; ?>">–</strong>
              </div>
            <?php endforeach; ?>

            <div class="note-box mt-3">
              <?php echo ikonica('kalendar-ok'); ?>
              <p>
                Obroci kupljeni za <?php echo htmlspecialchars(lcfirst($nazivMeseca)); ?> važe do <?php echo $vaziDo; ?>
                i mogu se iskoristiti u svakoj menzi (<?php echo htmlspecialchars(implode(', ', $naziviMenzi)); ?>).
                Najviše jedan obrok svake vrste dnevno.
              </p>
            </div>
          </section>
        </div>

        <div class="col-lg-5">
          <section class="panel buy-summary">
            <h2 class="panel-title mb-1">Pregled kupovine</h2>
            <div id="pregledStavke"></div>
            <div class="summary-total">
              <span>Ukupno</span>
              <span class="amount" id="pregledUkupno">–</span>
            </div>
            <div class="summary-row"><span>Stanje na kartici</span><strong><?php echo Format::novac($kartica->stanje); ?></strong></div>
            <div class="summary-row"><span>Stanje posle kupovine</span><strong id="pregledStanje">–</strong></div>
            <p class="buy-low mb-0 mt-2" id="nemaNovca" hidden>
              Nemaš dovoljno novca na kartici. <a href="dopuna.php">Dopuni karticu</a>
            </p>
            <button type="submit" class="btn btn-primary btn-app btn-app-lg w-100 mt-3" id="dugmeKupi">Kupi obroke</button>
          </section>
        </div>
      </div>
    </form>
  </main>

  <script>
    // Dugmad − / + i pregled kupovine (samo prikaz – server sve ponovo računa)
    (function () {
      const stanje = <?php echo json_encode((float)$kartica->stanje); ?>;
      const forma = document.getElementById('formaKupovine');
      const polja = forma.querySelectorAll('.stepper-input');
      const novac = (x) => x.toLocaleString('sr-RS', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' RSD';
      const broj = (x) => x.toLocaleString('sr-RS', { maximumFractionDigits: 2 });

      forma.querySelectorAll('.stepper-btn').forEach(function (dugme) {
        dugme.addEventListener('click', function () {
          const polje = document.getElementById(dugme.dataset.za);
          const max = parseInt(polje.max, 10);
          const nova = (parseInt(polje.value, 10) || 0) + parseInt(dugme.dataset.korak, 10);
          polje.value = Math.min(max, Math.max(0, nova));
          osvezi();
        });
      });

      function osvezi() {
        let ukupno = 0;
        const lista = document.getElementById('pregledStavke');
        lista.textContent = '';
        polja.forEach(function (polje) {
          const kolicina = Math.max(0, parseInt(polje.value, 10) || 0);
          const cena = parseFloat(polje.dataset.cena);
          const iznos = kolicina * cena;
          ukupno += iznos;
          document.querySelector('[data-iznos-za="' + polje.id.replace('kolicina-', '') + '"]').textContent = novac(iznos);
          if (kolicina > 0) {
            // Red pravim preko textContent, da naziv iz baze ne može da ubaci HTML
            const red = document.createElement('div');
            red.className = 'summary-row';
            const opis = document.createElement('span');
            opis.textContent = polje.dataset.naziv + ' · ' + kolicina + ' × ' + broj(cena) + ' RSD';
            const vrednost = document.createElement('strong');
            vrednost.textContent = novac(iznos);
            red.append(opis, vrednost);
            lista.append(red);
          }
        });
        const posle = stanje - ukupno;
        document.getElementById('pregledUkupno').textContent = novac(ukupno);
        const pregledStanje = document.getElementById('pregledStanje');
        pregledStanje.textContent = novac(posle);
        pregledStanje.classList.toggle('amount-red', posle < 0);
        document.getElementById('nemaNovca').hidden = posle >= 0;
        document.getElementById('dugmeKupi').textContent = ukupno > 0 ? 'Kupi obroke — ' + novac(ukupno) : 'Kupi obroke';
      }

      polja.forEach((polje) => polje.addEventListener('input', osvezi));
      osvezi();
    })();
  </script>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
