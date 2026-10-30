const form = document.getElementById("loginForm");
const cardInput = document.getElementById("cardNumber");
const passInput = document.getElementById("password");
const toggleBtn = document.getElementById("togglePassword");
const eyeIcon = document.getElementById("eyeIcon");

// Prikaži / sakrij lozinku
const eyeOpen =
  '<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>';
const eyeClosed =
  '<path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143M2 2l20 20"/>';

toggleBtn.addEventListener("click", () => {
  const hidden = passInput.type === "password";
  passInput.type = hidden ? "text" : "password";
  eyeIcon.innerHTML = hidden ? eyeClosed : eyeOpen;
});

// Formatiranje broja kartice: 0012 3456 7890
cardInput.addEventListener("input", () => {
  const digits = cardInput.value.replace(/\D/g, "").slice(0, 12);
  cardInput.value = digits.replace(/(\d{4})(?=\d)/g, "$1 ");
});

// Slanje forme
form.addEventListener("submit", (e) => {
  e.preventDefault();

  const card = cardInput.value.replace(/\s/g, "");
  const password = passInput.value;

  if (card.length !== 12) {
    alert("Broj kartice mora imati 12 cifara.");
    cardInput.focus();
    return;
  }
  if (!password) {
    alert("Unesi lozinku.");
    passInput.focus();
    return;
  }

  // TODO: ovde ide poziv ka backendu
  console.log("Prijava:", { card, password });
});