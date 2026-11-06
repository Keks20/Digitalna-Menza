<?php
/**
 * PRIKAZ: admin – smeštaj: ko je platio stanarinu za mesec i pravljenje mesečnih zaduženja.
 *
 * U Figmi nema ovog ekrana – prati izgled "Uplate i obaveštenja" (filteri, pretraga, redovi tabele).
 * Spisak promenljivih je na vrhu admin_smestaj.php.
 * Samo prikaz, bez upita u bazu; tekst iz baze se ispisuje kroz htmlspecialchars().
 */
$naslovStrane = 'Smeštaj';
$aktivnaStrana = 'admin_smestaj.php';
// .btn-choice je iz jelovnik.css; filteri i pretraga (.btn-choice-sm, .payments-search) iz admin_uplate.css
$stilovi = ['css/jelovnik.css', 'css/admin_uplate.css', 'css/admin_smestaj.css'];
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/admin_meni.php';

$procenatNaplate = $zbir['ukupno'] > 0 ? round($zbir['naplaceno'] / $zbir['ukupno'] * 100) : 0;

/** Skrivena polja da GET forme (dom, pretraga) zadrže ostatak filtera */
function skriveniFilter(array $polja): string
{
    $html = '';
    foreach ($polja as $ime => $vrednost) {
        if ($vrednost !== '') {
            $html .= '<input type="hidden" name="' . $ime . '" value="' . htmlspecialchars($vrednost) . '" />';
        }
    }
    return $html;
}
?>
  <main class="app-main app-main-wide">

    <header class="page-header d-flex justify-content-between align-items-start gap-3">
      <div>
        <h1 class="page-title">Smeštaj</h1>
        <p class="page-date">Stanarine po mesecima · ko je platio, ko kasni</p>
      </div>
      <?php require __DIR__ . '/delovi/korisnik.php'; ?>
    </header>

    <?php if ($poruka): ?>
      <div class="alert app-alert alert-<?php echo $poruka['tip'] === 'success' ? 'success' : 'danger'; ?> mb-3 mb-lg-4" role="alert"><?php echo htmlspecialchars($poruka['tekst']); ?></div>
    <?php endif; ?>

    <!-- Mesec i dugme za nova zaduženja -->
    <div class="month-bar d-flex flex-wrap align-items-center gap-3 mb-3 mb-lg-4">
      <div class="month-switch d-flex align-items-center">
        <a href="<?php echo htmlspecialchars(adminSmestajUrl(['mesec' => $prethodniMesec])); ?>" class="week-arrow" aria-label="Prethodni mesec" title="Prethodni mesec"><?php echo ikonica('levo'); ?></a>
        <span class="month-name"><?php echo htmlspecialchars($nazivMeseca); ?></span>
        <a href="<?php echo htmlspecialchars(adminSmestajUrl(['mesec' => $sledeciMesec])); ?>" class="week-arrow" aria-label="Sledeći mesec" title="Sledeći mesec"><?php echo ikonica('desno'); ?></a>
      </div>
      <?php if ($mozeNapraviti): ?>
        <form method="post" action="admin_smestaj.php" class="ms-lg-auto"
              onsubmit="return confirm('Napraviti zaduženja za <?php echo htmlspecialchars($nazivMeseca); ?> svim aktivnim ugovorima? Ugovori koji već imaju zaduženje se preskaču.');">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>" />
          <input type="hidden" name="akcija" value="napravi_zaduzenja" />
          <input type="hidden" name="mesec" value="<?php echo htmlspecialchars($mesec); ?>" />
          <button type="submit" class="btn btn-primary btn-app"><?php echo ikonica('plus'); ?> Napravi zaduženja za <?php echo htmlspecialchars(lcfirst($nazivMeseca)); ?></button>
        </form>
      <?php endif; ?>
    </div>

    <!-- Zbir za mesec -->
    <div class="row g-3 g-lg-4 mb-3 mb-lg-4">
      <div class="col-6 col-xl-3">
        <section class="stat-card h-100">
          <span class="tint-box tint-blue"><?php echo ikonica('zgrada'); ?></span>
          <p class="stat-num"><?php echo (int)$zbir['broj']; ?></p>
          <p class="stat-text">Zaduženja za mesec</p>
        </section>
      </div>
      <div class="col-6 col-xl-3">
        <section class="stat-card h-100">
          <span class="tint-box tint-green"><?php echo ikonica('kvacica'); ?></span>
          <p class="stat-num"><?php echo (int)$zbir['placeno_broj']; ?> <span>/ <?php echo (int)$zbir['broj']; ?></span></p>
          <p class="stat-text">Plaćeno</p>
        </section>
      </div>
      <div class="col-6 col-xl-3">
        <section class="stat-card h-100">
          <span class="tint-box tint-orange"><?php echo ikonica('upozorenje'); ?></span>
          <p class="stat-num<?php echo $zbir['kasni_broj'] > 0 ? ' amount-red' : ''; ?>"><?php echo (int)$zbir['kasni_broj']; ?></p>
          <p class="stat-text">Kasni sa plaćanjem</p>
        </section>
      </div>
      <div class="col-6 col-xl-3">
        <section class="stat-card h-100">
          <span class="tint-box tint-green"><?php echo ikonica('novac'); ?></span>
          <p class="stat-num stat-money"><?php echo Format::novac($zbir['naplaceno']); ?></p>
          <p class="stat-text">Naplaćeno od <?php echo Format::novac($zbir['ukupno']); ?> (<?php echo $procenatNaplate; ?>%)</p>
        </section>
      </div>
    </div>

    <!-- Tabela -->
    <section class="panel">
      <div class="payments-tools d-flex flex-wrap align-items-center gap-2 mb-3">
        <nav class="d-flex flex-wrap gap-2" aria-label="Filter po statusu">
          <?php foreach ($FILTERI as $vrednost => $naziv): ?>
            <?php $aktivan = $filterStatus === $vrednost; ?>
            <a href="<?php echo htmlspecialchars(adminSmestajUrl(['status' => $vrednost])); ?>"
               class="btn-choice btn-choice-sm<?php echo $aktivan ? ' active' : ''; ?>"<?php echo $aktivan ? ' aria-current="page"' : ''; ?>>
              <?php echo $naziv; ?>
              <?php if ($vrednost === 'kasni' && $zbir['kasni_broj'] > 0): ?>
                <span class="choice-count"><?php echo (int)$zbir['kasni_broj']; ?></span>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        </nav>

        <div class="d-flex flex-wrap gap-2 ms-lg-auto">
          <?php if (count($domovi) > 1): ?>
            <form method="get" action="admin_smestaj.php">
              <?php echo skriveniFilter(['mesec' => $mesec, 'status' => $filterStatus, 'q' => $pretraga]); ?>
              <label class="visually-hidden" for="filterDom">Dom</label>
              <select class="form-select app-input dorm-select" id="filterDom" name="dom" onchange="this.form.submit()">
                <option value="">Svi domovi</option>
                <?php foreach ($domovi as $d): ?>
                  <option value="<?php echo htmlspecialchars($d); ?>" <?php echo $dom === $d ? 'selected' : ''; ?>><?php echo htmlspecialchars($d); ?></option>
                <?php endforeach; ?>
              </select>
              <noscript><button type="submit" class="btn btn-app btn-app-outline">Prikaži</button></noscript>
            </form>
          <?php endif; ?>

          <form method="get" action="admin_smestaj.php" class="payments-search" role="search">
            <?php echo skriveniFilter(['mesec' => $mesec, 'status' => $filterStatus, 'dom' => $dom]); ?>
            <?php echo ikonica('pretraga', 'search-icon'); ?>
            <input type="search" class="form-control app-input" name="q" value="<?php echo htmlspecialchars($pretraga); ?>"
                   placeholder="Ime, indeks ili kartica" aria-label="Pretraga studenata" />
          </form>
        </div>
      </div>

      <?php if (empty($zaduzenja)): ?>
        <div class="empty-state text-center">
          <span class="tint-box tint-blue mx-auto"><?php echo ikonica('zgrada'); ?></span>
          <?php if ($filterStatus === '' && $dom === '' && $pretraga === ''): ?>
            <p class="empty-title">Nema zaduženja za <?php echo htmlspecialchars(lcfirst($nazivMeseca)); ?></p>
            <p class="empty-text">
              <?php echo $mozeNapraviti ? 'Klikni „Napravi zaduženja" iznad da ih napraviš svim aktivnim ugovorima.' : 'Za ovaj mesec zaduženja nisu pravljena.'; ?>
            </p>
          <?php else: ?>
            <p class="empty-title">Nema zaduženja za izabrani filter</p>
            <p class="empty-text">Probaj drugi status ili dom, ili obriši tekst iz pretrage.</p>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="rent-head d-none d-md-grid" aria-hidden="true">
          <span>Student</span><span>Soba</span><span class="text-end">Iznos</span><span>Rok</span><span>Status</span>
        </div>
        <?php foreach ($zaduzenja as $z): ?>
          <?php
            if ($z['placeno']) {
                [$status, $boja] = ['Plaćeno', 'tag-green'];
            } else {
                [$status, $boja] = $z['kasni'] ? ['Kasni', 'tag-red'] : ['Neplaćeno', 'tag-orange'];
            }
          ?>
          <article class="rent-row<?php echo $z['kasni'] ? ' is-late' : ''; ?>">
            <div class="rent-student min-w-0">
              <p class="rent-name"><?php echo htmlspecialchars($z['ime'] . ' ' . $z['prezime']); ?></p>
              <p class="rent-sub"><?php echo htmlspecialchars($z['broj_indeksa']); ?> · <?php echo htmlspecialchars(Format::brojKartice($z['broj_kartice'])); ?></p>
            </div>
            <div class="rent-room">
              <?php echo htmlspecialchars($z['dom']); ?> · <?php echo htmlspecialchars($z['paviljon']); ?> · <?php echo htmlspecialchars($z['soba']); ?>
            </div>
            <div class="rent-amount"><?php echo Format::novac($z['iznos']); ?></div>
            <div class="rent-due"><span class="d-md-none">rok </span><?php echo Format::datum($z['rok_placanja']); ?></div>
            <div class="rent-status">
              <span class="tag ms-0 <?php echo $boja; ?>"><?php echo $status; ?></span>
              <?php if ($z['placeno'] && $z['datum_placanja']): ?>
                <span class="rent-paid"><?php echo date('d.m.', strtotime($z['datum_placanja'])); ?></span>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
