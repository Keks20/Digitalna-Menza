<?php
/**
 * PRIKAZ: smeštaj – ugovor, mesečna zaduženja i plaćanje stanarine (izgled po Figmi – ekran "Smeštaj").
 *
 * Spisak promenljivih i imena polja forme su na vrhu smestaj.php.
 * Samo prikaz, bez upita u bazu; tekst iz baze se ispisuje kroz htmlspecialchars().
 */
$naslovStrane = 'Smeštaj';
$aktivnaStrana = 'smestaj.php';
$stilovi = ['css/dopuna.css', 'css/smestaj.css'];   // izbor načina plaćanja (.pay-option) je iz dopuna.css
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/meni.php';

$tipSobe = [1 => 'jednokrevetna', 2 => 'dvokrevetna', 3 => 'trokrevetna', 4 => 'četvorokrevetna'];

// Izgled načina plaćanja (ključevi su isti kao u $nacini iz smestaj.php)
$izgledNacina = [
    'kartica' => ['ikonica' => 'novcanik', 'boja' => 'tint-blue', 'opis' => 'Trenutno stanje ' . Format::novac($kartica->stanje)],
    'ips_qr' => ['ikonica' => 'qr', 'boja' => 'tint-green', 'opis' => 'Plaćanje iz aplikacije banke'],
];

/** Oznaka statusa za red u tabeli */
function oznakaZaduzenja(SmestajZaduzenje $z): array
{
    if ($z->placeno) {
        return ['Plaćeno', 'tag-green'];
    }
    return $z->kasni() ? ['Kasni', 'tag-red'] : ['Neplaćeno', 'tag-orange'];
}
?>
  <main class="app-main app-main-wide">

    <header class="page-header d-flex justify-content-between align-items-start gap-3">
      <div>
        <h1 class="page-title">Smeštaj</h1>
        <p class="page-date">Zaduženja za dom i plaćanje stanarine</p>
      </div>
      <?php require __DIR__ . '/delovi/korisnik.php'; ?>
    </header>

    <?php if ($poruka): ?>
      <div class="alert app-alert alert-<?php echo $poruka['tip'] === 'success' ? 'success' : 'danger'; ?> mb-3 mb-lg-4" role="alert"><?php echo htmlspecialchars($poruka['tekst']); ?></div>
    <?php endif; ?>
    <?php if (!empty($greske)): ?>
      <div class="alert app-alert alert-danger mb-3 mb-lg-4" role="alert">
        <?php foreach ($greske as $g): ?><div><?php echo htmlspecialchars($g); ?></div><?php endforeach; ?>
        <?php if (str_contains(implode(' ', $greske), 'novca')): ?>
          <a href="dopuna.php" class="alert-link">Dopuni karticu</a> ili izaberi plaćanje IPS QR kodom.
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($ugovor === null && empty($zaduzenja)): ?>
      <!-- Student ne živi u domu -->
      <section class="panel empty-state text-center">
        <span class="tint-box tint-box-lg tint-blue mx-auto"><?php echo ikonica('zgrada'); ?></span>
        <p class="empty-title">Nemaš ugovor o smeštaju u domu</p>
        <p class="empty-text">
          Kad potpišeš ugovor u Studentskom centru, ovde ćeš videti svoju sobu, mesečna zaduženja i plaćati stanarinu.
        </p>
      </section>
    <?php else: ?>

    <div class="row g-3 g-lg-4 align-items-start">
      <div class="col-lg-7">

        <!-- Ugovor i soba -->
        <?php if ($ugovor !== null): ?>
          <section class="panel room-panel mb-3 mb-lg-4">
            <div class="d-flex align-items-start gap-3 mb-2">
              <span class="tint-box tint-box-lg tint-blue"><?php echo ikonica('zgrada'); ?></span>
              <div class="flex-fill min-w-0">
                <h2 class="room-title">
                  <?php echo htmlspecialchars($ugovor['dom']); ?> · Paviljon <?php echo htmlspecialchars($ugovor['paviljon']); ?>
                  · soba <?php echo htmlspecialchars($ugovor['soba']); ?>
                </h2>
                <p class="room-sub">
                  Školska <?php echo htmlspecialchars($ugovor['skolska_godina']); ?>
                  <?php if (isset($tipSobe[(int)$ugovor['broj_kreveta']])): ?> · <?php echo $tipSobe[(int)$ugovor['broj_kreveta']]; ?> soba<?php endif; ?>
                </p>
              </div>
              <span class="tag tag-green flex-shrink-0">Ugovor aktivan</span>
            </div>
            <div class="summary-row"><span>Mesečna stanarina</span><strong><?php echo Format::novac($ugovor['mesecna_cena']); ?></strong></div>
            <div class="summary-row"><span>Rok plaćanja</span><strong>do 20. u mesecu</strong></div>
            <div class="summary-row"><span>Ugovor važi do</span><strong><?php echo Format::datum($ugovor['vazi_do']); ?></strong></div>
          </section>
        <?php endif; ?>

        <!-- Zaduženja -->
        <section class="panel room-panel">
          <div class="panel-head">
            <h2>Zaduženja</h2>
            <?php if ($brojNeplacenih > 0): ?>
              <span class="tag tag-orange"><?php echo $brojNeplacenih; ?> <?php echo Format::mnozina($brojNeplacenih, 'neplaćeno', 'neplaćena', 'neplaćenih'); ?></span>
            <?php else: ?>
              <span class="tag tag-green">Sve plaćeno</span>
            <?php endif; ?>
          </div>

          <?php if (empty($zaduzenja)): ?>
            <p class="message-text mb-0">Još nema zaduženja za stanarinu.</p>
          <?php else: ?>
            <div class="debt-head" aria-hidden="true">
              <span>Mesec</span><span class="d-none d-md-block text-end">Iznos</span><span class="d-none d-md-block">Rok</span><span>Status</span><span></span>
            </div>
            <?php foreach ($zaduzenja as $z): ?>
              <?php
                [$status, $boja] = oznakaZaduzenja($z);
                $izabranRed = $izabrano !== null && (int)$z->id === (int)$izabrano->id;
              ?>
              <div class="debt-row<?php echo $izabranRed ? ' is-selected' : ''; ?>">
                <span class="debt-month"><?php echo ucfirst(Format::mesecGodina($z->mesec)); ?></span>
                <span class="debt-amount d-none d-md-block"><?php echo Format::novac($z->iznos); ?></span>
                <span class="debt-due d-none d-md-block"><?php echo Format::datum($z->rok_placanja); ?></span>
                <span><span class="tag ms-0 <?php echo $boja; ?>"><?php echo $status; ?></span></span>
                <span class="text-end">
                  <?php if ($z->placeno): ?>
                    <a href="smestaj_potvrda.php?id=<?php echo (int)$z->id; ?>" class="panel-link">Potvrda</a>
                  <?php elseif ($izabranRed): ?>
                    <span class="debt-chosen">Izabrano</span>
                  <?php else: ?>
                    <a href="smestaj.php?zaduzenje=<?php echo (int)$z->id; ?>#placanje" class="btn btn-primary btn-sm debt-pay">Plati</a>
                  <?php endif; ?>
                </span>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </section>
      </div>

      <!-- Plaćanje -->
      <div class="col-lg-5">
        <section class="panel room-panel" id="placanje">
          <h2 class="panel-title mb-1">Plaćanje stanarine</h2>

          <?php if ($izabrano === null): ?>
            <div class="empty-state text-center py-4">
              <span class="tint-box tint-box-lg tint-green mx-auto"><?php echo ikonica('kvacica'); ?></span>
              <p class="empty-title">Sve je plaćeno</p>
              <p class="empty-text">Nemaš neplaćenih zaduženja. Potvrde za plaćene mesece su u tabeli.</p>
            </div>
          <?php else: ?>
            <form method="post" action="smestaj.php" id="formaStanarine">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
              <input type="hidden" name="akcija" value="plati" />
              <input type="hidden" name="zaduzenje" value="<?php echo (int)$izabrano->id; ?>" />

              <div class="summary-row"><span>Zaduženje</span><strong><?php echo ucfirst(Format::mesecGodina($izabrano->mesec)); ?></strong></div>
              <div class="summary-row"><span>Iznos</span><strong><?php echo Format::novac($izabrano->iznos); ?></strong></div>
              <?php if ($izabrano->kasni()): ?>
                <div class="summary-row"><span>Rok</span><strong class="amount-red"><?php echo Format::datum($izabrano->rok_placanja); ?> – kasni</strong></div>
              <?php endif; ?>

              <p class="form-label mt-3 mb-2" id="nacinOznaka">Način plaćanja</p>
              <div class="d-flex flex-column gap-3" role="radiogroup" aria-labelledby="nacinOznaka">
                <?php foreach ($nacini as $vrednost => $naziv): ?>
                  <?php $iz = $izgledNacina[$vrednost] ?? ['ikonica' => 'novac', 'boja' => 'tint-blue', 'opis' => '']; ?>
                  <label class="pay-option d-flex align-items-center" for="nacin-<?php echo $vrednost; ?>">
                    <span class="tint-box <?php echo $iz['boja']; ?>"><?php echo ikonica($iz['ikonica']); ?></span>
                    <span class="flex-fill">
                      <span class="pay-name"><?php echo htmlspecialchars($naziv); ?></span>
                      <span class="pay-desc"><?php echo $iz['opis']; ?></span>
                    </span>
                    <input class="form-check-input pay-radio" type="radio" name="nacin" id="nacin-<?php echo $vrednost; ?>"
                           value="<?php echo $vrednost; ?>" <?php echo $nacin === $vrednost ? 'checked' : ''; ?> />
                  </label>
                <?php endforeach; ?>
              </div>

              <div class="summary-total mt-2" id="redStanjePosle">
                <span>Stanje posle plaćanja</span>
                <span class="amount<?php echo $stanjePosle < 0 ? ' amount-red' : ''; ?>"><?php echo Format::novac($stanjePosle); ?></span>
              </div>
              <?php if ($stanjePosle < 0): ?>
                <p class="buy-low mb-0" id="nemaNovca">
                  Na kartici nema dovoljno novca. <a href="dopuna.php">Dopuni karticu</a> ili plati IPS QR kodom.
                </p>
              <?php endif; ?>
              <p class="ips-hint mb-0 mt-3" id="ipsNapomena" hidden>
                Otvoriće se IPS QR kod za <?php echo Format::novac(ceil($izabrano->iznos)); ?>. Čim uplata stigne, stanarina se plaća sama.
              </p>

              <button type="submit" class="btn btn-primary btn-app btn-app-lg w-100 mt-3" id="dugmePlati">
                Plati <?php echo Format::novac($izabrano->iznos); ?>
              </button>
            </form>

            <div class="note-box mt-3">
              <?php echo ikonica('info'); ?>
              <p>Posle uplate zaduženje dobija status „Plaćeno", a potvrdu možeš da preuzmeš iz tabele zaduženja.</p>
            </div>
          <?php endif; ?>
        </section>
      </div>
    </div>
    <?php endif; ?>
  </main>

  <script>
    // Kod IPS-a se stanje kartice ne menja, pa se umesto "Stanje posle plaćanja" prikazuje napomena
    (function () {
      const forma = document.getElementById('formaStanarine');
      if (!forma) {
        return;
      }
      const iznos = <?php echo json_encode($izabrano !== null ? Format::novac($izabrano->iznos) : ''); ?>;
      function osvezi() {
        const ips = forma.querySelector('input[name="nacin"]:checked')?.value === 'ips_qr';
        document.getElementById('redStanjePosle').hidden = ips;
        const nemaNovca = document.getElementById('nemaNovca');
        if (nemaNovca) {
          nemaNovca.hidden = ips;
        }
        document.getElementById('ipsNapomena').hidden = !ips;
        document.getElementById('dugmePlati').textContent = ips ? 'Nastavi na IPS QR uplatu' : 'Plati ' + iznos;
      }
      forma.addEventListener('change', osvezi);
      osvezi();
    })();
  </script>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
