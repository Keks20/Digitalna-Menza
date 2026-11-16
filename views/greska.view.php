<?php
/**
 * PRIKAZ: strana za greške (403, 404, 500).
 *
 * Promenljive koje dobija iz greska.php ili iz src/Greske.php:
 *   $kodGreske  int  403 | 404 | 500
 *
 * Stranica je samostalna (bez zaglavlja, menija, baze i sesije), da bi mogla da se prikaže
 * i kad ostatak aplikacije ne radi. Adrese stilova i linkova kreću od korena aplikacije ($osnova),
 * jer Apache ovu stranu prikazuje i za adrese u podfolderima.
 */
require_once __DIR__ . '/delovi/ikonice.php';

$TEKSTOVI = [
    403 => ['naslov' => 'Pristup nije dozvoljen', 'tekst' => 'Ova adresa nije predviđena za otvaranje u pregledaču.'],
    404 => ['naslov' => 'Stranica ne postoji', 'tekst' => 'Proveri adresu ili se vrati na početnu stranu.'],
    500 => ['naslov' => 'Nešto nije u redu', 'tekst' => 'Došlo je do greške na serveru. Pokušaj ponovo za par minuta.'],
];
$kodGreske = isset($TEKSTOVI[$kodGreske ?? 0]) ? (int)$kodGreske : 500;
$opisGreske = $TEKSTOVI[$kodGreske];

$osnova = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/';
?>
<!DOCTYPE html>
<html lang="sr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex" />
  <title><?php echo htmlspecialchars($opisGreske['naslov']); ?> – Digitalna menza</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="<?php echo htmlspecialchars($osnova); ?>css/style.css" />
  <link rel="stylesheet" href="<?php echo htmlspecialchars($osnova); ?>css/greska.css" />
</head>
<body class="error-page">
  <main class="error-box panel text-center">
    <span class="tint-box <?php echo $kodGreske === 500 ? 'tint-orange' : 'tint-blue'; ?> mx-auto">
      <?php echo ikonica($kodGreske === 403 ? 'brava' : 'upozorenje'); ?>
    </span>
    <p class="error-code">Greška <?php echo $kodGreske; ?></p>
    <h1 class="error-title"><?php echo htmlspecialchars($opisGreske['naslov']); ?></h1>
    <p class="error-text"><?php echo htmlspecialchars($opisGreske['tekst']); ?></p>
    <a href="<?php echo htmlspecialchars($osnova); ?>index.php" class="btn btn-primary btn-app"><?php echo ikonica('pocetna'); ?> Nazad na početnu</a>
  </main>
</body>
</html>
