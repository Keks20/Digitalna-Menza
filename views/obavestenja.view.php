<?php
/**
 * PRIKAZ: sva obaveštenja za studenta ("Sva obaveštenja" iz zvonceta).
 *
 * U Figmi nema posebnog ekrana – kartice prate izgled poruka u aplikaciji.
 * Spisak promenljivih je na vrhu obavestenja.php.
 * Samo prikaz, bez upita u bazu; tekst iz baze se ispisuje kroz htmlspecialchars().
 */
$naslovStrane = 'Obaveštenja';
$aktivnaStrana = 'obavestenja.php';
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/meni.php';
require_once __DIR__ . '/delovi/obavestenja_izgled.php';
?>
  <main class="app-main">

    <header class="page-header d-flex justify-content-between align-items-start gap-3">
      <div>
        <h1 class="page-title">Obaveštenja</h1>
        <p class="page-date">Poruke Studentskog centra, menzi i tvog doma</p>
      </div>
      <?php require __DIR__ . '/delovi/korisnik.php'; ?>
    </header>

    <section class="panel">
      <div class="panel-head">
        <h2>Sve poruke</h2>
        <a href="pocetna.php" class="panel-link"><?php echo ikonica('levo'); ?> Početna</a>
      </div>

      <?php foreach ($poruke as $poruka): ?>
        <?php $izgled = izgledObavestenja($poruka); ?>
        <article class="message d-flex<?php echo !empty($poruka['novo']) ? ' is-new' : ''; ?>">
          <span class="tint-box <?php echo $izgled['boja']; ?>"><?php echo ikonica($izgled['ikonica']); ?></span>
          <div>
            <h3 class="message-title">
              <?php echo htmlspecialchars($poruka['naslov']); ?>
              <?php if (!empty($poruka['novo'])): ?><span class="tag tag-orange">Novo</span><?php endif; ?>
            </h3>
            <p class="message-text"><?php echo nl2br(htmlspecialchars($poruka['tekst'])); ?></p>
            <p class="message-meta">
              <?php echo Format::datumVreme($poruka['kreirano']); ?> ·
              <?php echo htmlspecialchars($izgled['kome']); ?>
            </p>
          </div>
        </article>
      <?php endforeach; ?>

      <?php if (empty($poruke)): ?>
        <p class="message-text mb-0">Trenutno nema obaveštenja.</p>
      <?php endif; ?>
    </section>

  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
