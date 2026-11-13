<?php
/**
 * PRIKAZ: dnevni i nedeljni jelovnik (izgled po Figmi – ekran "Jelovnik").
 *
 * Spisak promenljivih koje stižu iz jelovnik.php je na vrhu tog fajla.
 * Samo prikaz, bez upita u bazu; tekst iz baze se ispisuje kroz htmlspecialchars().
 */
$naslovStrane = 'Jelovnik';
$aktivnaStrana = 'jelovnik.php';
$stilovi = ['css/jelovnik.css'];
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/meni.php';

// Ikonica i boja za svaki obrok (po id-ju iz tabele tipovi_obroka) – isto kao na početnoj
$izgledObroka = [
    1 => ['ikonica' => 'solja', 'boja' => 'tint-orange'],  // Doručak
    2 => ['ikonica' => 'jaje',  'boja' => 'tint-blue'],    // Ručak
    3 => ['ikonica' => 'mesec', 'boja' => 'tint-green'],   // Večera
];
?>
  <main class="app-main">

    <!-- ZAGLAVLJE: naslov, nedelja sa strelicama i korisnik -->
    <header class="page-header jel-header">
      <h1 class="page-title jel-title">Jelovnik</h1>
      <div class="jel-week d-flex align-items-center gap-2">
        <p class="page-date">Nedelja <?php echo $nedeljaOpis; ?></p>
        <div class="week-nav d-flex gap-1 no-print">
          <a href="<?php echo htmlspecialchars($prethodnaNedeljaUrl); ?>" class="week-arrow" title="Prethodna nedelja" aria-label="Prethodna nedelja"><?php echo ikonica('levo'); ?></a>
          <a href="<?php echo htmlspecialchars($sledecaNedeljaUrl); ?>" class="week-arrow" title="Sledeća nedelja" aria-label="Sledeća nedelja"><?php echo ikonica('desno'); ?></a>
        </div>
      </div>
      <div class="jel-user">
        <?php require __DIR__ . '/delovi/korisnik.php'; ?>
      </div>
    </header>

    <!-- IZBOR: menza + preuzimanje, pa dani u nedelji -->
    <section class="panel picker mb-3 mb-lg-4">
      <div class="d-flex flex-wrap align-items-center gap-2 gap-lg-3 mb-3">
        <?php foreach ($menze as $menza): ?>
          <?php $kratko = preg_replace('/^Menza\s+/u', '', $menza['naziv']); ?>
          <a href="<?php echo htmlspecialchars($menza['url']); ?>"
             class="btn-choice<?php echo $menza['izabrana'] ? ' active' : ''; ?>"
             <?php echo $menza['izabrana'] ? 'aria-current="true"' : ''; ?>>
            <span><?php if ($kratko !== $menza['naziv']): ?><span class="d-none d-lg-inline">Menza </span><?php endif; ?><?php echo htmlspecialchars($kratko); ?></span>
          </a>
        <?php endforeach; ?>

        <!-- "PDF" = štampa stranice; u prozoru za štampu se bira "Sačuvaj kao PDF" -->
        <button type="button" class="btn-choice btn-pdf ms-lg-auto no-print" onclick="window.print()">
          <span class="d-lg-none"><?php echo ikonica('preuzmi'); ?> Nedeljni jelovnik (PDF)</span>
          <span class="d-none d-lg-inline">Preuzmi nedeljni jelovnik (PDF)</span>
        </button>
      </div>

      <nav class="day-strip d-flex" aria-label="Dani u nedelji">
        <?php foreach ($dani as $dan): ?>
          <a href="<?php echo htmlspecialchars($dan['url']); ?>"
             class="day-btn<?php echo $dan['izabran'] ? ' active' : ''; ?><?php echo $dan['danas'] ? ' today' : ''; ?>"
             <?php echo $dan['izabran'] ? 'aria-current="date"' : ''; ?>
             <?php echo $dan['danas'] ? 'title="Danas"' : ''; ?>>
            <span class="day-name"><?php echo $dan['dan']; ?></span>
            <span class="day-num"><?php echo $dan['broj']; ?></span>
          </a>
        <?php endforeach; ?>
      </nav>
    </section>

    <!-- OBROCI za izabrani dan (pri štampi se umesto njih vidi cela nedelja, ako je ima) -->
    <?php $samoEkran = !empty($jelovnikNedelja) ? ' d-print-none' : ''; ?>
    <?php if (!$imaJelovnika): ?>
      <section class="panel empty-state text-center mb-3 mb-lg-4<?php echo $samoEkran; ?>">
        <span class="tint-box tint-blue mx-auto"><?php echo ikonica('kalendar'); ?></span>
        <p class="empty-title">Jelovnik još nije objavljen</p>
        <p class="empty-text">
          <?php echo htmlspecialchars($izabranaMenza['naziv']); ?> još nije objavila ponudu za
          <?php echo mb_strtolower(Format::danSaDatumom($izabraniDan)); ?> Pogledaj drugi dan ili drugu menzu.
        </p>
      </section>
    <?php else: ?>
      <div class="row g-3 g-lg-4 mb-3 mb-lg-4<?php echo $samoEkran; ?>">
        <?php foreach ($tipoviObroka as $tip): ?>
          <?php
            $izgled = $izgledObroka[(int)$tip->id] ?? ['ikonica' => 'solja', 'boja' => 'tint-blue'];
            $stavke = $jelovnik[$tip->id] ?? [];
          ?>
          <div class="col-lg-4">
            <section class="panel meal-card h-100">
              <div class="meal-card-head d-flex align-items-center">
                <span class="tint-box <?php echo $izgled['boja']; ?>"><?php echo ikonica($izgled['ikonica']); ?></span>
                <div class="flex-fill">
                  <h2 class="meal-name"><?php echo htmlspecialchars($tip->naziv); ?></h2>
                  <p class="meal-hours"><?php echo $tip->radnoVreme(); ?></p>
                </div>
                <span class="meal-price"><?php echo Format::cena($student->cenaObroka($tip->cena)); ?></span>
              </div>

              <ul class="dish-list">
                <?php foreach ($stavke as $stavka): ?>
                  <li>
                    <span class="dish-name"><?php echo htmlspecialchars($stavka->jelo); ?></span>
                    <?php if ($stavka->posno): ?><span class="tag tag-green">Posno</span><?php endif; ?>
                  </li>
                <?php endforeach; ?>
                <?php if (!$stavke): ?>
                  <li class="dish-empty">Nema ponude za ovaj obrok.</li>
                <?php endif; ?>
              </ul>
            </section>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($jelovnikNedelja)): ?>
      <!-- Samo za štampu / PDF: cela nedelja izabrane menze.
           Radi kad jelovnik.php pošalje $jelovnikNedelja (JelovnikRepository::zaNedelju). -->
      <section class="print-week d-none d-print-block mb-3">
        <h2 class="print-week-title">
          <?php echo htmlspecialchars($izabranaMenza['naziv']); ?> · nedelja <?php echo $nedeljaOpis; ?>
        </h2>
        <table class="table table-bordered align-top">
          <thead>
            <tr>
              <th>Dan</th>
              <?php foreach ($tipoviObroka as $tip): ?>
                <th><?php echo htmlspecialchars($tip->naziv); ?> <small>(<?php echo $tip->radnoVreme(); ?>)</small></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($dani as $dan): ?>
              <tr>
                <th><?php echo $dan['dan']; ?> <?php echo date('j.n.', strtotime($dan['datum'])); ?></th>
                <?php foreach ($tipoviObroka as $tip): ?>
                  <td>
                    <?php foreach ($jelovnikNedelja[$dan['datum']][$tip->id] ?? [] as $stavka): ?>
                      <div><?php echo htmlspecialchars($stavka->jelo); ?><?php echo $stavka->posno ? ' (posno)' : ''; ?></div>
                    <?php endforeach; ?>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>
    <?php endif; ?>

    <div class="note-box">
      <?php echo ikonica('info'); ?>
      <p>
        Posno — jelo bez mesa, mlečnih proizvoda i jaja. Jelovnik unosi služba ishrane i može se promeniti;
        o izmenama stiže obaveštenje.
      </p>
    </div>

  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
