<?php
/**
 * Meni za zaposlene SC-a (po Figmi: bočni meni "ADMINISTRACIJA" na računaru,
 * donja traka na telefonu).
 *
 * Stranica pre uključivanja postavlja:
 *   $aktivnaStrana – naziv fajla trenutne stranice, npr. 'admin_pocetna.php'
 * Koristi i $admin (iz includes/provera_admina.php) da kasiru prikaže samo njegove stavke.
 *
 * Stavka je link tek kad njena stranica postoji (inače je zatamnjena).
 */
require_once __DIR__ . '/ikonice.php';

$aktivnaStrana = $aktivnaStrana ?? '';

// 'traka' = kratak naziv u donjoj traci na telefonu (null = samo u bočnom meniju)
$stavkeMenija = [
    ['fajl' => 'admin_pocetna.php',  'naziv' => 'Pregled',              'ikonica' => 'pregled',  'traka' => 'Pregled',  'uloge' => ['admin']],
    ['fajl' => 'admin_jelovnik.php', 'naziv' => 'Jelovnik i cene',      'ikonica' => 'kalendar', 'traka' => 'Jelovnik', 'uloge' => ['admin']],
    ['fajl' => 'admin_uplate.php',   'naziv' => 'Uplate i obaveštenja', 'ikonica' => 'novac',    'traka' => 'Uplate',   'uloge' => ['admin']],
    ['fajl' => 'kasir_bonovi.php',   'naziv' => 'Provera bonova',       'ikonica' => 'qr',       'traka' => 'Bonovi',   'uloge' => ['admin', 'kasir']],
    ['fajl' => 'admin_smestaj.php',  'naziv' => 'Smeštaj',              'ikonica' => 'kuca',     'traka' => null,       'uloge' => ['admin']],
    ['fajl' => 'admin_studenti.php', 'naziv' => 'Studenti',             'ikonica' => 'studenti', 'traka' => null,       'uloge' => ['admin']],
];
$stavkeMenija = array_values(array_filter($stavkeMenija, fn($s) => in_array($admin->uloga, $s['uloge'], true)));

/** Jedna stavka: link ako stranica postoji, inače zatamnjen tekst. */
$stavkaAdminMenija = function (array $stavka, string $klasa, string $tekst) use ($aktivnaStrana): string {
    $postoji = file_exists(__DIR__ . '/../../' . $stavka['fajl']);
    $aktivna = $stavka['fajl'] === $aktivnaStrana;
    $sadrzaj = ikonica($stavka['ikonica']) . '<span>' . htmlspecialchars($tekst) . '</span>';

    if (!$postoji) {
        return '<span class="' . $klasa . ' disabled" title="Stranica još nije napravljena">' . $sadrzaj . '</span>';
    }
    return '<a href="' . $stavka['fajl'] . '" class="' . $klasa . ($aktivna ? ' active' : '') . '"'
        . ($aktivna ? ' aria-current="page"' : '') . '>' . $sadrzaj . '</a>';
};
?>
  <!-- BOČNI MENI (računar) -->
  <nav class="app-sidebar d-none d-lg-flex flex-column" aria-label="Meni administracije">
    <a href="<?php echo $admin->jeAdmin() ? 'admin_pocetna.php' : 'kasir_bonovi.php'; ?>" class="sidebar-brand d-flex align-items-center">
      <span class="sidebar-logo"><?php echo ikonica('solja'); ?></span>
      <span class="d-flex flex-column">
        <span>Digitalna menza</span>
        <span class="sidebar-role">Administracija</span>
      </span>
    </a>

    <div class="d-flex flex-column gap-1">
      <?php foreach ($stavkeMenija as $stavka): ?>
        <?php echo $stavkaAdminMenija($stavka, 'sidebar-link', $stavka['naziv']); ?>
      <?php endforeach; ?>
    </div>

    <div class="mt-auto d-flex flex-column gap-1">
      <a href="admin_logout.php" class="sidebar-link"><?php echo ikonica('odjava'); ?><span>Odjava</span></a>
    </div>
  </nav>

  <!-- DONJA TRAKA (telefon) -->
  <nav class="app-tabbar d-flex d-lg-none" aria-label="Meni administracije">
    <?php foreach ($stavkeMenija as $stavka): ?>
      <?php if ($stavka['traka'] !== null): ?>
        <?php echo $stavkaAdminMenija($stavka, 'tabbar-link', $stavka['traka']); ?>
      <?php endif; ?>
    <?php endforeach; ?>
    <a href="admin_logout.php" class="tabbar-link"><?php echo ikonica('odjava'); ?><span>Odjava</span></a>
  </nav>
