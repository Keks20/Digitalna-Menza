<?php
/**
 * PRIKAZ: unos jelovnika i cena obroka (izgled po Figmi – ekran "Admin-jelovnik").
 *
 * Spisak promenljivih i imena polja forme su na vrhu admin_jelovnik.php.
 * Samo prikaz, bez upita u bazu; tekst iz baze se ispisuje kroz htmlspecialchars().
 */
$naslovStrane = 'Jelovnik i cene';
$aktivnaStrana = 'admin_jelovnik.php';
$stilovi = ['css/jelovnik.css', 'css/admin_jelovnik.css'];   // dugmad za menze i dane su ista kao kod studenta
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/admin_meni.php';

// Oznaka u boji za status dana
$bojeStatusa = [
    'objavljen' => 'tag-green',
    'u_izmeni' => 'tag-orange',
    'nije_unet' => 'tag-gray',
];

// Ikonica i boja za svaki obrok (po id-ju iz tabele tipovi_obroka) – isto kao kod studenta
$izgledObroka = [
    1 => ['ikonica' => 'solja', 'boja' => 'tint-orange'],  // Doručak
    2 => ['ikonica' => 'jaje',  'boja' => 'tint-blue'],    // Ručak
    3 => ['ikonica' => 'mesec', 'boja' => 'tint-green'],   // Večera
];

/** Skrivena polja koja svaka forma na ovoj stranici šalje */
function skrivenaPolja(string $akcija, string $csrfToken, array $menza, string $dan): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken) . '" />'
        . '<input type="hidden" name="akcija" value="' . $akcija . '" />'
        . '<input type="hidden" name="menza" value="' . (int)$menza['id'] . '" />'
        . '<input type="hidden" name="dan" value="' . htmlspecialchars($dan) . '" />';
}
?>
  <main class="app-main app-main-wide">

    <!-- ZAGLAVLJE -->
    <header class="page-header d-flex justify-content-between align-items-start gap-3">
      <div>
        <h1 class="page-title">Jelovnik i cene</h1>
        <p class="page-date">Unos jelovnika po danu i menzi · cene važe za sve menze</p>
      </div>
      <?php require __DIR__ . '/delovi/korisnik.php'; ?>
    </header>

    <?php if ($poruka): ?>
      <div class="alert app-alert alert-<?php echo $poruka['tip'] === 'success' ? 'success' : 'danger'; ?> mb-3 mb-lg-4" role="alert">
        <?php echo htmlspecialchars($poruka['tekst']); ?>
      </div>
    <?php endif; ?>
    <?php if (!empty($greske)): ?>
      <div class="alert app-alert alert-danger mb-3 mb-lg-4" role="alert">
        <?php foreach ($greske as $greska): ?>
          <div><?php echo htmlspecialchars($greska); ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="row g-3 g-lg-4 align-items-start">

      <!-- LEVO: izbor menze i dana + jela -->
      <div class="col-lg-8">
        <section class="panel picker mb-3 mb-lg-4">
          <div class="d-flex flex-wrap align-items-center gap-2 gap-lg-3 mb-3">
            <?php foreach ($menze as $menza): ?>
              <?php $kratko = preg_replace('/^Menza\s+/u', '', $menza['naziv']); ?>
              <a href="<?php echo htmlspecialchars($menza['url']); ?>"
                 class="btn-choice<?php echo $menza['izabrana'] ? ' active' : ''; ?>"
                 <?php echo $menza['izabrana'] ? 'aria-current="true"' : ''; ?>>
                <span><?php if ($kratko !== $menza['naziv']): ?><span class="d-none d-xl-inline">Menza </span><?php endif; ?><?php echo htmlspecialchars($kratko); ?></span>
              </a>
            <?php endforeach; ?>

            <div class="week-switch d-flex align-items-center gap-1 ms-auto">
              <a href="<?php echo htmlspecialchars($prethodnaNedeljaUrl); ?>" class="week-arrow" title="Prethodna nedelja" aria-label="Prethodna nedelja"><?php echo ikonica('levo'); ?></a>
              <span class="week-label"><?php echo $nedeljaOpis; ?></span>
              <a href="<?php echo htmlspecialchars($sledecaNedeljaUrl); ?>" class="week-arrow" title="Sledeća nedelja" aria-label="Sledeća nedelja"><?php echo ikonica('desno'); ?></a>
            </div>
          </div>

          <nav class="day-strip d-flex" aria-label="Dani u nedelji">
            <?php foreach ($dani as $dan): ?>
              <a href="<?php echo htmlspecialchars($dan['url']); ?>"
                 class="day-btn<?php echo $dan['izabran'] ? ' active' : ''; ?><?php echo $dan['danas'] ? ' today' : ''; ?>"
                 title="<?php echo $NAZIVI_STATUSA[$dan['status']]; ?><?php echo $dan['danas'] ? ' · danas' : ''; ?>"
                 <?php echo $dan['izabran'] ? 'aria-current="date"' : ''; ?>>
                <span class="day-dot status-<?php echo $dan['status']; ?>" aria-hidden="true"></span>
                <span class="day-name"><?php echo $dan['dan']; ?></span>
                <span class="day-num"><?php echo $dan['broj']; ?></span>
              </a>
            <?php endforeach; ?>
          </nav>
        </section>

        <div class="day-heading d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
          <p class="mb-0">
            <strong><?php echo Format::danSaDatumom($izabraniDan); ?></strong>
            · <?php echo htmlspecialchars($izabranaMenza['naziv']); ?>
          </p>
          <span class="tag <?php echo $bojeStatusa[$statusDana]; ?>"><?php echo $NAZIVI_STATUSA[$statusDana]; ?></span>
        </div>

        <!-- Forma za jela izabranog dana -->
        <form method="post" action="admin_jelovnik.php" id="formaJela">
          <?php echo skrivenaPolja('sacuvaj', $csrfToken, $izabranaMenza, $izabraniDan); ?>

          <?php foreach ($tipoviObroka as $tip): ?>
            <?php $izgled = $izgledObroka[(int)$tip->id] ?? ['ikonica' => 'solja', 'boja' => 'tint-blue']; ?>
            <section class="panel meal-form mb-3 mb-lg-4">
              <div class="d-flex align-items-center gap-3 mb-3">
                <span class="tint-box <?php echo $izgled['boja']; ?>"><?php echo ikonica($izgled['ikonica']); ?></span>
                <div>
                  <h2 class="meal-name"><?php echo htmlspecialchars($tip->naziv); ?></h2>
                  <p class="meal-hours"><?php echo $tip->radnoVreme(); ?></p>
                </div>
              </div>

              <?php foreach ($forma[$tip->id] as $i => $polje): ?>
                <?php $idPolja = (int)$tip->id . '-' . $i; ?>
                <div class="dish-row d-flex align-items-center">
                  <label class="visually-hidden" for="jelo-<?php echo $idPolja; ?>">
                    <?php echo htmlspecialchars($tip->naziv); ?>, <?php echo $i + 1; ?>. jelo
                  </label>
                  <input type="text" class="form-control app-input" id="jelo-<?php echo $idPolja; ?>"
                         name="jela[<?php echo (int)$tip->id; ?>][<?php echo $i; ?>][jelo]"
                         value="<?php echo htmlspecialchars($polje['jelo']); ?>"
                         maxlength="150" placeholder="<?php echo $i + 1; ?>. jelo" />
                  <div class="form-check posno-check mb-0">
                    <input class="form-check-input" type="checkbox" value="1"
                           id="posno-<?php echo $idPolja; ?>"
                           name="jela[<?php echo (int)$tip->id; ?>][<?php echo $i; ?>][posno]"
                           <?php echo $polje['posno'] ? 'checked' : ''; ?> />
                    <label class="form-check-label" for="posno-<?php echo $idPolja; ?>">Posno</label>
                  </div>
                </div>
              <?php endforeach; ?>
            </section>
          <?php endforeach; ?>

          <div class="form-actions d-flex flex-wrap gap-2 gap-lg-3">
            <button type="submit" name="dugme" value="objavi" class="btn btn-primary btn-app flex-fill">
              <?php echo ikonica('posalji'); ?> Objavi jelovnik za <?php echo date('d.m.', strtotime($izabraniDan)); ?>
            </button>
            <button type="submit" name="dugme" value="nacrt" class="btn btn-app btn-app-outline">
              <?php echo ikonica('sacuvaj'); ?> Sačuvaj kao nacrt
            </button>
            <!-- Ovo dugme šalje zasebnu formu "formaKopiraj" (ispod), ne formu sa jelima -->
            <button type="submit" form="formaKopiraj" class="btn btn-app btn-app-outline">
              <?php echo ikonica('kopiraj'); ?> Kopiraj od juče
            </button>
          </div>
        </form>

        <form method="post" action="admin_jelovnik.php" id="formaKopiraj" class="d-none"
              <?php if ($statusDana !== 'nije_unet'): ?>onsubmit="return confirm('Postojeća jela za ovaj dan biće zamenjena jelima od juče. Nastaviti?');"<?php endif; ?>>
          <?php echo skrivenaPolja('kopiraj', $csrfToken, $izabranaMenza, $izabraniDan); ?>
        </form>
      </div>

      <!-- DESNO: cene i status nedelje -->
      <div class="col-lg-4">
        <section class="panel mb-3 mb-lg-4">
          <h2 class="panel-title">Cene obroka</h2>
          <form method="post" action="admin_jelovnik.php">
            <?php echo skrivenaPolja('cene', $csrfToken, $izabranaMenza, $izabraniDan); ?>
            <?php foreach ($tipoviObroka as $tip): ?>
              <?php $izgled = $izgledObroka[(int)$tip->id] ?? ['ikonica' => 'solja']; ?>
              <label class="form-label" for="cena-<?php echo (int)$tip->id; ?>"><?php echo htmlspecialchars($tip->naziv); ?> (RSD)</label>
              <div class="field">
                <?php echo ikonica($izgled['ikonica'], 'field-icon'); ?>
                <input type="text" inputmode="decimal" class="form-control" id="cena-<?php echo (int)$tip->id; ?>"
                       name="cena[<?php echo (int)$tip->id; ?>]" value="<?php echo htmlspecialchars($ceneUFormi[$tip->id]); ?>" />
              </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-primary btn-app w-100">Sačuvaj cene</button>
          </form>
          <div class="note-box mt-3">
            <?php echo ikonica('info'); ?>
            <p>Nove cene važe odmah, za sve menze. Već kupljeni obroci važe po staroj ceni.</p>
          </div>
        </section>

        <section class="panel">
          <h2 class="panel-title">Status nedelje</h2>
          <ul class="status-list">
            <?php foreach ($dani as $dan): ?>
              <li>
                <a href="<?php echo htmlspecialchars($dan['url']); ?>" class="<?php echo $dan['izabran'] ? 'is-selected' : ''; ?>">
                  <?php echo $dan['dan']; ?> <?php echo date('d.m.', strtotime($dan['datum'])); ?>
                </a>
                <span class="tag <?php echo $bojeStatusa[$dan['status']]; ?>"><?php echo $NAZIVI_STATUSA[$dan['status']]; ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      </div>
    </div>

  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
