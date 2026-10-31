<?php
require_once __DIR__ . '/includes/provera_prijave.php';
require_once __DIR__ . '/src/StudentRepository.php';
require_once __DIR__ . '/src/KarticaRepository.php';
require_once __DIR__ . '/src/TransakcijaRepository.php';
require_once __DIR__ . '/src/KupljeniObrokRepository.php';
require_once __DIR__ . '/src/SmestajRepository.php';
require_once __DIR__ . '/src/MenzaRepository.php';
require_once __DIR__ . '/src/TipObrokaRepository.php';
require_once __DIR__ . '/src/JelovnikRepository.php';
require_once __DIR__ . '/src/ObavestenjeRepository.php';
require_once __DIR__ . '/src/Format.php';

// =====================================================================
// PODACI ZA POČETNU (backend – Luka)
// Sve što stranica prikazuje je u promenljivama ispod.
// =====================================================================
$danas = date('Y-m-d');
$tekuciMesec = date('Y-m-01');

$student = (new StudentRepository())->read($studentId);            // Student
$kartica = (new KarticaRepository())->read($karticaId);            // Kartica
$poslednjaUplata = (new TransakcijaRepository())->poslednjaUplata($karticaId);   // Transakcija ili null
$obroci = (new KupljeniObrokRepository())->stanjeZaMesec($karticaId, $tekuciMesec);  // niz po tipu obroka
$stanarina = (new SmestajRepository())->tekuceZaduzenje($studentId);  // SmestajZaduzenje ili null
$menze = (new MenzaRepository())->list();                          // niz menzi
$tipoviObroka = (new TipObrokaRepository())->list();               // [TipObroka, ...]
$poruke = (new ObavestenjeRepository())->list(['limit' => 3]);

$preostaloUkupno = array_sum(array_column($obroci, 'preostalo'));

// Današnji jelovnik za svaku menzu: [menza_id => [tip_obroka_id => [StavkaJelovnika, ...]]]
$jelovnikRepo = new JelovnikRepository();
$jelovnikDanas = [];
foreach ($menze as $menza) {
    $jelovnikDanas[$menza['id']] = $jelovnikRepo->zaDan((int)$menza['id'], $danas);
}

// =====================================================================
// PRIVREMENI PRIKAZ – kolega ovaj HTML zamenjuje dizajnom iz Figme.
// Promenljive iznad ostaju iste.
// =====================================================================
?>
<!DOCTYPE html>
<html lang="sr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Početna – Digitalna menza</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light">
  <main class="container py-4" style="max-width: 960px;">

    <div class="d-flex justify-content-between align-items-start mb-4">
      <div>
        <h1 class="h3 mb-1">Zdravo, <?php echo htmlspecialchars($student->ime); ?></h1>
        <p class="text-muted mb-0"><?php echo Format::danSaDatumom($danas); ?></p>
      </div>
      <a href="logout.php" class="btn btn-outline-secondary btn-sm">Odjava</a>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-body">
            <p class="text-muted mb-1">Stanje na kartici</p>
            <p class="h3 mb-1"><?php echo Format::novac($kartica->stanje); ?></p>
            <p class="text-muted small mb-0">
              <?php echo htmlspecialchars($kartica->formatiranBroj()); ?> ·
              <?php echo htmlspecialchars($student->punoIme()); ?> · <?php echo htmlspecialchars($student->broj_indeksa); ?>
            </p>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <div class="card h-100"><div class="card-body">
          <p class="h4 mb-1"><?php echo $preostaloUkupno; ?></p>
          <p class="text-muted small mb-0">Preostalih obroka u mesecu (<?php echo Format::mesec($tekuciMesec); ?>)</p>
        </div></div>
      </div>
      <div class="col-6 col-md-2">
        <div class="card h-100"><div class="card-body">
          <?php if ($stanarina): ?>
            <p class="h5 mb-1"><?php echo Format::novac($stanarina->iznos); ?></p>
            <p class="text-muted small mb-0">
              Stanarina za <?php echo Format::mesec($stanarina->mesec); ?> ·
              <?php echo $stanarina->placeno ? 'plaćeno' : 'rok ' . Format::datum($stanarina->rok_placanja); ?>
            </p>
          <?php else: ?>
            <p class="text-muted small mb-0">Nema smeštaja u domu</p>
          <?php endif; ?>
        </div></div>
      </div>
      <div class="col-6 col-md-2">
        <div class="card h-100"><div class="card-body">
          <?php if ($poslednjaUplata): ?>
            <p class="h5 mb-1"><?php echo Format::novac($poslednjaUplata->iznos, true); ?></p>
            <p class="text-muted small mb-0">Poslednja uplata · <?php echo Format::datumVreme($poslednjaUplata->kreirano); ?></p>
          <?php else: ?>
            <p class="text-muted small mb-0">Još nema uplata</p>
          <?php endif; ?>
        </div></div>
      </div>
    </div>

    <h2 class="h5">Danas u menzi</h2>
    <ul class="nav nav-tabs" role="tablist">
      <?php foreach ($menze as $i => $menza): ?>
        <li class="nav-item" role="presentation">
          <button class="nav-link <?php echo $i === 0 ? 'active' : ''; ?>" data-bs-toggle="tab"
                  data-bs-target="#menza-<?php echo (int)$menza['id']; ?>" type="button" role="tab">
            <?php echo htmlspecialchars($menza['naziv']); ?>
          </button>
        </li>
      <?php endforeach; ?>
    </ul>
    <div class="tab-content bg-white border border-top-0 p-3 mb-4">
      <?php foreach ($menze as $i => $menza): ?>
        <div class="tab-pane fade <?php echo $i === 0 ? 'show active' : ''; ?>" id="menza-<?php echo (int)$menza['id']; ?>" role="tabpanel">
          <div class="row g-3">
            <?php foreach ($tipoviObroka as $tip): ?>
              <div class="col-md-4">
                <p class="fw-semibold mb-1">
                  <?php echo htmlspecialchars($tip->naziv); ?>
                  <span class="text-muted fw-normal small"><?php echo $tip->radnoVreme(); ?></span>
                </p>
                <ul class="list-unstyled small mb-0">
                  <?php foreach ($jelovnikDanas[$menza['id']][$tip->id] ?? [] as $stavka): ?>
                    <li>
                      <?php echo htmlspecialchars($stavka->jelo); ?>
                      <?php if ($stavka->posno): ?><span class="badge text-bg-success">Posno</span><?php endif; ?>
                    </li>
                  <?php endforeach; ?>
                  <?php if (empty($jelovnikDanas[$menza['id']][$tip->id])): ?>
                    <li class="text-muted">Jelovnik još nije objavljen.</li>
                  <?php endif; ?>
                </ul>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="row g-3">
      <div class="col-md-5">
        <h2 class="h5">Moji obroci — <?php echo Format::mesec($tekuciMesec); ?></h2>
        <ul class="list-group">
          <?php foreach ($obroci as $obrok): ?>
            <li class="list-group-item d-flex justify-content-between">
              <span><?php echo htmlspecialchars($obrok['naziv']); ?></span>
              <span><?php echo $obrok['preostalo']; ?> / <?php echo $obrok['kupljeno']; ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
        <p class="text-muted small mt-2">Važe do <?php echo Format::datum(date('Y-m-t')); ?></p>
      </div>
      <div class="col-md-7">
        <h2 class="h5">Poruke studentima</h2>
        <?php foreach ($poruke as $poruka): ?>
          <div class="card mb-2"><div class="card-body py-2">
            <p class="small text-muted mb-1">
              <?php echo Format::datumVreme($poruka['kreirano']); ?>
              <?php if ($poruka['menza']): ?>· <?php echo htmlspecialchars($poruka['menza']); ?><?php endif; ?>
            </p>
            <p class="fw-semibold mb-1"><?php echo htmlspecialchars($poruka['naslov']); ?></p>
            <p class="small mb-0"><?php echo htmlspecialchars($poruka['tekst']); ?></p>
          </div></div>
        <?php endforeach; ?>
      </div>
    </div>

  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
