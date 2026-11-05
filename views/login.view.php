<?php
/**
 * PRIKAZ: prijava studenta (izgled po Figmi, ekran "Prijava").
 *
 * Promenljive koje dobija iz login.php:
 *   $greska       string  poruka o grešci posle neuspešne prijave ('' ako je nema)
 *   $brojKartice  string  broj kartice koji je student upisao (da ne kuca ponovo)
 *   $csrfToken    string  skriveno polje csrf_token u formi
 */
$naslovStrane = 'Prijava';
$skripte = ['js/login.js'];
require __DIR__ . '/delovi/zaglavlje.php';
?>
  <main class="container-fluid p-0">
    <div class="row g-0 min-vh-100">

      <!-- LEVA STRANA (vidi se samo na većim ekranima) -->
      <section class="col-lg hero d-none d-lg-flex flex-column">
        <div class="brand d-flex align-items-center gap-3">
          <span class="icon-box">
            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M10 2v2M14 2v2M6 2v2M16 8a1 1 0 0 1 1 1v8a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V9a1 1 0 0 1 1-1z"/><path d="M17 12h1.5a2.5 2.5 0 0 1 0 5H17"/></svg>
          </span>
          <span class="brand-name">Digitalna menza</span>
        </div>

        <div class="hero-content">
          <h1>Menza, kartica i dom&nbsp;—<br />na jednom mestu.</h1>
          <p class="hero-text">
            Dopuni karticu, kupi obrok i plati smeštaj bez čekanja u redu na šalteru.
          </p>

          <ul class="features list-unstyled d-flex flex-column">
            <li class="d-flex align-items-center">
              <span class="icon-box">
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/></svg>
              </span>
              Stanje kartice i dopuna u par klikova
            </li>
            <li class="d-flex align-items-center">
              <span class="icon-box">
                <svg aria-hidden="true" viewBox="0 0 24 24"><rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/><rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3M21 21v.01M12 7v3a2 2 0 0 1-2 2H7M3 12h.01M12 3h.01M12 16v.01M16 12h1M21 12v.01M12 21v-1"/></svg>
              </span>
              QR bon za obrok umesto skidanja na kasi
            </li>
            <li class="d-flex align-items-center">
              <span class="icon-box">
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M8 2v4M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"/></svg>
              </span>
              Dnevni i nedeljni jelovnik
            </li>
            <li class="d-flex align-items-center">
              <span class="icon-box">
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
              </span>
              Plaćanje stanarine za dom
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
            <span class="icon-box">
              <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M10 2v2M14 2v2M6 2v2M16 8a1 1 0 0 1 1 1v8a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V9a1 1 0 0 1 1-1z"/><path d="M17 12h1.5a2.5 2.5 0 0 1 0 5H17"/></svg>
            </span>
            <span class="brand-name">Digitalna menza</span>
          </div>

          <h2>Prijava</h2>
          <p class="subtitle">Prijavi se brojem studentske kartice i lozinkom.</p>

          <form id="loginForm" method="post" action="login.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />

            <?php if ($greska !== ''): ?>
              <div class="alert alert-danger form-alert" role="alert"><?php echo htmlspecialchars($greska); ?></div>
            <?php endif; ?>

            <label for="cardNumber" class="form-label">Broj kartice</label>
            <div class="field">
              <svg aria-hidden="true" class="field-icon" viewBox="0 0 24 24"><rect width="20" height="14" x="2" y="5" rx="2"/><path d="M6 15h4M6 11h.01M10 11h8"/></svg>
              <input type="text" class="form-control" id="cardNumber" name="broj_kartice" aria-describedby="cardError" value="<?php echo htmlspecialchars($brojKartice); ?>" placeholder="npr. 0012 3456 7890" inputmode="numeric" autocomplete="username" maxlength="14" />
              <div class="invalid-feedback" id="cardError"></div>
            </div>

            <label for="password" class="form-label">Lozinka</label>
            <div class="field">
              <svg aria-hidden="true" class="field-icon" viewBox="0 0 24 24"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              <input type="password" class="form-control" id="password" name="lozinka" aria-describedby="passwordError" placeholder="••••••••" autocomplete="current-password" />
              <button type="button" class="toggle-password" id="togglePassword" aria-label="Prikaži lozinku">
                <svg aria-hidden="true" id="eyeIcon" viewBox="0 0 24 24"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
              <div class="invalid-feedback" id="passwordError"></div>
            </div>

            <div class="form-row-options d-flex justify-content-between align-items-center">
              <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" id="remember" name="zapamti" />
                <label class="form-check-label" for="remember">Zapamti me</label>
              </div>
              <a href="#" class="link">Zaboravljena lozinka?</a>
            </div>

            <button type="submit" class="btn btn-primary btn-login w-100 d-flex align-items-center justify-content-center gap-2">
              Prijavi se
              <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </button>

            <div class="alert alert-success form-message d-none" id="formMessage" role="status"></div>
          </form>

          <div class="info-box d-flex">
            <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <p class="mb-0">
              Broj kartice je odštampan ispod čipa na tvojoj studentskoj kartici.
              Lozinku dobijaš na šalteru SC-a.
            </p>
          </div>
        </div>
      </section>

    </div>
  </main>

<?php require __DIR__ . '/delovi/podnozje.php'; ?>
