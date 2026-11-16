<?php
/**
 * LOGIKA: strana za greške – stranica ne postoji, pristup nije dozvoljen ili greška na serveru.
 * Izgled je u views/greska.view.php.
 *
 * Adresa: greska.php?kod=404   (403 | 404 | 500; sve ostalo se prikazuje kao 404)
 * Apache je otvara sam preko ErrorDocument iz .htaccess u korenu, a kod neuhvaćenog izuzetka
 * isti prikaz ispisuje src/Greske.php.
 *
 * Stranica ne koristi bazu ni sesiju, da bi radila i kad baza ne radi.
 *
 * Prikaz dobija:
 *   $kodGreske  int  403 | 404 | 500
 */
const DOZVOLJENI_KODOVI = [403, 404, 500];

$kodGreske = (int)($_GET['kod'] ?? 404);
if (!in_array($kodGreske, DOZVOLJENI_KODOVI, true)) {
    $kodGreske = 404;
}

http_response_code($kodGreske);
header('Cache-Control: no-store');

require __DIR__ . '/views/greska.view.php';
