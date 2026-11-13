// Zvonce u zaglavlju: kad student otvori listu obaveštenja, serveru se javlja da su pročitana
// i broj na zvoncetu nestaje. Oznake "novo" ostaju dok je lista otvorena, da se vidi šta je stiglo.
(function () {
  const zvonce = document.getElementById('zvonce');
  if (!zvonce) {
    return;
  }

  let javljeno = false;

  zvonce.addEventListener('show.bs.dropdown', function () {
    if (javljeno || parseInt(zvonce.dataset.neprocitano, 10) === 0) {
      return;
    }
    javljeno = true;

    const podaci = new URLSearchParams();
    podaci.set('csrf_token', zvonce.dataset.csrf);

    fetch('obavestenja_procitano.php', {
      method: 'POST',
      body: podaci,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (odgovor) {
        if (!odgovor.ok) {
          throw new Error('Obaveštenja nisu označena kao pročitana.');
        }
        const broj = document.getElementById('zvonceBroj');
        if (broj) {
          broj.remove();
        }
        zvonce.dataset.neprocitano = '0';
        zvonce.setAttribute('aria-label', 'Obaveštenja');
      })
      .catch(function () {
        // Broj ostaje na zvoncetu, pa se pri sledećem otvaranju pokušava ponovo
        javljeno = false;
      });
  });
})();
