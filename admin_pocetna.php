<?php
/**
 * LOGIKA: administratorski pregled (backend – Luka).
 * Izgled je u views/admin_pocetna.view.php.
 *
 * Za sada samo pozdrav i meni; statistika (uplate danas, prodati obroci,
 * izdati obroci po menzama, poslednje uplate) dolazi u nedelji 6 po planu.
 *
 * Prikaz dobija:
 *   $admin   Admin   ime_prezime, inicijali(), nazivUloge()
 *   $danas   string  'Y-m-d'
 */
$dozvoljeneUloge = ['admin'];
require_once __DIR__ . '/includes/provera_admina.php';
require_once __DIR__ . '/src/Format.php';

$danas = date('Y-m-d');

require __DIR__ . '/views/admin_pocetna.view.php';
