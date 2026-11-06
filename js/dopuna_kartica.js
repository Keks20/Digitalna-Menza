// Plaćanje karticom: dok student kuca, broj kartice se deli u grupe po 4 cifre,
// a datum isteka dobija kosu crtu (MM/GG). Samo izgled – server sve proverava.
(function () {
  const broj = document.getElementById("brojKartice");
  const istek = document.getElementById("istek");
  const cvc = document.getElementById("cvc");

  if (broj) {
    broj.addEventListener("input", () => {
      const cifre = broj.value.replace(/\D/g, "").slice(0, 19);
      broj.value = cifre.replace(/(\d{4})(?=\d)/g, "$1 ");
    });
  }

  if (istek) {
    istek.addEventListener("input", (e) => {
      let cifre = istek.value.replace(/\D/g, "").slice(0, 4);
      // Brisanje kose crte ne sme da je odmah vrati
      if (cifre.length > 2 || (cifre.length === 2 && e.inputType !== "deleteContentBackward")) {
        cifre = cifre.slice(0, 2) + "/" + cifre.slice(2);
      }
      istek.value = cifre;
    });
  }

  if (cvc) {
    cvc.addEventListener("input", () => {
      cvc.value = cvc.value.replace(/\D/g, "").slice(0, 3);
    });
  }
})();
