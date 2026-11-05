<?php
/**
 * PRIKAZ: početna strana studenta (izgled po Figmi – ekran "Početna").
 *
 * Spisak promenljivih koje stižu iz pocetna.php je na vrhu tog fajla.
 * Ovde nema upita u bazu – samo prikaz. Tekst iz baze uvek kroz htmlspecialchars().
 */
$naslovStrane = 'Početna';
$aktivnaStrana = 'pocetna.php';
$stilovi = ['css/pocetna.css'];
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/meni.php';
require_once __DIR__ . '/delovi/obavestenja_izgled.php';

/** Link ka stranici ako već postoji, inače '#' (da dugme ne vodi na grešku 404). */
function linkKa(string $fajl): string
{
    return file_exists(__DIR__ . '/../' . $fajl) ? $fajl : '#';
}

// "oktobar" -> "oktobru" (za "Preostalih obroka u oktobru")
$mesecU = [
    'januar' => 'januaru', 'februar' => 'februaru', 'mart' => 'martu', 'april' => 'aprilu',
    'maj' => 'maju', 'jun' => 'junu', 'jul' => 'julu', 'avgust' => 'avgustu',
    'septembar' => 'septembru', 'oktobar' => 'oktobru', 'novembar' => 'novembru', 'decembar' => 'decembru',
];
$nazivMeseca = Format::mesec($tekuciMesec);
$vaziDo = date('t.m.Y.', strtotime($tekuciMesec));

// Ikonica i boja za svaki obrok (po id-ju iz tabele tipovi_obroka)
$izgledObroka = [
    1 => ['ikonica' => 'solja', 'boja' => 'tint-orange'],  // Doručak
    2 => ['ikonica' => 'jaje',  'boja' => 'tint-blue'],    // Ručak
    3 => ['ikonica' => 'mesec', 'boja' => 'tint-green'],   // Večera
];
?>
  <main class="app-main">

    <!-- ZAGLAVLJE: pozdrav i korisnik -->
    <header class="page-header d-flex justify-content-between align-items-start gap-3">
      <div>
        <h1 class="page-title">Zdravo, <?php echo htmlspecialchars($student->ime); ?> <span aria-hidden="true">👋</span></h1>
        <p class="page-date"><?php echo Format::danSaDatumom($danas); ?></p>
      </div>

      <?php $imaObavestenja = !empty($poruke); require __DIR__ . '/delovi/korisnik.php'; ?>
    </header>

    <!-- RED 1: kartica sa stanjem + tri male kartice -->
    <div class="row g-3 g-lg-4 mb-3 mb-lg-4">
      <div class="col-lg-7">
        <section class="balance-card h-100 d-flex flex-column">
          <span class="balance-chip" aria-hidden="true"></span>
          <p class="balance-label">Stanje na kartici</p>
          <p class="balance-amount">
            <?php echo number_format($kartica->stanje, 2, ',', '.'); ?> <span>RSD</span>
          </p>
          <p class="balance-number mt-lg-auto"><?php echo htmlspecialchars($kartica->formatiranBroj()); ?></p>
          <a href="<?php echo linkKa('dopuna.php'); ?>" class="btn btn-balance">
            <?php echo ikonica('plus'); ?> Dopuni karticu
          </a>
        </section>
      </div>

      <div class="col-lg-5">
        <div class="row g-3 g-lg-4 h-100">
          <!-- Preostali obroci -->
          <div class="col-6">
            <section class="stat-card h-100">
              <span class="tint-box tint-blue"><?php echo ikonica('karta'); ?></span>
              <p class="stat-value"><?php echo (int)$preostaloUkupno; ?></p>
              <p class="stat-label">Preostalih obroka u <?php echo $mesecU[$nazivMeseca] ?? $nazivMeseca; ?></p>
            </section>
          </div>

          <!-- Stanarina -->
          <div class="col-6">
            <section class="stat-card h-100">
              <span class="tint-box tint-orange"><?php echo ikonica('kuca'); ?></span>
              <?php if ($stanarina): ?>
                <p class="stat-value"><?php echo number_format($stanarina->iznos, 0, ',', '.'); ?> <span>RSD</span></p>
                <p class="stat-label">
                  Stanarina za <?php echo Format::mesec($stanarina->mesec); ?> ·
                  <?php if ($stanarina->placeno): ?>
                    <span class="text-success">plaćeno</span>
                  <?php elseif ($stanarina->kasni()): ?>
                    <span class="text-danger">rok <?php echo date('d.m.', strtotime($stanarina->rok_placanja)); ?> (kasni)</span>
                  <?php else: ?>
                    rok <?php echo date('d.m.', strtotime($stanarina->rok_placanja)); ?>
                  <?php endif; ?>
                </p>
              <?php else: ?>
                <p class="stat-value">—</p>
                <p class="stat-label">Nemaš ugovor o smeštaju u domu</p>
              <?php endif; ?>
            </section>
          </div>

          <!-- Poslednja uplata -->
          <div class="col-12">
            <section class="stat-card h-100">
              <span class="tint-box tint-green"><?php echo ikonica('uplata'); ?></span>
              <?php if ($poslednjaUplata): ?>
                <p class="stat-value">+<?php echo number_format($poslednjaUplata->iznos, 0, ',', '.'); ?></p>
                <p class="stat-label">Poslednja uplata · <?php echo date('d.m.', strtotime($poslednjaUplata->kreirano)); ?></p>
              <?php else: ?>
                <p class="stat-value">—</p>
                <p class="stat-label">Još nema uplata na kartici</p>
              <?php endif; ?>
            </section>
          </div>
        </div>
      </div>
    </div>

    <!-- RED 2: današnji jelovnik + moji obroci -->
    <div class="row g-3 g-lg-4 mb-3 mb-lg-4 align-items-start">
      <div class="col-lg-7">
        <section class="panel">
          <div class="panel-head">
            <h2>Danas u menzi</h2>
            <a href="<?php echo linkKa('jelovnik.php'); ?>" class="panel-link">Nedeljni jelovnik <?php echo ikonica('desno'); ?></a>
          </div>

          <!-- Tabovi sa menzama (Bootstrap pills) -->
          <div class="nav menza-tabs" role="tablist">
            <?php foreach ($menze as $i => $menza): ?>
              <?php $kratko = preg_replace('/^Menza\s+/u', '', $menza['naziv']); ?>
              <button type="button" class="nav-link<?php echo $i === 0 ? ' active' : ''; ?>"
                      id="tab-menza-<?php echo (int)$menza['id']; ?>"
                      data-bs-toggle="pill" data-bs-target="#menza-<?php echo (int)$menza['id']; ?>"
                      role="tab" aria-controls="menza-<?php echo (int)$menza['id']; ?>"
                      aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>">
                <?php if ($kratko !== $menza['naziv']): ?><span class="d-none d-md-inline">Menza </span><?php endif; ?><?php echo htmlspecialchars($kratko); ?>
              </button>
            <?php endforeach; ?>
          </div>

          <div class="tab-content">
            <?php foreach ($menze as $i => $menza): ?>
              <div class="tab-pane fade<?php echo $i === 0 ? ' show active' : ''; ?>"
                   id="menza-<?php echo (int)$menza['id']; ?>" role="tabpanel"
                   aria-labelledby="tab-menza-<?php echo (int)$menza['id']; ?>">
                <?php foreach ($tipoviObroka as $tip): ?>
                  <?php $stavke = $jelovnikDanas[$menza['id']][$tip->id] ?? []; ?>
                  <div class="meal-row">
                    <div class="meal-time">
                      <strong><?php echo htmlspecialchars($tip->naziv); ?></strong>
                      <span><?php echo $tip->radnoVreme(); ?></span>
                    </div>
                    <ul class="meal-items">
                      <?php foreach ($stavke as $stavka): ?>
                        <li>
                          <?php echo htmlspecialchars($stavka->jelo); ?>
                          <?php if ($stavka->posno): ?><span class="tag tag-green">Posno</span><?php endif; ?>
                        </li>
                      <?php endforeach; ?>
                      <?php if (!$stavke): ?>
                        <li class="meal-empty">Jelovnik još nije objavljen.</li>
                      <?php endif; ?>
                    </ul>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </section>
      </div>

      <div class="col-lg-5">
        <section class="panel">
          <div class="panel-head">
            <h2>Moji obroci — <?php echo $nazivMeseca; ?></h2>
            <a href="<?php echo linkKa('kupovina.php'); ?>" class="panel-link">Kupi još <?php echo ikonica('desno'); ?></a>
          </div>

          <?php foreach ($obroci as $obrok): ?>
            <?php
              $izgled = $izgledObroka[(int)$obrok['tip_obroka_id']] ?? ['ikonica' => 'solja', 'boja' => 'tint-blue'];
              $procenat = $obrok['kupljeno'] > 0 ? round($obrok['preostalo'] / $obrok['kupljeno'] * 100) : 0;
            ?>
            <div class="meal-balance d-flex align-items-center">
              <span class="tint-box <?php echo $izgled['boja']; ?>"><?php echo ikonica($izgled['ikonica']); ?></span>
              <div class="flex-fill">
                <div class="d-flex justify-content-between align-items-baseline">
                  <strong><?php echo htmlspecialchars($obrok['naziv']); ?></strong>
                  <span class="meal-count"><b><?php echo (int)$obrok['preostalo']; ?></b> / <?php echo (int)$obrok['kupljeno']; ?></span>
                </div>
                <div class="progress meal-progress" role="progressbar" aria-label="<?php echo htmlspecialchars($obrok['naziv']); ?>"
                     aria-valuenow="<?php echo $procenat; ?>" aria-valuemin="0" aria-valuemax="100">
                  <div class="progress-bar" style="width: <?php echo $procenat; ?>%"></div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>

          <div class="d-flex justify-content-between align-items-center gap-2 mt-3">
            <span class="valid-until"><?php echo ikonica('kalendar-ok'); ?> Važe do <b><?php echo $vaziDo; ?></b></span>
            <a href="<?php echo linkKa('obroci.php'); ?>" class="btn btn-primary btn-qr"><?php echo ikonica('qr'); ?> Prikaži QR</a>
          </div>
        </section>
      </div>
    </div>

    <!-- RED 3: poruke studentima -->
    <section class="panel">
      <div class="panel-head">
        <h2>Poruke studentima</h2>
        <a href="<?php echo linkKa('obavestenja.php'); ?>" class="panel-link">Sve poruke <?php echo ikonica('desno'); ?></a>
      </div>

      <?php foreach ($poruke as $poruka): ?>
        <?php $izgled = izgledObavestenja($poruka); ?>
        <article class="message d-flex">
          <span class="tint-box <?php echo $izgled['boja']; ?>"><?php echo ikonica($izgled['ikonica']); ?></span>
          <div>
            <h3 class="message-title"><?php echo htmlspecialchars($poruka['naslov']); ?></h3>
            <p class="message-text"><?php echo htmlspecialchars($poruka['tekst']); ?></p>
            <p class="message-meta">
              <?php echo Format::datum($poruka['kreirano']); ?> ·
              <?php echo htmlspecialchars($izgled['kome']); ?>
            </p>
          </div>
        </article>
      <?php endforeach; ?>

      <?php if (empty($poruke)): ?>
        <p class="meal-empty mb-0">Trenutno nema novih poruka.</p>
      <?php endif; ?>
    </section>

  </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
