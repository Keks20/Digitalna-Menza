<?php
/**
 * PRIKAZ: prijava zaposlenih SC-a – administrator i kasir (izgled po Figmi – ekran "Admin-prijava").
 *
 * Spisak promenljivih koje stižu iz admin_login.php je na vrhu tog fajla.
 * Polja forme moraju da se zovu: korisnicko_ime, lozinka, csrf_token.
 */
$naslovStrane = 'Prijava za osoblje';
$skripte = ['js/admin_login.js'];
require __DIR__ . '/delovi/zaglavlje.php';
require_once __DIR__ . '/delovi/ikonice.php';
?>
  <main class="container-fluid p-0">
    <div class="row g-0 min-vh-100">

      <!-- LEVA STRANA (vidi se samo na većim ekranima) -->
      <section class="col-lg hero d-none d-lg-flex flex-column">
        <div class="brand d-flex align-items-center gap-3">
          <span class="icon-box"><?php echo ikonica('solja'); ?></span>
          <span class="brand-name">Digitalna menza</span>
          <span class="tag tag-orange tag-staff">Osoblje</span>
        </div>

        <div class="hero-content">
          <h1>Administracija menze i Studentskog centra.</h1>
          <p class="hero-text">
            Unos jelovnika i cena, pregled uplata i obaveštenja za studente — na jednom mestu.
          </p>

          <ul class="features list-unstyled d-flex flex-column">
            <li class="d-flex align-items-center">
              <span class="icon-box"><?php echo ikonica('novac'); ?></span>
              Pregled uplata i prodatih obroka
            </li>
            <li class="d-flex align-items-center">
              <span class="icon-box"><?php echo ikonica('qr'); ?></span>
              Provera QR bonova na kasi
            </li>
            <li class="d-flex align-items-center">
              <span class="icon-box"><?php echo ikonica('kalendar'); ?></span>
              Unos jelovnika i cena obroka
            </li>
            <li class="d-flex align-items-center">
              <span class="icon-box"><?php echo ikonica('megafon'); ?></span>
              Obaveštenja studentima
            </li>
          </ul>
        </div>

        <footer class="hero-footer mt-auto">
          Studentski centar Kragujevac · prototip za predmet Inovacije u IT-u
        </footer>
      </section>

      <!-- DESNA STRANA -->
      <section class="col-12 col-lg form-side d-flex justify-content-center align-items-start align-items-lg-center">
        <div class="form-wrapper w-100">

          <!-- Logo iznad forme, samo na telefonu -->
          <div class="brand brand-mobile d-flex d-lg-none align-items-center gap-3">
            <span class="icon-box"><?php echo ikonica('solja'); ?></span>
            <span class="brand-name">Digitalna menza</span>
            <span class="tag tag-orange tag-staff">Osoblje</span>
          </div>

          <h2>Prijava za osoblje</h2>
          <p class="subtitle">Pristup imaju samo zaposleni u službi ishrane i SC-u.</p>

          <form id="adminLoginForm" method="post" action="admin_login.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />

            <?php if ($greska !== ''): ?>
              <div class="alert alert-danger form-alert" role="alert"><?php echo htmlspecialchars($greska); ?></div>
            <?php endif; ?>

            <label for="korisnickoIme" class="form-label">Korisničko ime</label>
            <div class="field">
              <?php echo ikonica('zaposleni', 'field-icon'); ?>
              <input type="text" class="form-control" id="korisnickoIme" name="korisnicko_ime"
                     aria-describedby="korisnickoImeGreska" value="<?php echo htmlspecialchars($korisnickoIme); ?>"
                     placeholder="npr. ishrana.ekonomski" autocomplete="username" autofocus />
              <div class="invalid-feedback" id="korisnickoImeGreska"></div>
            </div>

            <label for="lozinka" class="form-label">Lozinka</label>
            <div class="field">
              <?php echo ikonica('brava', 'field-icon'); ?>
              <input type="password" class="form-control" id="lozinka" name="lozinka"
                     aria-describedby="lozinkaGreska" placeholder="••••••••" autocomplete="current-password" />
              <button type="button" class="toggle-password" id="prikaziLozinku" aria-label="Prikaži lozinku">
                <?php echo ikonica('oko'); ?>
              </button>
              <div class="invalid-feedback" id="lozinkaGreska"></div>
            </div>

            <button type="submit" class="btn btn-primary btn-login w-100 d-flex align-items-center justify-content-center gap-2 mt-2">
              Prijavi se <?php echo ikonica('napred'); ?>
            </button>
          </form>

          <div class="info-box d-flex">
            <?php echo ikonica('info'); ?>
            <p class="mb-0">
              Nalog za osoblje otvara administrator SC-a. Student se prijavljuje na
              <a href="login.php" class="link">studentskoj prijavi</a>.
            </p>
          </div>
        </div>
      </section>

    </div>
  </main>

<?php require __DIR__ . '/delovi/podnozje.php'; ?>
