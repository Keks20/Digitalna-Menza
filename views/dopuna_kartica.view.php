<?php
/**
 * PRIKAZ: dopuna kartice – plaćanje platnom karticom.
 *
 * Privremeni raspored – konačni izgled je ekran "Dopuna-kartica" u Figmi.
 * Spisak promenljivih i imena polja forme su na vrhu dopuna_kartica.php.
 */
$naslovStrane = 'Plaćanje karticom';
$klasaTela = 'bg-light';
$aktivnaStrana = 'dopuna.php';
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/meni.php';

/** Bootstrap klasa i poruka ispod polja koje ima grešku */
$klasaPolja = fn(string $polje) => isset($greskePolja[$polje]) ? ' is-invalid' : '';
?>
  <main class="container py-4" style="max-width: 1000px;">
    <a href="dopuna.php" class="small">&larr; Dopuna kartice</a>
    <h1 class="h3 mb-3 mt-1">Plaćanje karticom</h1>

    <?php if ($greska !== ''): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($greska); ?></div>
    <?php endif; ?>

    <div class="row g-3">
      <div class="col-lg-7">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <p class="fw-bold">Podaci platne kartice</p>
              <span class="badge text-bg-warning align-self-start">Test režim</span>
            </div>

            <form method="post" action="dopuna_kartica.php" novalidate>
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
              <input type="hidden" name="akcija" value="plati" />

              <label for="brojKartice" class="form-label small fw-semibold">Broj kartice</label>
              <input type="text" inputmode="numeric" autocomplete="cc-number" class="form-control<?php echo $klasaPolja('broj'); ?>"
                     id="brojKartice" name="broj_kartice" maxlength="23" placeholder="4242 4242 4242 4242" />
              <div class="invalid-feedback"><?php echo htmlspecialchars($greskePolja['broj'] ?? ''); ?></div>

              <label for="imeNaKartici" class="form-label small fw-semibold mt-3">Ime i prezime na kartici</label>
              <input type="text" autocomplete="cc-name" class="form-control<?php echo $klasaPolja('ime'); ?>"
                     id="imeNaKartici" name="ime_na_kartici" value="<?php echo htmlspecialchars($imeNaKartici); ?>" />
              <div class="invalid-feedback"><?php echo htmlspecialchars($greskePolja['ime'] ?? ''); ?></div>

              <div class="row g-2 mt-1">
                <div class="col-6">
                  <label for="istek" class="form-label small fw-semibold">Datum isteka</label>
                  <input type="text" inputmode="numeric" autocomplete="cc-exp" class="form-control<?php echo $klasaPolja('istek'); ?>"
                         id="istek" name="istek" maxlength="5" placeholder="MM/GG" />
                  <div class="invalid-feedback"><?php echo htmlspecialchars($greskePolja['istek'] ?? ''); ?></div>
                </div>
                <div class="col-6">
                  <label for="cvc" class="form-label small fw-semibold">CVC</label>
                  <input type="password" inputmode="numeric" autocomplete="cc-csc" class="form-control<?php echo $klasaPolja('cvc'); ?>"
                         id="cvc" name="cvc" maxlength="3" placeholder="•••" />
                  <div class="invalid-feedback"><?php echo htmlspecialchars($greskePolja['cvc'] ?? ''); ?></div>
                </div>
              </div>

              <p class="small text-muted mt-3">
                U pravoj aplikaciji ovaj korak radi banka (3-D Secure) i podaci kartice nikad ne stižu do našeg servera.
                Test kartice: 4242 4242 4242 4242 (prolazi), 4000 0000 0000 0002 (banka odbija).
              </p>
              <button type="submit" class="btn btn-primary w-100">Plati <?php echo Format::novac($iznos); ?></button>
            </form>

            <form method="post" action="dopuna_kartica.php" class="mt-2">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
              <input type="hidden" name="akcija" value="odustani" />
              <button type="submit" class="btn btn-outline-secondary w-100">Odustani</button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="card">
          <div class="card-body">
            <p class="fw-bold">Pregled uplate</p>
            <div class="d-flex justify-content-between"><span class="text-muted">Iznos dopune</span><strong><?php echo Format::novac($iznos); ?></strong></div>
            <div class="d-flex justify-content-between"><span class="text-muted">Način plaćanja</span><span>Platnom karticom</span></div>
            <div class="d-flex justify-content-between"><span class="text-muted">Provizija</span><span>0,00 RSD</span></div>
            <hr />
            <div class="d-flex justify-content-between"><span>Novo stanje posle uplate</span><strong><?php echo Format::novac($novoStanje); ?></strong></div>
          </div>
        </div>
      </div>
    </div>
  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
