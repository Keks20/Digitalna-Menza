<?php
/**
 * PRIKAZ: unos jelovnika i cena obroka.
 *
 * Privremeni raspored – konačni izgled je ekran "Admin-jelovnik" u Figmi.
 * Spisak promenljivih i imena polja forme su na vrhu admin_jelovnik.php.
 * Samo prikaz, bez upita u bazu; tekst iz baze se ispisuje kroz htmlspecialchars().
 */
$naslovStrane = 'Jelovnik i cene';
$klasaTela = 'bg-light';
$aktivnaStrana = 'admin_jelovnik.php';
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/admin_meni.php';

// Boje znački za status dana
$bojeStatusa = [
    'objavljen' => 'text-bg-success',
    'u_izmeni' => 'text-bg-warning',
    'nije_unet' => 'text-bg-secondary',
];
?>
  <main class="container pb-4" style="max-width: 1100px;">

    <h1 class="h3 mb-1">Jelovnik i cene</h1>
    <p class="text-muted">Unos jelovnika po danu i menzi · cene važe za sve menze</p>

    <?php if ($poruka): ?>
      <div class="alert alert-<?php echo $poruka['tip'] === 'success' ? 'success' : 'danger'; ?>" role="alert">
        <?php echo htmlspecialchars($poruka['tekst']); ?>
      </div>
    <?php endif; ?>
    <?php if (!empty($greske)): ?>
      <div class="alert alert-danger" role="alert">
        <?php foreach ($greske as $greska): ?>
          <div><?php echo htmlspecialchars($greska); ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="row g-3">
      <!-- LEVO: izbor menze i dana + jela -->
      <div class="col-lg-8">
        <div class="card mb-3">
          <div class="card-body">
            <div class="d-flex flex-wrap gap-2 mb-3">
              <?php foreach ($menze as $menza): ?>
                <a href="<?php echo htmlspecialchars($menza['url']); ?>"
                   class="btn <?php echo $menza['izabrana'] ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                  <?php echo htmlspecialchars($menza['naziv']); ?>
                </a>
              <?php endforeach; ?>
            </div>

            <p class="small text-muted mb-2">Nedelja <?php echo $nedeljaOpis; ?></p>
            <div class="d-flex align-items-stretch gap-2">
              <a href="<?php echo htmlspecialchars($prethodnaNedeljaUrl); ?>" class="btn btn-outline-secondary d-flex align-items-center" title="Prethodna nedelja">&lsaquo;</a>
              <div class="d-flex gap-2 flex-fill overflow-auto">
                <?php foreach ($dani as $dan): ?>
                  <a href="<?php echo htmlspecialchars($dan['url']); ?>"
                     class="btn flex-fill <?php echo $dan['izabran'] ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                     style="min-width: 3.5rem;" title="<?php echo $NAZIVI_STATUSA[$dan['status']]; ?>">
                    <span class="d-block small"><?php echo $dan['dan']; ?></span>
                    <span class="d-block fw-bold"><?php echo $dan['broj']; ?></span>
                    <span class="d-inline-block rounded-circle <?php echo $bojeStatusa[$dan['status']]; ?>" style="width: .5rem; height: .5rem;"></span>
                  </a>
                <?php endforeach; ?>
              </div>
              <a href="<?php echo htmlspecialchars($sledecaNedeljaUrl); ?>" class="btn btn-outline-secondary d-flex align-items-center" title="Sledeća nedelja">&rsaquo;</a>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2">
          <p class="fw-semibold mb-0">
            <?php echo Format::danSaDatumom($izabraniDan); ?> · <?php echo htmlspecialchars($izabranaMenza['naziv']); ?>
          </p>
          <span class="badge <?php echo $bojeStatusa[$statusDana]; ?>"><?php echo $NAZIVI_STATUSA[$statusDana]; ?></span>
        </div>

        <form method="post" action="admin_jelovnik.php">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
          <input type="hidden" name="akcija" value="sacuvaj" />
          <input type="hidden" name="menza" value="<?php echo (int)$izabranaMenza['id']; ?>" />
          <input type="hidden" name="dan" value="<?php echo htmlspecialchars($izabraniDan); ?>" />

          <?php foreach ($tipoviObroka as $tip): ?>
            <div class="card mb-3">
              <div class="card-body">
                <p class="fw-bold mb-0"><?php echo htmlspecialchars($tip->naziv); ?></p>
                <p class="small text-muted"><?php echo $tip->radnoVreme(); ?></p>
                <?php foreach ($forma[$tip->id] as $i => $polje): ?>
                  <div class="d-flex align-items-center gap-2 mb-2">
                    <input type="text" class="form-control"
                           name="jela[<?php echo (int)$tip->id; ?>][<?php echo $i; ?>][jelo]"
                           value="<?php echo htmlspecialchars($polje['jelo']); ?>"
                           maxlength="150" placeholder="<?php echo ($i + 1); ?>. jelo" />
                    <div class="form-check mb-0 text-nowrap">
                      <input class="form-check-input" type="checkbox" value="1"
                             id="posno-<?php echo (int)$tip->id; ?>-<?php echo $i; ?>"
                             name="jela[<?php echo (int)$tip->id; ?>][<?php echo $i; ?>][posno]"
                             <?php echo $polje['posno'] ? 'checked' : ''; ?> />
                      <label class="form-check-label small" for="posno-<?php echo (int)$tip->id; ?>-<?php echo $i; ?>">Posno</label>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>

          <div class="d-flex flex-wrap gap-2">
            <button type="submit" name="dugme" value="objavi" class="btn btn-primary">
              Objavi jelovnik za <?php echo date('d.m.', strtotime($izabraniDan)); ?>
            </button>
            <button type="submit" name="dugme" value="nacrt" class="btn btn-outline-secondary">Sačuvaj kao nacrt</button>
          </div>
        </form>

        <form method="post" action="admin_jelovnik.php" class="mt-2"
              <?php if ($statusDana !== 'nije_unet'): ?>onsubmit="return confirm('Postojeća jela za ovaj dan biće zamenjena jelima od juče. Nastaviti?');"<?php endif; ?>>
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
          <input type="hidden" name="akcija" value="kopiraj" />
          <input type="hidden" name="menza" value="<?php echo (int)$izabranaMenza['id']; ?>" />
          <input type="hidden" name="dan" value="<?php echo htmlspecialchars($izabraniDan); ?>" />
          <button type="submit" class="btn btn-link px-0">Kopiraj od juče</button>
        </form>
      </div>

      <!-- DESNO: cene i status nedelje -->
      <div class="col-lg-4">
        <div class="card mb-3">
          <div class="card-body">
            <p class="fw-bold">Cene obroka</p>
            <form method="post" action="admin_jelovnik.php">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
              <input type="hidden" name="akcija" value="cene" />
              <input type="hidden" name="menza" value="<?php echo (int)$izabranaMenza['id']; ?>" />
              <input type="hidden" name="dan" value="<?php echo htmlspecialchars($izabraniDan); ?>" />
              <?php foreach ($tipoviObroka as $tip): ?>
                <label class="form-label small fw-semibold" for="cena-<?php echo (int)$tip->id; ?>"><?php echo htmlspecialchars($tip->naziv); ?> (RSD)</label>
                <input type="text" inputmode="decimal" class="form-control mb-2" id="cena-<?php echo (int)$tip->id; ?>"
                       name="cena[<?php echo (int)$tip->id; ?>]" value="<?php echo htmlspecialchars($ceneUFormi[$tip->id]); ?>" />
              <?php endforeach; ?>
              <button type="submit" class="btn btn-primary w-100 mt-2">Sačuvaj cene</button>
            </form>
            <p class="small text-muted mt-3 mb-0">
              Nove cene važe odmah. Već kupljeni obroci važe po staroj ceni.
            </p>
          </div>
        </div>

        <div class="card">
          <div class="card-body">
            <p class="fw-bold">Status nedelje</p>
            <ul class="list-group list-group-flush">
              <?php foreach ($dani as $dan): ?>
                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                  <a href="<?php echo htmlspecialchars($dan['url']); ?>" class="text-decoration-none <?php echo $dan['izabran'] ? 'fw-bold' : 'text-body'; ?>">
                    <?php echo $dan['dan']; ?> <?php echo date('d.m.', strtotime($dan['datum'])); ?>
                  </a>
                  <span class="badge <?php echo $bojeStatusa[$dan['status']]; ?>"><?php echo $NAZIVI_STATUSA[$dan['status']]; ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </div>
    </div>

  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
