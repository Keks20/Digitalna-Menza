// Kamera na kasi: čita QR bon (biblioteka jsQR) i šalje ga serveru preko forme #formaSkeniranja.
// Kamera radi samo na https ili na localhost. Ako je kasir jednom uključi, uključuje se sama
// i posle svakog skeniranja (strana se ponovo učita), dok je ne isključi.
(function () {
  const video = document.getElementById("kameraVideo");
  const dugme = document.getElementById("dugmeKamera");
  const status = document.getElementById("kameraStatus");
  const forma = document.getElementById("formaSkeniranja");
  const polje = document.getElementById("bonTekst");
  if (!video || !dugme || !forma || !polje) {
    return;
  }

  const KLJUC = "kasaKamera";
  const platno = document.createElement("canvas");
  const crtanje = platno.getContext("2d", { willReadFrequently: true });
  let tok = null;
  let poslato = false;

  function zapamti(ukljucena) {
    try {
      sessionStorage.setItem(KLJUC, ukljucena ? "1" : "0");
    } catch (e) {
      // Bez sessionStorage kamera se samo ne uključuje sama posle učitavanja
    }
  }

  function bilaUkljucena() {
    try {
      return sessionStorage.getItem(KLJUC) === "1";
    } catch (e) {
      return false;
    }
  }

  function prikaziStatus(tekst, klasa) {
    if (status) {
      status.textContent = tekst;
      status.className = "tag " + klasa;
    }
  }

  function citaj() {
    if (!tok || poslato) {
      return;
    }
    if (video.readyState === video.HAVE_ENOUGH_DATA) {
      platno.width = video.videoWidth;
      platno.height = video.videoHeight;
      crtanje.drawImage(video, 0, 0, platno.width, platno.height);
      const slika = crtanje.getImageData(0, 0, platno.width, platno.height);
      const kod = window.jsQR(slika.data, slika.width, slika.height, { inversionAttempts: "dontInvert" });
      // Šalje se samo naš bon (počinje sa "DM1."), ostali QR kodovi se preskaču
      if (kod && kod.data.indexOf("DM1.") === 0) {
        poslato = true;
        polje.value = kod.data;
        ugasi(false);
        forma.submit();
        return;
      }
    }
    requestAnimationFrame(citaj);
  }

  function upali() {
    if (!navigator.mediaDevices || !window.jsQR) {
      prikaziStatus("Kamera nije dostupna", "tag-red");
      return;
    }
    navigator.mediaDevices
      .getUserMedia({ video: { facingMode: "environment" } })
      .then(function (s) {
        tok = s;
        video.srcObject = s;
        video.style.display = "block";
        video.play();
        dugme.textContent = "Isključi kameru";
        prikaziStatus("Kamera uključena", "tag-green");
        zapamti(true);
        requestAnimationFrame(citaj);
      })
      .catch(function () {
        prikaziStatus("Kamera nije dozvoljena", "tag-red");
        zapamti(false);
      });
  }

  function ugasi(zaboravi) {
    if (tok) {
      tok.getTracks().forEach(function (t) {
        t.stop();
      });
    }
    tok = null;
    video.style.display = "none";
    dugme.textContent = "Uključi kameru";
    prikaziStatus("Kamera isključena", "tag-gray");
    if (zaboravi) {
      zapamti(false);
    }
  }

  dugme.addEventListener("click", function () {
    if (tok) {
      ugasi(true);
    } else {
      upali();
    }
  });

  // Dok čeka potvrdu izdavanja, kamera se ne pali (da isti bon ne bi bio skeniran dvaput)
  if (bilaUkljucena() && !document.getElementById("dugmeIzdaj")) {
    upali();
  }
})();
