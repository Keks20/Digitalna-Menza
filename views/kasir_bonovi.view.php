<?php
/**
 * PRIKAZ: provera QR bonova na kasi.
 *
 * PRIVREMENI IZGLED (Luka) – skeniranje bona iz Figme (ekran "Kasir-provera-bonova") dolazi u nedelji 6.
 * Spisak promenljivih je na vrhu kasir_bonovi.php.
 */
$naslovStrane = 'Provera bonova';
$klasaTela = 'bg-light';
$aktivnaStrana = 'kasir_bonovi.php';
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/admin_meni.php';
?>
  <main class="container pb-4" style="max-width: 1100px;">
    <h1 class="h3 mb-3">Provera bonova</h1>
    <div class="card">
      <div class="card-body">
        <p class="mb-1">Zdravo, <strong><?php echo htmlspecialchars($admin->ime_prezime); ?></strong>.</p>
        <p class="text-muted mb-0">Ovde će kasir skenirati QR bon studenta i izdavati obrok.</p>
      </div>
    </div>
  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
