# Digitalna menza

Timski projekat za predmet **Inovacije u IT-u** – veb aplikacija za Studentski centar Kragujevac:
dopuna studentske kartice, kupovina obroka (QR bon), plaćanje smeštaja, dnevni i nedeljni jelovnik
i mini kviz o aplikaciji.

Tehnologije: PHP 8 (OOP), MySQL/MariaDB (mysqli, prepared statements), Bootstrap 5, XAMPP.

## Dizajn (Figma)

- Dizajn (svi ekrani, računar i telefon): https://www.figma.com/design/j4a3f4dpUNIzJ9wZkviu2d/Digitalna-menza
- Klikabilni prototip: https://www.figma.com/proto/j4a3f4dpUNIzJ9wZkviu2d/Digitalna-menza?node-id=2-2&p=f&viewport=75%2C118%2C0.11&t=SGA1uBfy2lWihKBK-1&scaling=min-zoom&content-scaling=fixed&starting-point-node-id=2%3A2&show-proto-sidebar=1&page-id=0%3A1 
- Početne tačke prototipa: **Računar**, **Telefon**, **Administracija**, **Kasir**
- Slike ekrana (PNG) su u `docs/dizajn/`

## Pokretanje

1. Projekat mora biti u `htdocs` folderu XAMPP-a (npr. `htdocs\menza`).
2. U XAMPP Control Panel-u uključi **Apache** i **MySQL**.
3. Otvori `http://localhost/phpmyadmin` → **Import** → izaberi `db/schema.sql` → **Import**.
   Zatim isto za `db/seed.sql` (test podaci).
4. Otvori `http://localhost/menza/`.

> Live Server više ne radi za stranice sa PHP-om – sajt se otvara samo preko `localhost`.
> Ako ti MySQL nije `root` bez lozinke, kopiraj `config.sample.php` u `config.local.php` i promeni podatke.

## Test nalozi

| Uloga | Prijava | Lozinka |
| --- | --- | --- |
| Student (Marko Marković) | kartica `0012 3456 7890` | `lozinka123` |
| Ostali studenti (29) | kartice iz tabele `kartice` | `lozinka123` |
| Blokirana kartica | `0012 3456 3310` | `lozinka123` |
| Admin | `admin` | `admin123` |
| Kasir | `kasir` | `kasir123` |

Studenti se prijavljuju na `http://localhost/menza/`, a zaposleni (admin i kasir) na
`http://localhost/menza/admin_login.php`.

Test podaci se računaju od dana uvoza (jelovnik za ovu i sledeću nedelju, obroci i stanarina
za tekući mesec). Pred odbranu je dovoljno ponovo uvesti `schema.sql` pa `seed.sql`.

## Struktura

Svaka stranica ima **dva fajla**: logiku (backend) u korenu i izgled (frontend) u `views/`.
Tako backend i frontend nikad ne menjaju isti fajl i mogu da rade istovremeno.

```
index.php                preusmerava na prijavu ili početnu
login.php / logout.php   prijava brojem kartice i odjava
pocetna.php              LOGIKA početne: upiti u bazu, promenljive za prikaz
jelovnik.php             LOGIKA jelovnika: izbor menze i dana (?menza=1&dan=2026-11-05)
admin_login.php          prijava zaposlenih (admin i kasir), admin_logout.php – odjava
admin_pocetna.php        administratorski pregled (samo admin)
kasir_bonovi.php         provera QR bonova (kasir i admin)
views/                   IZGLED stranica (samo HTML i prikaz promenljivih, bez upita u bazu)
  login.view.php
  pocetna.view.php
  jelovnik.view.php
  admin_login.view.php, admin_pocetna.view.php, kasir_bonovi.view.php
  delovi/                zajednički delovi: zaglavlje.php (<head>), meni.php, admin_meni.php, podnozje.php
includes/                provera_prijave.php (student) i provera_admina.php (zaposleni, po ulozi)
src/                     klase: Database, CrudInterface, BaseRepository, BaseAuth (Auth, AdminAuth), modeli i repozitorijumi
db/                      schema.sql (struktura) i seed.sql (test podaci)
css/, js/                stilovi i JavaScript
config.php               podešavanja baze (config.sample.php / config.local.php)
```

Svaka tabela ima model (npr. `Kartica`) i repozitorijum (npr. `KarticaRepository`) koji nasleđuje
apstraktni `BaseRepository`, a on implementira `CrudInterface` (create, read, update, delete, list).

| Ko | Menja |
| --- | --- |
| Backend (Luka) | `src/`, `db/`, `includes/`, stranice u korenu (`pocetna.php`…) |
| Frontend (kolega) | `views/`, `css/`, `js/` |

## Nova stranica

**1. Logika** – npr. `jelovnik.php` u korenu. Na vrhu je komentar sa spiskom promenljivih koje prikaz dobija:

```php
<?php
/**
 * LOGIKA: jelovnik (backend).
 * Prikaz dobija: $menze, $jelovnik, $tipoviObroka
 */
require_once __DIR__ . '/includes/provera_prijave.php';   // daje $auth, $studentId, $karticaId
require_once __DIR__ . '/src/JelovnikRepository.php';

$jelovnik = (new JelovnikRepository())->zaDan($menzaId, $dan);

require __DIR__ . '/views/jelovnik.view.php';
```

**2. Izgled** – `views/jelovnik.view.php` koristi samo te promenljive:

```php
<?php
$naslovStrane = 'Jelovnik';
$aktivnaStrana = 'jelovnik.php';
require __DIR__ . '/delovi/zaglavlje.php';
require __DIR__ . '/delovi/meni.php';
?>
  <main> ... <?php echo htmlspecialchars($stavka->jelo); ?> ... </main>
<?php require __DIR__ . '/delovi/podnozje.php'; ?>
```

Meni sam prikaže link ka novoj stranici čim njen fajl postoji u korenu.
Folder `views/` je zaštićen sa `.htaccess`, pa se prikaz ne može otvoriti direktno iz pregledača.
