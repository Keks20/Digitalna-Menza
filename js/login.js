const form = document.getElementById("loginForm");
const cardInput = document.getElementById("cardNumber");
const passInput = document.getElementById("password");
const toggleBtn = document.getElementById("togglePassword");
const eyeIcon = document.getElementById("eyeIcon");
const cardError = document.getElementById("cardError");
const passwordError = document.getElementById("passwordError");
const formMessage = document.getElementById("formMessage");

// Broj cifara na studentskoj kartici (proveriti na pravoj kartici SC-a)
const CARD_LENGTH = 12;

// Prikaži / sakrij lozinku
const eyeOpen =
  '<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>';
const eyeClosed =
  '<path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143M2 2l20 20"/>';

toggleBtn.addEventListener("click", () => {
  const hidden = passInput.type === "password";
  passInput.type = hidden ? "text" : "password";
  eyeIcon.innerHTML = hidden ? eyeClosed : eyeOpen;
  toggleBtn.setAttribute("aria-label", hidden ? "Sakrij lozinku" : "Prikaži lozinku");
});

// Greška ispod polja (Bootstrap: klasa is-invalid prikazuje invalid-feedback)
function showError(input, errorEl, message) {
  errorEl.textContent = message;
  input.classList.add("is-invalid");
  input.setAttribute("aria-invalid", "true");
}

function clearError(input, errorEl) {
  errorEl.textContent = "";
  input.classList.remove("is-invalid");
  input.removeAttribute("aria-invalid");
}

function hideMessage() {
  formMessage.textContent = "";
  formMessage.classList.add("d-none");
}

// Formatiranje broja kartice: 0012 3456 7890
cardInput.addEventListener("input", () => {
  const digits = cardInput.value.replace(/\D/g, "").slice(0, CARD_LENGTH);
  cardInput.value = digits.replace(/(\d{4})(?=\d)/g, "$1 ");
  clearError(cardInput, cardError);
  hideMessage();
});

passInput.addEventListener("input", () => {
  clearError(passInput, passwordError);
  hideMessage();
});

// Slanje forme
form.addEventListener("submit", (e) => {
  const card = cardInput.value.replace(/\s/g, "");
  const password = passInput.value;
  let valid = true;

  if (card.length !== CARD_LENGTH) {
    showError(cardInput, cardError, `Broj kartice mora imati ${CARD_LENGTH} cifara.`);
    valid = false;
  }
  if (!password) {
    showError(passInput, passwordError, "Unesi lozinku.");
    valid = false;
  }

  if (!valid) {
    e.preventDefault();
    (cardInput.classList.contains("is-invalid") ? cardInput : passInput).focus();
    return;
  }

  // Podaci su ispravni – forma se šalje POST metodom na login.php
});
