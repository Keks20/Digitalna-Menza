// QR bon za obrok: prvi tekst stiže sa stranom (data-tekst), a posle se svakih
// data-osvezi sekundi traži novi sa servera (data-adresa), jer stari bon ističe.
(function () {
  const mesto = document.getElementById("bonQr");
  if (!window.QRCode || !mesto) {
    return;
  }

  const odbrojavanje = document.getElementById("bonOdbrojavanje");
  let sekundi = parseInt(mesto.dataset.osvezi, 10) || 60;
  let preostalo = sekundi;
  let uToku = false;

  const qr = new QRCode(mesto, {
    text: mesto.dataset.tekst,
    width: 240,
    height: 240,
    correctLevel: QRCode.CorrectLevel.M,
  });

  function prikaziOdbrojavanje() {
    if (odbrojavanje) {
      odbrojavanje.textContent = preostalo;
    }
  }

  function osvezi() {
    uToku = true;
    fetch(mesto.dataset.adresa, { credentials: "same-origin", cache: "no-store" })
      .then(function (odgovor) {
        return odgovor.json();
      })
      .then(function (podaci) {
        if (podaci.tekst) {
          qr.makeCode(podaci.tekst);
          mesto.dataset.tekst = podaci.tekst;
          sekundi = podaci.osvezi || sekundi;
        } else {
          // Obroka te vrste više nema (npr. upravo je iskorišćen) – strana se učitava ponovo
          window.location.reload();
        }
      })
      .catch(function () {
        // Bez interneta stari kod ostaje; pokušava se ponovo za 10 sekundi
        sekundi = 10;
      })
      .finally(function () {
        uToku = false;
        preostalo = sekundi;
        prikaziOdbrojavanje();
      });
  }

  prikaziOdbrojavanje();
  setInterval(function () {
    if (uToku) {
      return;
    }
    preostalo -= 1;
    if (preostalo <= 0) {
      osvezi();
    } else {
      prikaziOdbrojavanje();
    }
  }, 1000);
})();
