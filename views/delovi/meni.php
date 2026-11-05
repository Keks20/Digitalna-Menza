<?php
/**
 * Glavni meni studenta (na računaru bočni meni, na telefonu donja traka – po Figmi).
 *
 * Stranica pre uključivanja postavlja:
 *   $aktivnaStrana – naziv fajla trenutne stranice, npr. 'jelovnik.php'
 *
 * Stavka se prikazuje kao link tek kad njena stranica postoji, pa meni
 * nikad ne vodi na stranicu koja još nije napravljena.
 *
 * Privremeni izgled – po Figmi ide bočni meni na računaru i donja traka na telefonu;
 * niz $stavkeMenija ostaje isti.
 */
$aktivnaStrana = $aktivnaStrana ?? '';

$stavkeMenija = [
    ['fajl' => 'pocetna.php',  'naziv' => 'Početna'],
    ['fajl' => 'dopuna.php',   'naziv' => 'Dopuna kartice'],
    ['fajl' => 'kupovina.php', 'naziv' => 'Kupovina obroka'],
    ['fajl' => 'obroci.php',   'naziv' => 'Moji obroci'],
    ['fajl' => 'jelovnik.php', 'naziv' => 'Jelovnik'],
    ['fajl' => 'smestaj.php',  'naziv' => 'Smeštaj'],
    ['fajl' => 'istorija.php', 'naziv' => 'Istorija'],
    ['fajl' => 'profil.php',   'naziv' => 'Profil'],
];
?>
<nav class="bg-white border-bottom mb-4">
  <div class="container d-flex flex-wrap align-items-center gap-1 py-2" style="max-width: 960px;">
    <span class="fw-bold me-3">Digitalna menza</span>
    <?php foreach ($stavkeMenija as $stavka): ?>
      <?php if (file_exists(__DIR__ . '/../../' . $stavka['fajl'])): ?>
        <a href="<?php echo $stavka['fajl']; ?>"
           class="btn btn-sm <?php echo $stavka['fajl'] === $aktivnaStrana ? 'btn-primary' : 'btn-light'; ?>">
          <?php echo $stavka['naziv']; ?>
        </a>
      <?php else: ?>
        <span class="btn btn-sm btn-light disabled" title="Stranica još nije napravljena"><?php echo $stavka['naziv']; ?></span>
      <?php endif; ?>
    <?php endforeach; ?>
    <a href="logout.php" class="btn btn-sm btn-outline-secondary ms-auto">Odjava</a>
  </div>
</nav>
