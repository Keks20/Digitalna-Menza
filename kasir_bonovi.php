<?php
/**
 * LOGIKA: provera QR bonova na kasi (backend – Luka).
 * Izgled je u views/kasir_bonovi.view.php.
 *
 * Na stranicu smeju kasir i administrator.
 * Skeniranje QR bona i izdavanje obroka dolaze u nedelji 6 po planu – za sada samo pozdrav.
 *
 * Prikaz dobija:
 *   $admin   Admin   ime_prezime, inicijali(), nazivUloge()
 */
$dozvoljeneUloge = ['kasir', 'admin'];
require_once __DIR__ . '/includes/provera_admina.php';

require __DIR__ . '/views/kasir_bonovi.view.php';
