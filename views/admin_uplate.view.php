<?php
/**
 * PRIKAZ: uplate i obaveštenja (za sada deo sa obaveštenjima).
 *
 * Privremeni raspored – konačni izgled je ekran "Admin-uplate" u Figmi
 * (levo tabela uplata, desno "Novo obaveštenje" i "Objavljeno").
 * Spisak promenljivih i imena polja forme su na vrhu admin_uplate.php.
 * Samo prikaz, bez upita u bazu; tekst iz baze se ispisuje kroz htmlspecialchars().
 */
$naslovStrane = 'Uplate i obaveštenja';
$klasaTela = 'bg-light';
$aktivnaStrana = 'admin_uplate.php';
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/admin_meni.php';

$izmena = $forma['id'] !== null;
?>
  <main class="container pb-4" style="max-width: 1100px;">

    <h1 class="h3 mb-1">Uplate i obaveštenja</h1>
    <p class="text-muted">Poruke za studente u aplikaciji</p>

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
      <!-- Forma: novo obaveštenje ili izmena -->
      <div class="col-lg-5">
        <div class="card">
          <div class="card-body">
            <p class="fw-bold"><?php echo $izmena ? 'Izmena obaveštenja' : 'Novo obaveštenje'; ?></p>

            <form method="post" action="admin_uplate.php">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
              <input type="hidden" name="akcija" value="<?php echo $izmena ? 'izmeni' : 'objavi'; ?>" />
              <?php if ($izmena): ?>
                <input type="hidden" name="id" value="<?php echo (int)$forma['id']; ?>" />
              <?php endif; ?>

              <label for="naslov" class="form-label small fw-semibold">Naslov</label>
              <input type="text" class="form-control mb-3" id="naslov" name="naslov" maxlength="150"
                     value="<?php echo htmlspecialchars($forma['naslov']); ?>" />

              <label for="tekst" class="form-label small fw-semibold">Tekst poruke</label>
              <textarea class="form-control mb-3" id="tekst" name="tekst" rows="4" maxlength="2000"><?php echo htmlspecialchars($forma['tekst']); ?></textarea>

              <p class="form-label small fw-semibold mb-1">Kome se šalje</p>
              <?php foreach ($NAZIVI_GRUPA as $vrednost => $naziv): ?>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="ciljna_grupa" id="grupa-<?php echo $vrednost; ?>"
                         value="<?php echo $vrednost; ?>" <?php echo $forma['ciljna_grupa'] === $vrednost ? 'checked' : ''; ?> />
                  <label class="form-check-label" for="grupa-<?php echo $vrednost; ?>"><?php echo $naziv; ?></label>
                </div>
              <?php endforeach; ?>

              <div class="row g-2 mt-1 mb-3">
                <div class="col-6">
                  <label for="menzaId" class="form-label small text-muted mb-1">Menza</label>
                  <select class="form-select form-select-sm" id="menzaId" name="menza_id">
                    <?php foreach ($menze as $menza): ?>
                      <option value="<?php echo (int)$menza['id']; ?>" <?php echo (int)$forma['menza_id'] === (int)$menza['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($menza['naziv']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-6">
                  <label for="dom" class="form-label small text-muted mb-1">Dom</label>
                  <select class="form-select form-select-sm" id="dom" name="dom">
                    <?php foreach ($domovi as $dom): ?>
                      <option value="<?php echo htmlspecialchars($dom); ?>" <?php echo $forma['dom'] === $dom ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dom); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <button type="submit" class="btn btn-primary w-100"><?php echo $izmena ? 'Sačuvaj izmene' : 'Objavi obaveštenje'; ?></button>
              <?php if ($izmena): ?>
                <a href="admin_uplate.php" class="btn btn-link w-100">Odustani</a>
              <?php endif; ?>
            </form>
          </div>
        </div>
      </div>

      <!-- Objavljena obaveštenja -->
      <div class="col-lg-7">
        <div class="card">
          <div class="card-body">
            <p class="fw-bold">Objavljeno</p>
            <?php if (empty($obavestenja)): ?>
              <p class="text-muted mb-0">Još nema objavljenih obaveštenja.</p>
            <?php endif; ?>
            <ul class="list-group list-group-flush">
              <?php foreach ($obavestenja as $o): ?>
                <li class="list-group-item px-0 <?php echo $izmena && (int)$o['id'] === (int)$forma['id'] ? 'bg-primary-subtle' : ''; ?>">
                  <div class="d-flex justify-content-between gap-2">
                    <div>
                      <p class="fw-semibold mb-0"><?php echo htmlspecialchars($o['naslov']); ?></p>
                      <p class="small text-muted mb-1">
                        <?php echo Format::datumVreme($o['kreirano']); ?> ·
                        <?php if ($o['ciljna_grupa'] === 'menza'): ?>
                          <?php echo htmlspecialchars($o['menza'] ?? 'Menza'); ?>
                        <?php elseif ($o['ciljna_grupa'] === 'dom'): ?>
                          <?php echo htmlspecialchars($o['dom']); ?>
                        <?php else: ?>
                          Svi studenti
                        <?php endif; ?>
                        <?php if (!empty($o['autor'])): ?> · <?php echo htmlspecialchars($o['autor']); ?><?php endif; ?>
                      </p>
                      <p class="small mb-0"><?php echo nl2br(htmlspecialchars($o['tekst'])); ?></p>
                    </div>
                    <div class="d-flex flex-column gap-1 text-nowrap">
                      <a href="admin_uplate.php?izmeni=<?php echo (int)$o['id']; ?>" class="btn btn-sm btn-outline-primary">Izmeni</a>
                      <form method="post" action="admin_uplate.php" onsubmit="return confirm('Obrisati ovo obaveštenje?');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
                        <input type="hidden" name="akcija" value="obrisi" />
                        <input type="hidden" name="id" value="<?php echo (int)$o['id']; ?>" />
                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">Obriši</button>
                      </form>
                    </div>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </div>
    </div>

  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
