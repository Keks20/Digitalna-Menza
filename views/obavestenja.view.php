<?php
/**
 * PRIKAZ: sva obaveštenja za studenta.
 *
 * Privremeni raspored – u Figmi nema posebnog ekrana; izgled kartica može da prati
 * blok "Poruke studentima" sa početne.
 * Spisak promenljivih je na vrhu obavestenja.php.
 * Samo prikaz, bez upita u bazu; tekst iz baze se ispisuje kroz htmlspecialchars().
 */
$naslovStrane = 'Obaveštenja';
$klasaTela = 'bg-light';
$aktivnaStrana = 'obavestenja.php';
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/meni.php';
?>
  <main class="container py-4" style="max-width: 900px;">
    <h1 class="h3 mb-1">Obaveštenja</h1>
    <p class="text-muted">Poruke Studentskog centra, menzi i tvog doma</p>

    <?php if (empty($poruke)): ?>
      <div class="alert alert-secondary">Trenutno nema obaveštenja.</div>
    <?php endif; ?>

    <?php foreach ($poruke as $poruka): ?>
      <div class="card mb-2">
        <div class="card-body">
          <p class="small text-muted mb-1">
            <?php echo Format::datumVreme($poruka['kreirano']); ?> ·
            <?php if ($poruka['ciljna_grupa'] === 'menza'): ?>
              <?php echo htmlspecialchars($poruka['menza'] ?? 'Menza'); ?>
            <?php elseif ($poruka['ciljna_grupa'] === 'dom'): ?>
              <?php echo htmlspecialchars($poruka['dom']); ?>
            <?php else: ?>
              Svi studenti
            <?php endif; ?>
          </p>
          <p class="fw-semibold mb-1"><?php echo htmlspecialchars($poruka['naslov']); ?></p>
          <p class="mb-0"><?php echo nl2br(htmlspecialchars($poruka['tekst'])); ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
