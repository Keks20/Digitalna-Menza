<?php
/**
 * Početak svake stranice: <head> sa fontom, Bootstrap-om i našim stilovima.
 *
 * Stranica pre uključivanja može da postavi:
 *   $naslovStrane  – tekst u kartici pregledača, npr. 'Jelovnik'
 *   $klasaTela     – klasa za <body>, npr. 'bg-light' (nije obavezno)
 *   $stilovi       – dodatni CSS fajlovi te stranice, npr. ['css/pocetna.css'] (nije obavezno)
 *
 * Upotreba u view fajlu:
 *   <?php require __DIR__ . '/delovi/zaglavlje.php'; ?>
 */
$naslovStrane = $naslovStrane ?? 'Digitalna menza';
$klasaTela = $klasaTela ?? '';
$stilovi = $stilovi ?? [];
?>
<!DOCTYPE html>
<html lang="sr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($naslovStrane); ?> – Digitalna menza</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Naši stilovi idu POSLE Bootstrap-a da bi mogli da ga menjaju -->
  <link rel="stylesheet" href="css/style.css" />
<?php foreach ($stilovi as $stil): ?>
  <link rel="stylesheet" href="<?php echo htmlspecialchars($stil); ?>" />
<?php endforeach; ?>
</head>
<body<?php echo $klasaTela !== '' ? ' class="' . htmlspecialchars($klasaTela) . '"' : ''; ?>>
