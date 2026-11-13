<?php
/**
 * PRIKAZ: profil studenta.
 *
 * U Figmi nema posebnog ekrana – stranica je složena od postojećih delova (panel, summary-row, app-input, tag).
 * Levo: profilna slika, podaci studenta, kartica, kategorija i dom. Desno: kontakt podaci i promena lozinke.
 * Spisak promenljivih i imena polja formi su na vrhu profil.php.
 * Samo prikaz, bez upita u bazu; tekst iz baze se ispisuje kroz htmlspecialchars().
 */
$naslovStrane = 'Profil';
$aktivnaStrana = 'profil.php';
$stilovi = ['css/profil.css'];
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/meni.php';

$slika = $student->putanjaSlike();

/** Lista grešaka jedne forme */
$ispisGresaka = function (array $lista): void {
    if (empty($lista)) {
        return;
    }
    echo '<div class="alert app-alert alert-danger mb-3" role="alert">';
    foreach ($lista as $g) {
        echo '<div>' . htmlspecialchars($g) . '</div>';
    }
    echo '</div>';
};
?>
  <main class="app-main app-main-wide">

    <header class="page-header d-flex justify-content-between align-items-start gap-3">
      <div>
        <h1 class="page-title">Profil</h1>
        <p class="page-date">Tvoji podaci, kontakt, lozinka i profilna slika</p>
      </div>
      <?php require __DIR__ . '/delovi/korisnik.php'; ?>
    </header>

    <?php if ($poruka): ?>
      <div class="alert app-alert alert-<?php echo $poruka['tip'] === 'success' ? 'success' : 'danger'; ?> mb-3 mb-lg-4" role="alert"><?php echo htmlspecialchars($poruka['tekst']); ?></div>
    <?php endif; ?>

    <div class="row g-3 g-lg-4 align-items-start">

      <!-- LEVO: slika i podaci -->
      <div class="col-lg-5">
        <section class="panel profile-card mb-3 mb-lg-4">
          <div class="d-flex align-items-center gap-3">
            <?php if ($slika !== null): ?>
              <img src="<?php echo htmlspecialchars($slika); ?>" alt="Profilna slika" class="profile-photo" id="pregledSlike" width="96" height="96" />
            <?php else: ?>
              <span class="profile-photo profile-initials" id="pregledInicijali"><?php echo htmlspecialchars($student->inicijali()); ?></span>
              <img src="" alt="Profilna slika" class="profile-photo" id="pregledSlike" width="96" height="96" hidden />
            <?php endif; ?>
            <div class="min-w-0">
              <h2 class="profile-name"><?php echo htmlspecialchars($student->punoIme()); ?></h2>
              <p class="profile-sub"><?php echo htmlspecialchars($student->ustanova); ?></p>
              <p class="profile-sub">Indeks <?php echo htmlspecialchars($student->broj_indeksa); ?></p>
            </div>
          </div>

          <?php $ispisGresaka($greske['slika']); ?>

          <form method="post" action="profil.php" enctype="multipart/form-data" class="profile-photo-form" id="formaSlike">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
            <input type="hidden" name="akcija" value="slika" />
            <label for="slika" class="form-label">Profilna slika</label>
            <input type="file" class="form-control app-input profile-file" id="slika" name="slika"
                   accept="image/jpeg,image/png,image/webp" data-najvise-mb="<?php echo (int)$najviseSlikaMb; ?>" required />
            <p class="profile-hint" id="slikaNapomena">JPG, PNG ili WebP, najviše <?php echo (int)$najviseSlikaMb; ?> MB.</p>
            <div class="d-flex flex-wrap gap-2 mt-3">
              <button type="submit" class="btn btn-primary btn-app flex-fill"><?php echo ikonica('sacuvaj'); ?> Postavi sliku</button>
              <?php if ($slika !== null): ?>
                <button type="submit" form="formaUkloniSliku" class="btn btn-app btn-app-outline"><?php echo ikonica('kanta'); ?> Ukloni</button>
              <?php endif; ?>
            </div>
          </form>
          <?php if ($slika !== null): ?>
            <form method="post" action="profil.php" id="formaUkloniSliku" onsubmit="return confirm('Ukloniti profilnu sliku?');">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
              <input type="hidden" name="akcija" value="ukloni_sliku" />
            </form>
          <?php endif; ?>
        </section>

        <section class="panel">
          <h2 class="panel-title mb-1">Studentska kartica i kategorija</h2>
          <div class="summary-row"><span>Broj kartice</span><strong><?php echo htmlspecialchars($kartica->formatiranBroj()); ?></strong></div>
          <div class="summary-row"><span>Stanje</span><strong><?php echo Format::novac($kartica->stanje); ?></strong></div>
          <div class="summary-row">
            <span>Status kartice</span>
            <span><span class="tag ms-0 <?php echo $kartica->jeAktivna() ? 'tag-green' : 'tag-red'; ?>"><?php echo $kartica->jeAktivna() ? 'Aktivna' : 'Blokirana'; ?></span></span>
          </div>
          <div class="summary-row">
            <span>Kategorija</span>
            <strong><?php echo htmlspecialchars($student->nazivKategorije()); ?></strong>
          </div>
          <?php if ($ugovor !== null): ?>
            <div class="summary-row">
              <span>Dom i soba</span>
              <strong><?php echo htmlspecialchars($ugovor['dom'] . ', ' . $ugovor['paviljon'] . ' ' . $ugovor['soba']); ?></strong>
            </div>
            <div class="summary-row"><span>Školska godina</span><strong><?php echo htmlspecialchars($ugovor['skolska_godina']); ?></strong></div>
          <?php else: ?>
            <div class="summary-row"><span>Studentski dom</span><strong>Nema ugovor o smeštaju</strong></div>
          <?php endif; ?>

          <div class="note-box mt-3">
            <?php echo ikonica('info'); ?>
            <p>
              <?php echo htmlspecialchars($student->opisKategorije()); ?>
              Ime, indeks i kategoriju menja Studentski centar.
            </p>
          </div>
        </section>
      </div>

      <!-- DESNO: kontakt i lozinka -->
      <div class="col-lg-7">
        <section class="panel mb-3 mb-lg-4" id="kontakt">
          <h2 class="panel-title">Kontakt podaci</h2>
          <?php $ispisGresaka($greske['kontakt']); ?>

          <form method="post" action="profil.php#kontakt" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
            <input type="hidden" name="akcija" value="kontakt" />

            <div class="row g-3">
              <div class="col-md-6">
                <label for="email" class="form-label">E-adresa</label>
                <input type="email" class="form-control app-input" id="email" name="email" maxlength="100" autocomplete="email"
                       value="<?php echo htmlspecialchars($forma['email']); ?>" placeholder="ime@primer.rs" required />
              </div>
              <div class="col-md-6">
                <label for="telefon" class="form-label">Broj telefona</label>
                <input type="tel" class="form-control app-input" id="telefon" name="telefon" maxlength="20" autocomplete="tel" inputmode="tel"
                       value="<?php echo htmlspecialchars($forma['telefon']); ?>" placeholder="064 123 4567" />
              </div>
            </div>
            <p class="profile-hint">Na ove podatke Studentski centar šalje obaveštenja o kartici i smeštaju. Telefon nije obavezan.</p>

            <button type="submit" class="btn btn-primary btn-app mt-3"><?php echo ikonica('sacuvaj'); ?> Sačuvaj kontakt</button>
          </form>
        </section>

        <section class="panel" id="lozinka">
          <h2 class="panel-title">Promena lozinke</h2>
          <?php $ispisGresaka($greske['lozinka']); ?>

          <form method="post" action="profil.php#lozinka" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
            <input type="hidden" name="akcija" value="lozinka" />

            <div class="row g-3">
              <div class="col-12">
                <label for="staraLozinka" class="form-label">Trenutna lozinka</label>
                <input type="password" class="form-control app-input" id="staraLozinka" name="stara_lozinka"
                       autocomplete="current-password" required />
              </div>
              <div class="col-md-6">
                <label for="novaLozinka" class="form-label">Nova lozinka</label>
                <input type="password" class="form-control app-input" id="novaLozinka" name="nova_lozinka"
                       minlength="<?php echo (int)$najmanjeLozinka; ?>" autocomplete="new-password" required />
              </div>
              <div class="col-md-6">
                <label for="potvrdaLozinke" class="form-label">Ponovi novu lozinku</label>
                <input type="password" class="form-control app-input" id="potvrdaLozinke" name="potvrda_lozinke"
                       minlength="<?php echo (int)$najmanjeLozinka; ?>" autocomplete="new-password" required />
              </div>
            </div>
            <p class="profile-hint">Najmanje <?php echo (int)$najmanjeLozinka; ?> znakova. Lozinkom se prijavljuješ zajedno sa brojem kartice.</p>

            <button type="submit" class="btn btn-primary btn-app mt-3"><?php echo ikonica('brava'); ?> Promeni lozinku</button>
          </form>
        </section>
      </div>
    </div>

  </main>

  <script>
    // Pregled izabrane slike pre slanja i provera veličine (server sve ponovo proverava)
    (function () {
      const polje = document.getElementById('slika');
      const slika = document.getElementById('pregledSlike');
      const inicijali = document.getElementById('pregledInicijali');
      const napomena = document.getElementById('slikaNapomena');
      const najviseMb = parseInt(polje.dataset.najviseMb, 10);
      const pocetnaNapomena = napomena.textContent;

      polje.addEventListener('change', function () {
        const fajl = polje.files[0];
        napomena.textContent = pocetnaNapomena;
        napomena.classList.remove('text-danger');
        polje.setCustomValidity('');
        if (!fajl) {
          return;
        }
        if (fajl.size > najviseMb * 1024 * 1024) {
          napomena.textContent = 'Slika je prevelika – najviše ' + najviseMb + ' MB.';
          napomena.classList.add('text-danger');
          polje.setCustomValidity(napomena.textContent);
          return;
        }
        slika.src = URL.createObjectURL(fajl);
        slika.hidden = false;
        if (inicijali) {
          inicijali.hidden = true;
        }
      });
    })();
  </script>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
