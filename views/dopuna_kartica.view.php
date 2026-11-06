<?php
/**
 * PRIKAZ: dopuna kartice – plaćanje platnom karticom (izgled po Figmi – ekran "Dopuna-kartica").
 *
 * Plaćanje je simulirano (test režim) – vidi src/PlatniSistem.php.
 * Spisak promenljivih i imena polja forme su na vrhu dopuna_kartica.php.
 */
$naslovStrane = 'Plaćanje karticom';
$aktivnaStrana = 'dopuna.php';
$stilovi = ['css/dopuna.css'];
$skripte = ['js/dopuna_kartica.js'];
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/meni.php';

/** Bootstrap klasa za polje koje ima grešku */
$klasaPolja = fn(string $polje) => isset($greskePolja[$polje]) ? ' is-invalid' : '';
?>
  <main class="app-main app-main-wide">

    <header class="page-header d-flex justify-content-between align-items-start gap-3">
      <div>
        <a href="dopuna.php" class="back-link"><?php echo ikonica('nazad'); ?> Dopuna kartice</a>
        <h1 class="page-title">Plaćanje karticom</h1>
        <p class="page-date">Dopuna <?php echo Format::novac($iznos); ?> · simulirano plaćanje (test režim)</p>
      </div>
      <?php require __DIR__ . '/delovi/korisnik.php'; ?>
    </header>

    <?php if ($greska !== ''): ?>
      <div class="alert app-alert alert-danger mb-3 mb-lg-4" role="alert"><?php echo htmlspecialchars($greska); ?></div>
    <?php endif; ?>

    <div class="row g-3 g-lg-4 align-items-start">
      <div class="col-lg-7">
        <section class="panel pay-panel">
          <div class="panel-head mb-2">
            <h2>Podaci platne kartice</h2>
            <span class="tag tag-blue">Test režim</span>
          </div>
          <p class="accepted mb-3">
            Prihvatamo: <span class="tag tag-gray">Visa</span> <span class="tag tag-gray">Mastercard</span> <span class="tag tag-gray">DinaCard</span>
          </p>

          <form method="post" action="dopuna_kartica.php" id="formaPlacanja" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
            <input type="hidden" name="akcija" value="plati" />

            <label for="brojKartice" class="form-label">Broj kartice</label>
            <input type="text" inputmode="numeric" autocomplete="cc-number" class="form-control app-input<?php echo $klasaPolja('broj'); ?>"
                   id="brojKartice" name="broj_kartice" maxlength="23" placeholder="4242 4242 4242 4242"
                   aria-describedby="greskaBroj" />
            <div class="invalid-feedback" id="greskaBroj"><?php echo htmlspecialchars($greskePolja['broj'] ?? ''); ?></div>

            <label for="imeNaKartici" class="form-label mt-3">Ime i prezime na kartici</label>
            <input type="text" autocomplete="cc-name" class="form-control app-input<?php echo $klasaPolja('ime'); ?>"
                   id="imeNaKartici" name="ime_na_kartici" value="<?php echo htmlspecialchars($imeNaKartici); ?>"
                   placeholder="kao što piše na kartici" aria-describedby="greskaIme" />
            <div class="invalid-feedback" id="greskaIme"><?php echo htmlspecialchars($greskePolja['ime'] ?? ''); ?></div>

            <div class="row g-3 mt-0">
              <div class="col-6">
                <label for="istek" class="form-label">Datum isteka</label>
                <input type="text" inputmode="numeric" autocomplete="cc-exp" class="form-control app-input<?php echo $klasaPolja('istek'); ?>"
                       id="istek" name="istek" maxlength="5" placeholder="MM/GG" aria-describedby="greskaIstek" />
                <div class="invalid-feedback" id="greskaIstek"><?php echo htmlspecialchars($greskePolja['istek'] ?? ''); ?></div>
              </div>
              <div class="col-6">
                <label for="cvc" class="form-label">CVC</label>
                <input type="password" inputmode="numeric" autocomplete="cc-csc" class="form-control app-input<?php echo $klasaPolja('cvc'); ?>"
                       id="cvc" name="cvc" maxlength="3" placeholder="•••" aria-describedby="greskaCvc" />
                <div class="invalid-feedback" id="greskaCvc"><?php echo htmlspecialchars($greskePolja['cvc'] ?? ''); ?></div>
              </div>
            </div>

            <div class="note-box mt-4">
              <?php echo ikonica('stit'); ?>
              <p>
                U pravoj aplikaciji ovaj korak radi banka (3-D Secure) i podaci kartice nikad ne stižu do našeg servera.
                U projektu je plaćanje simulirano. Test kartice: <strong>4242 4242 4242 4242</strong> (prolazi),
                <strong>4000 0000 0000 0002</strong> (banka odbija).
              </p>
            </div>
          </form>

          <!-- Odustajanje je zasebna forma; dugme "Odustani" ispod je šalje preko atributa form -->
          <form method="post" action="dopuna_kartica.php" id="formaOdustani" class="d-none">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
            <input type="hidden" name="akcija" value="odustani" />
          </form>

          <div class="d-flex flex-wrap gap-2 gap-lg-3 mt-4">
            <button type="submit" form="formaPlacanja" class="btn btn-primary btn-app btn-app-lg flex-fill">
              Plati <?php echo Format::novac($iznos); ?>
            </button>
            <button type="submit" form="formaOdustani" class="btn btn-app btn-app-outline btn-app-lg pay-cancel">Odustani</button>
          </div>
        </section>
      </div>

      <div class="col-lg-5">
        <section class="panel">
          <h2 class="panel-title mb-1">Pregled uplate</h2>
          <div class="summary-row"><span>Iznos dopune</span><strong><?php echo Format::novac($iznos); ?></strong></div>
          <div class="summary-row"><span>Način plaćanja</span><strong>Platnom karticom</strong></div>
          <div class="summary-row"><span>Provizija</span><strong>0,00 RSD</strong></div>
          <div class="summary-total">
            <span>Novo stanje <span class="d-none d-lg-inline">posle uplate</span></span>
            <span class="amount amount-green"><?php echo Format::novac($novoStanje); ?></span>
          </div>
        </section>
      </div>
    </div>
  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
