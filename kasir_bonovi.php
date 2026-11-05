<?php
/**
 * LOGIKA: provera QR bonova na kasi.
 * Izgled je u views/kasir_bonovi.view.php.
 *
 * Na stranicu smeju kasir i administrator.
 * Za sada samo pozdrav; skeniranje QR bona i izdavanje obroka se dodaju u sledećoj fazi.
 *
 * Prikaz dobija:
 *   $admin   Admin   ime_prezime, inicijali(), nazivUloge()
 */
$dozvoljeneUloge = ['kasir', 'admin'];
require_once __DIR__ . '/includes/provera_admina.php';

require __DIR__ . '/views/kasir_bonovi.view.php';
