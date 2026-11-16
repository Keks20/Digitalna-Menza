<?php
/**
 * Uključi na vrh svake stranice koja je samo za zaposlene SC-a.
 * PRE uključivanja navedi koje uloge smeju na stranicu:
 *
 *   $dozvoljeneUloge = ['admin'];            // samo administrator (podrazumevano)
 *   $dozvoljeneUloge = ['admin', 'kasir'];   // i kasir
 *   require_once __DIR__ . '/includes/provera_admina.php';
 *
 * Posle toga su dostupni $adminAuth, $adminId, $admin (objekat Admin) i $zvonceAdmina.
 *
 * $zvonceAdmina su podaci za zvonce u zaglavlju (views/delovi/korisnik.php):
 *   ['na_cekanju' => 2]   koliko IPS uplata čeka potvrdu banke; null za kasira (kasir nema zvonce)
 */
require_once __DIR__ . '/../src/AdminAuth.php';
require_once __DIR__ . '/../src/AdminRepository.php';

$dozvoljeneUloge = $dozvoljeneUloge ?? ['admin'];

$adminAuth = new AdminAuth();

// Nije prijavljen kao zaposleni -> na administratorsku prijavu
if (!$adminAuth->check()) {
    header('Location: admin_login.php');
    exit;
}

// Prijavljen je, ali njegova uloga ne sme ovde (npr. kasir na unos jelovnika) -> na njegovu početnu
if (!$adminAuth->imaUlogu($dozvoljeneUloge)) {
    header('Location: ' . $adminAuth->pocetnaStrana());
    exit;
}

$adminId = $adminAuth->adminId();
$admin = (new AdminRepository())->read($adminId);

// Nalog je obrisan u međuvremenu -> odjava
if (!$admin) {
    $adminAuth->logout();
    header('Location: admin_login.php');
    exit;
}

// IPS uplate kojima je prošao rok za potvrdu postaju 'isteklo' pre nego što stranica pročita uplate
require_once __DIR__ . '/../src/TransakcijaRepository.php';
$uplateZaZvonce = new TransakcijaRepository();
$uplateZaZvonce->oznaciIstekleUplate();

// Zvonce u zaglavlju (views/delovi/korisnik.php): administrator vidi koliko IPS uplata čeka potvrdu banke
$zvonceAdmina = $admin->jeAdmin() ? ['na_cekanju' => $uplateZaZvonce->brojUplataNaCekanju()] : null;
unset($uplateZaZvonce);
