<?php
/**
 * PRIKAZ: kupovina obroka.
 *
 * Privremeni raspored – konačni izgled je ekran "Kupovina obroka" u Figmi.
 * Spisak promenljivih i imena polja forme su na vrhu kupovina.php.
 * Samo prikaz, bez upita u bazu; tekst iz baze se ispisuje kroz htmlspecialchars().
 */
$naslovStrane = 'Kupovina obroka';
$aktivnaStrana = 'kupovina.php';
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/meni.php';
?>
  <main class="app-main app-main-wide">

    <header class="page-header d-flex justify-content-between align-items-start gap-3">
      <div>
        <h1 class="page-title">Kupovina obroka</h1>
        <p class="page-date">Kupljeni obroci važe do kraja izabranog meseca · stanje <?php echo Format::novac($kartica->stanje); ?></p>
      </div>
      <?php require __DIR__ . '/delovi/korisnik.php'; ?>
    </header>

    <?php if ($poruka): ?>
      <div class="alert app-alert alert-<?php echo $poruka['tip'] === 'success' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($poruka['tekst']); ?></div>
    <?php endif; ?>
    <?php if (!empty($greske)): ?>
      <div class="alert app-alert alert-danger" role="alert">
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

      <div class="row g-3 g-lg-4">
        <div class="col-lg-7">
          <section class="panel">
            <h2 class="panel-title">Mesec</h2>
            <div class="d-flex flex-wrap gap-2 mb-4">
              <?php foreach ($meseci as $m): ?>
                <a href="kupovina.php?mesec=<?php echo $m['datum']; ?>"
                   class="btn <?php echo $m['datum'] === $mesec ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                   <?php echo $m['datum'] === $mesec ? 'aria-current="page"' : ''; ?>><?php echo $m['naziv']; ?></a>
              <?php endforeach; ?>
            </div>

            <h2 class="panel-title">Broj obroka</h2>
            <?php foreach ($obroci as $tipId => $o): ?>
              <div class="d-flex flex-wrap align-items-center gap-3 border-top py-3">
                <div class="flex-fill">
                  <label for="kolicina-<?php echo $tipId; ?>" class="fw-bold"><?php echo htmlspecialchars($o['tip']->naziv); ?></label>
                  <div class="small text-muted">
                    <?php echo $o['tip']->radnoVreme(); ?> · <?php echo Format::cena($o['tip']->cena); ?> po obroku
                    <?php if ($o['kupljeno'] > 0): ?> · već kupljeno <?php echo $o['kupljeno']; ?><?php endif; ?>
                    · najviše još <?php echo $o['maks']; ?>
                  </div>
                </div>
                <input type="number" class="form-control" style="width: 90px;" id="kolicina-<?php echo $tipId; ?>"
                       name="kolicina[<?php echo $tipId; ?>]" min="0" max="<?php echo $o['maks']; ?>" step="1"
                       value="<?php echo htmlspecialchars((string)$forma['kolicine'][$tipId]); ?>"
                       data-cena="<?php echo $o['tip']->cena; ?>" data-naziv="<?php echo htmlspecialchars($o['tip']->naziv); ?>" />
                <strong class="text-end" style="min-width: 120px;" data-iznos-za="<?php echo $tipId; ?>">–</strong>
              </div>
            <?php endforeach; ?>

            <p class="small text-muted mb-0 mt-2">
              Obroci kupljeni za <?php echo htmlspecialchars(lcfirst($nazivMeseca)); ?> važe do <?php echo $vaziDo; ?>
              i mogu se iskoristiti u svakoj menzi (<?php echo htmlspecialchars(implode(', ', array_column($menze, 'naziv'))); ?>).
              Najviše jedan obrok svake vrste dnevno.
            </p>
          </section>
        </div>

        <div class="col-lg-5">
          <section class="panel">
            <h2 class="panel-title">Pregled kupovine</h2>
            <div id="pregledStavke" class="small"></div>
            <div class="d-flex justify-content-between border-top pt-2 mt-2"><strong>Ukupno</strong><strong id="pregledUkupno">–</strong></div>
            <div class="d-flex justify-content-between small"><span class="text-muted">Stanje na kartici</span><span><?php echo Format::novac($kartica->stanje); ?></span></div>
            <div class="d-flex justify-content-between small"><span class="text-muted">Stanje posle kupovine</span><span id="pregledStanje">–</span></div>
            <button type="submit" class="btn btn-primary w-100 mt-3" id="dugmeKupi">Kupi obroke</button>
          </section>
        </div>
      </div>
    </form>
  </main>

  <script>
    // Pregled kupovine se osvežava dok student menja brojeve (samo prikaz – server sve ponovo računa)
    (function () {
      const stanje = <?php echo json_encode((float)$kartica->stanje); ?>;
      const polja = document.querySelectorAll('#formaKupovine input[type="number"]');
      const novac = (x) => x.toLocaleString('sr-RS', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' RSD';

      function osvezi() {
        let ukupno = 0;
        const lista = document.getElementById('pregledStavke');
        lista.textContent = '';
        polja.forEach(function (polje) {
          const broj = Math.max(0, parseInt(polje.value, 10) || 0);
          const cena = parseFloat(polje.dataset.cena);
          const iznos = broj * cena;
          ukupno += iznos;
          document.querySelector('[data-iznos-za="' + polje.id.replace('kolicina-', '') + '"]').textContent = novac(iznos);
          if (broj > 0) {
            // Red pravim preko textContent, da naziv iz baze ne može da ubaci HTML
            const red = document.createElement('div');
            red.className = 'd-flex justify-content-between';
            const opis = document.createElement('span');
            opis.className = 'text-muted';
            opis.textContent = polje.dataset.naziv + ' · ' + broj + ' × ' + cena + ' RSD';
            const vrednost = document.createElement('span');
            vrednost.textContent = novac(iznos);
            red.append(opis, vrednost);
            lista.append(red);
          }
        });
        document.getElementById('pregledUkupno').textContent = novac(ukupno);
        document.getElementById('pregledStanje').textContent = novac(stanje - ukupno);
        document.getElementById('dugmeKupi').textContent = ukupno > 0 ? 'Kupi obroke — ' + novac(ukupno) : 'Kupi obroke';
      }

      polja.forEach((polje) => polje.addEventListener('input', osvezi));
      osvezi();
    })();
  </script>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
