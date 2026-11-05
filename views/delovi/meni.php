<?php
/**
 * Glavni meni studenta (po Figmi):
 *   – računar: tamni bočni meni levo
 *   – telefon: donja traka sa 4 glavne stavke + "Još" (ostalo i odjava)
 *
 * Stranica pre uključivanja postavlja:
 *   $aktivnaStrana – naziv fajla trenutne stranice, npr. 'jelovnik.php'
 *
 * Stavka je link tek kad njena stranica postoji u korenu projekta, pa meni
 * nikad ne vodi na stranicu koja još nije napravljena (takva je zatamnjena).
 */
require_once __DIR__ . '/ikonice.php';

$aktivnaStrana = $aktivnaStrana ?? '';

// 'traka' = kratak naziv u donjoj traci na telefonu (null = stavka je pod "Još")
$stavkeMenija = [
    ['fajl' => 'pocetna.php',  'naziv' => 'Početna',         'ikonica' => 'pocetna',  'traka' => 'Početna'],
    ['fajl' => 'dopuna.php',   'naziv' => 'Dopuna kartice',  'ikonica' => 'novcanik', 'traka' => 'Dopuna'],
    ['fajl' => 'kupovina.php', 'naziv' => 'Kupovina obroka', 'ikonica' => 'korpa',    'traka' => null],
    ['fajl' => 'obroci.php',   'naziv' => 'Moji obroci',     'ikonica' => 'qr',       'traka' => 'Obroci'],
    ['fajl' => 'jelovnik.php', 'naziv' => 'Jelovnik',        'ikonica' => 'kalendar', 'traka' => 'Jelovnik'],
    ['fajl' => 'smestaj.php',  'naziv' => 'Smeštaj',         'ikonica' => 'kuca',     'traka' => null],
    ['fajl' => 'istorija.php', 'naziv' => 'Istorija',        'ikonica' => 'istorija', 'traka' => null],
];
$profil = ['fajl' => 'profil.php', 'naziv' => 'Profil', 'ikonica' => 'korisnik', 'traka' => null];

/** Jedna stavka: link ako stranica postoji, inače zatamnjen tekst. */
$stavkaMenija = function (array $stavka, string $klasa, string $tekst) use ($aktivnaStrana): string {
    $postoji = file_exists(__DIR__ . '/../../' . $stavka['fajl']);
    $aktivna = $stavka['fajl'] === $aktivnaStrana;
    $sadrzaj = ikonica($stavka['ikonica']) . '<span>' . htmlspecialchars($tekst) . '</span>';

    if (!$postoji) {
        return '<span class="' . $klasa . ' disabled" title="Stranica još nije napravljena">' . $sadrzaj . '</span>';
    }
    return '<a href="' . $stavka['fajl'] . '" class="' . $klasa . ($aktivna ? ' active' : '') . '"'
        . ($aktivna ? ' aria-current="page"' : '') . '>' . $sadrzaj . '</a>';
};

$ostaleStavke = array_filter($stavkeMenija, fn($s) => $s['traka'] === null);
$ostaloAktivno = in_array($aktivnaStrana, array_column([...$ostaleStavke, $profil], 'fajl'), true);
?>
  <!-- BOČNI MENI (računar) -->
  <nav class="app-sidebar d-none d-lg-flex flex-column" aria-label="Glavni meni">
    <a href="pocetna.php" class="sidebar-brand d-flex align-items-center">
      <span class="sidebar-logo"><?php echo ikonica('solja'); ?></span>
      <span>Digitalna menza</span>
    </a>

    <div class="d-flex flex-column gap-1">
      <?php foreach ($stavkeMenija as $stavka): ?>
        <?php echo $stavkaMenija($stavka, 'sidebar-link', $stavka['naziv']); ?>
      <?php endforeach; ?>
    </div>

    <div class="mt-auto d-flex flex-column gap-1">
      <?php echo $stavkaMenija($profil, 'sidebar-link', $profil['naziv']); ?>
      <a href="logout.php" class="sidebar-link"><?php echo ikonica('odjava'); ?><span>Odjava</span></a>
    </div>
  </nav>

  <!-- DONJA TRAKA (telefon) -->
  <nav class="app-tabbar d-flex d-lg-none" aria-label="Glavni meni">
    <?php foreach ($stavkeMenija as $stavka): ?>
      <?php if ($stavka['traka'] !== null): ?>
        <?php echo $stavkaMenija($stavka, 'tabbar-link', $stavka['traka']); ?>
      <?php endif; ?>
    <?php endforeach; ?>

    <div class="dropup tabbar-more">
      <button type="button" class="tabbar-link<?php echo $ostaloAktivno ? ' active' : ''; ?>"
              data-bs-toggle="dropdown" aria-expanded="false">
        <?php echo ikonica('meni'); ?><span>Još</span>
      </button>
      <div class="dropdown-menu dropdown-menu-end tabbar-dropdown">
        <?php foreach ([...$ostaleStavke, $profil] as $stavka): ?>
          <?php echo $stavkaMenija($stavka, 'dropdown-item', $stavka['naziv']); ?>
        <?php endforeach; ?>
        <div class="dropdown-divider"></div>
        <a href="logout.php" class="dropdown-item"><?php echo ikonica('odjava'); ?><span>Odjava</span></a>
      </div>
    </div>
  </nav>
