<?php
/**
 * Meni za zaposlene SC-a (bočni meni "ADMINISTRACIJA" iz Figme).
 *
 * Stranica pre uključivanja postavlja:
 *   $aktivnaStrana – naziv fajla trenutne stranice, npr. 'admin_pocetna.php'
 * Koristi i $admin (iz includes/provera_admina.php) da kasiru prikaže samo njegove stavke.
 *
 * Stavka je link tek kad njena stranica postoji.
 * Privremeni izgled – po Figmi ide bočni meni na računaru i donja traka na telefonu;
 * niz $stavkeMenija ostaje isti.
 */
$aktivnaStrana = $aktivnaStrana ?? '';

$stavkeMenija = [
    ['fajl' => 'admin_pocetna.php',  'naziv' => 'Pregled',              'uloge' => ['admin']],
    ['fajl' => 'admin_jelovnik.php', 'naziv' => 'Jelovnik i cene',      'uloge' => ['admin']],
    ['fajl' => 'admin_uplate.php',   'naziv' => 'Uplate i obaveštenja', 'uloge' => ['admin']],
    ['fajl' => 'kasir_bonovi.php',   'naziv' => 'Provera bonova',       'uloge' => ['admin', 'kasir']],
    ['fajl' => 'admin_smestaj.php',  'naziv' => 'Smeštaj',              'uloge' => ['admin']],
    ['fajl' => 'admin_studenti.php', 'naziv' => 'Studenti',             'uloge' => ['admin']],
];
?>
<nav class="bg-dark mb-4">
  <div class="container d-flex flex-wrap align-items-center gap-1 py-2" style="max-width: 1100px;">
    <span class="fw-bold text-white me-1">Digitalna menza</span>
    <span class="small text-warning me-3">ADMINISTRACIJA</span>
    <?php foreach ($stavkeMenija as $stavka): ?>
      <?php if (!in_array($admin->uloga, $stavka['uloge'], true)) continue; ?>
      <?php if (file_exists(__DIR__ . '/../../' . $stavka['fajl'])): ?>
        <a href="<?php echo $stavka['fajl']; ?>"
           class="btn btn-sm <?php echo $stavka['fajl'] === $aktivnaStrana ? 'btn-primary' : 'btn-outline-light'; ?>">
          <?php echo $stavka['naziv']; ?>
        </a>
      <?php else: ?>
        <span class="btn btn-sm btn-outline-light disabled" title="Stranica još nije napravljena"><?php echo $stavka['naziv']; ?></span>
      <?php endif; ?>
    <?php endforeach; ?>
    <span class="ms-auto text-white small me-2">
      <?php echo htmlspecialchars($admin->ime_prezime); ?> · <?php echo $admin->nazivUloge(); ?>
    </span>
    <a href="admin_logout.php" class="btn btn-sm btn-light">Odjava</a>
  </div>
</nav>
