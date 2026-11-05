<?php
/**
 * PRIKAZ: administratorski pregled.
 *
 * Privremeni raspored – konačni izgled sa statistikom je ekran "Admin" u Figmi.
 * Spisak promenljivih je na vrhu admin_pocetna.php.
 */
$naslovStrane = 'Pregled';
$klasaTela = 'bg-light';
$aktivnaStrana = 'admin_pocetna.php';
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/admin_meni.php';
?>
  <main class="container pb-4" style="max-width: 1100px;">
    <h1 class="h3 mb-1">Pregled</h1>
    <p class="text-muted"><?php echo Format::danSaDatumom($danas); ?></p>

    <div class="card">
      <div class="card-body">
        <p class="mb-1">Zdravo, <strong><?php echo htmlspecialchars($admin->ime_prezime); ?></strong>.</p>
        <p class="text-muted mb-0">
          Ovde će biti statistika: uplate danas, prodati i izdati obroci po menzama i poslednje uplate.
          Za sada iz menija otvori stranice koje su već napravljene.
        </p>
      </div>
    </div>
  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
