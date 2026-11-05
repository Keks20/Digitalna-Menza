<?php
/**
 * PRIKAZ: prijava zaposlenih SC-a (administrator i kasir).
 *
 * PRIVREMENI IZGLED (Luka) – kolega ga menja dizajnom iz Figme (ekran "Admin-prijava").
 * Spisak promenljivih koje stižu iz admin_login.php je na vrhu tog fajla.
 * Polja forme moraju da se zovu: korisnicko_ime, lozinka, csrf_token.
 */
$naslovStrane = 'Prijava za zaposlene';
$klasaTela = 'bg-light';
require __DIR__ . '/delovi/zaglavlje.php';
?>
  <main class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="card shadow-sm w-100" style="max-width: 420px;">
      <div class="card-body p-4">
        <p class="fw-bold mb-0">Digitalna menza</p>
        <p class="small text-warning fw-semibold mb-4">ADMINISTRACIJA</p>

        <h1 class="h4 mb-1">Prijava za zaposlene</h1>
        <p class="text-muted small mb-4">Služba ishrane i kasiri Studentskog centra.</p>

        <form method="post" action="admin_login.php" novalidate>
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />

          <?php if ($greska !== ''): ?>
            <div class="alert alert-danger form-alert" role="alert"><?php echo htmlspecialchars($greska); ?></div>
          <?php endif; ?>

          <label for="korisnickoIme" class="form-label">Korisničko ime</label>
          <input type="text" class="form-control mb-3" id="korisnickoIme" name="korisnicko_ime"
                 value="<?php echo htmlspecialchars($korisnickoIme); ?>" autocomplete="username" autofocus />

          <label for="lozinka" class="form-label">Lozinka</label>
          <input type="password" class="form-control mb-4" id="lozinka" name="lozinka" autocomplete="current-password" />

          <button type="submit" class="btn btn-primary w-100">Prijavi se</button>
        </form>

        <p class="small text-muted text-center mt-4 mb-0">
          Student si? <a href="login.php">Prijava brojem kartice</a>
        </p>
      </div>
    </div>
  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
