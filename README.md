# Digitalna menza

Timski projekat za predmet **Inovacije u IT-u** – veb aplikacija za Studentski centar Kragujevac:
dopuna studentske kartice, kupovina obroka (QR bon), plaćanje smeštaja, dnevni i nedeljni jelovnik
i mini kviz o aplikaciji.

Tehnologije: PHP 8 (OOP), MySQL/MariaDB (mysqli, prepared statements), Bootstrap 5, XAMPP.

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

Test podaci se računaju od dana uvoza (jelovnik za ovu i sledeću nedelju, obroci i stanarina
za tekući mesec). Pred odbranu je dovoljno ponovo uvesti `schema.sql` pa `seed.sql`.

## Struktura

```
index.php                preusmerava na prijavu ili početnu
login.php / logout.php   prijava brojem kartice i odjava
pocetna.php              početna strana studenta
includes/                provera_prijave.php – zaštita stranica za prijavljene
src/                     klase: Database, CrudInterface, BaseRepository, Auth, modeli i repozitorijumi
db/                      schema.sql (struktura) i seed.sql (test podaci)
css/, js/                stilovi i JavaScript
config.php               podešavanja baze (config.sample.php / config.local.php)
```

Svaka tabela ima model (npr. `Kartica`) i repozitorijum (npr. `KarticaRepository`) koji nasleđuje
apstraktni `BaseRepository`, a on implementira `CrudInterface` (create, read, update, delete, list).

## Nova stranica za prijavljene

```php
<?php
require_once __DIR__ . '/includes/provera_prijave.php';   // daje $auth, $studentId, $karticaId
require_once __DIR__ . '/src/KarticaRepository.php';

$kartica = (new KarticaRepository())->read($karticaId);
?>
```

## Rad sa Git-om

1. Pre rada: **Pull** (preuzmi izmene kolege).
2. Ne menjajte isti fajl u isto vreme – PHP logika je na vrhu fajla (backend), HTML ispod (frontend).
3. Posle rada: **Commit** sa jasnom porukom (npr. `Dodata stranica jelovnika`) i **Push**.
