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


