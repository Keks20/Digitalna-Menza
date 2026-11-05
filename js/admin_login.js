// Prijava za osoblje: prikaz/sakrivanje lozinke i provera praznih polja pre slanja.
// Sama provera korisničkog imena i lozinke je na serveru (admin_login.php).

const adminForm = document.getElementById("adminLoginForm");
const korisnickoIme = document.getElementById("korisnickoIme");
const lozinka = document.getElementById("lozinka");
const prikaziLozinku = document.getElementById("prikaziLozinku");

const okoOtvoreno =
  '<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>';
const okoZatvoreno =
  '<path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143M2 2l20 20"/>';

prikaziLozinku.addEventListener("click", () => {
  const sakrivena = lozinka.type === "password";
  lozinka.type = sakrivena ? "text" : "password";
  prikaziLozinku.querySelector("svg").innerHTML = sakrivena ? okoZatvoreno : okoOtvoreno;
  prikaziLozinku.setAttribute("aria-label", sakrivena ? "Sakrij lozinku" : "Prikaži lozinku");
});

function prikaziGresku(polje, poruka) {
  document.getElementById(polje.getAttribute("aria-describedby")).textContent = poruka;
  polje.classList.add("is-invalid");
  polje.setAttribute("aria-invalid", "true");
}

function skloniGresku(polje) {
  document.getElementById(polje.getAttribute("aria-describedby")).textContent = "";
  polje.classList.remove("is-invalid");
  polje.removeAttribute("aria-invalid");
}

[korisnickoIme, lozinka].forEach((polje) => polje.addEventListener("input", () => skloniGresku(polje)));

adminForm.addEventListener("submit", (e) => {
  let ispravno = true;
  if (korisnickoIme.value.trim() === "") {
    prikaziGresku(korisnickoIme, "Upiši korisničko ime.");
    ispravno = false;
  }
  if (lozinka.value === "") {
    prikaziGresku(lozinka, "Unesi lozinku.");
    ispravno = false;
  }
  if (!ispravno) {
    e.preventDefault();
    (korisnickoIme.classList.contains("is-invalid") ? korisnickoIme : lozinka).focus();
  }
});
