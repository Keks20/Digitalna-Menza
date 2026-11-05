<?php
/**
 * PRIKAZ: dnevni i nedeljni jelovnik.
 *
 * Privremeni raspored – konačni izgled je ekran "Jelovnik" u Figmi.
 * Spisak promenljivih koje stižu iz jelovnik.php je na vrhu tog fajla.
 * Samo prikaz, bez upita u bazu; tekst iz baze se ispisuje kroz htmlspecialchars().
 */
$naslovStrane = 'Jelovnik';
$klasaTela = 'bg-light';
$aktivnaStrana = 'jelovnik.php';
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/meni.php';
?>
  <main class="container pb-4" style="max-width: 960px;">

    <div class="d-flex justify-content-between align-items-start mb-3">
      <div>
        <h1 class="h3 mb-1">Jelovnik</h1>
        <p class="text-muted mb-0">Nedelja <?php echo $nedeljaOpis; ?></p>
      </div>
      <div class="text-end small">
        <span class="fw-semibold"><?php echo htmlspecialchars($student->punoIme()); ?></span><br />
        <span class="text-muted"><?php echo htmlspecialchars($student->broj_indeksa); ?></span>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-body">
        <!-- Izbor menze -->
        <div class="d-flex flex-wrap gap-2 mb-3">
          <?php foreach ($menze as $menza): ?>
            <a href="<?php echo htmlspecialchars($menza['url']); ?>"
               class="btn <?php echo $menza['izabrana'] ? 'btn-primary' : 'btn-outline-secondary'; ?>">
              <?php echo htmlspecialchars($menza['naziv']); ?>
            </a>
          <?php endforeach; ?>
        </div>

        <!-- Dani u nedelji + strelice za prethodnu i sledeću nedelju (na telefonu se dani pomeraju prstom) -->
        <div class="d-flex align-items-stretch gap-2">
          <a href="<?php echo htmlspecialchars($prethodnaNedeljaUrl); ?>" class="btn btn-outline-secondary d-flex align-items-center" title="Prethodna nedelja">&lsaquo;</a>
          <div class="d-flex gap-2 flex-fill overflow-auto">
            <?php foreach ($dani as $dan): ?>
              <a href="<?php echo htmlspecialchars($dan['url']); ?>"
                 class="btn flex-fill <?php echo $dan['izabran'] ? 'btn-primary' : 'btn-outline-secondary'; ?><?php echo $dan['danas'] && !$dan['izabran'] ? ' border-primary border-2' : ''; ?>"
                 style="min-width: 3.5rem;"<?php echo $dan['danas'] ? ' title="Danas"' : ''; ?>>
                <span class="d-block small"><?php echo $dan['dan']; ?></span>
                <span class="d-block fw-bold fs-5"><?php echo $dan['broj']; ?></span>
              </a>
            <?php endforeach; ?>
          </div>
          <a href="<?php echo htmlspecialchars($sledecaNedeljaUrl); ?>" class="btn btn-outline-secondary d-flex align-items-center" title="Sledeća nedelja">&rsaquo;</a>
        </div>
      </div>
    </div>

    <p class="text-muted mb-2"><?php echo Format::danSaDatumom($izabraniDan); ?> · <?php echo htmlspecialchars($izabranaMenza['naziv']); ?></p>

    <?php if (!$imaJelovnika): ?>
      <div class="alert alert-secondary">Za ovaj dan jelovnik još nije objavljen.</div>
    <?php else: ?>
      <div class="row g-3 mb-3">
        <?php foreach ($tipoviObroka as $tip): ?>
          <div class="col-md-4">
            <div class="card h-100">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <div>
                    <p class="fw-bold mb-0"><?php echo htmlspecialchars($tip->naziv); ?></p>
                    <p class="text-muted small mb-0"><?php echo $tip->radnoVreme(); ?></p>
                  </div>
                  <span class="fw-bold"><?php echo Format::cena($tip->cena); ?></span>
                </div>
                <ul class="list-group list-group-flush">
                  <?php foreach ($jelovnik[$tip->id] ?? [] as $stavka): ?>
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                      <?php echo htmlspecialchars($stavka->jelo); ?>
                      <?php if ($stavka->posno): ?><span class="badge text-bg-success">Posno</span><?php endif; ?>
                    </li>
                  <?php endforeach; ?>
                  <?php if (empty($jelovnik[$tip->id])): ?>
                    <li class="list-group-item px-0 text-muted">Nema ponude za ovaj obrok.</li>
                  <?php endif; ?>
                </ul>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="alert alert-primary small mb-0">
      Posno — jelo bez mesa, mlečnih proizvoda i jaja. Jelovnik unosi služba ishrane i može se promeniti;
      o izmenama stiže obaveštenje.
    </div>

  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
