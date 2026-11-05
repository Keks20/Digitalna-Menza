<?php
/**
 * LOGIKA: administratorski pregled.
 * Izgled je u views/admin_pocetna.view.php.
 *
 * Za sada samo pozdrav i meni; statistika (uplate danas, prodati obroci,
 * izdati obroci po menzama, poslednje uplate) se dodaje u sledećoj fazi.
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
