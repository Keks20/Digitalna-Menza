<?php
/**
 * Uključi na vrh svake stranice koja je samo za zaposlene SC-a.
 * PRE uključivanja navedi koje uloge smeju na stranicu:
 *
 *   $dozvoljeneUloge = ['admin'];            // samo administrator (podrazumevano)
 *   $dozvoljeneUloge = ['admin', 'kasir'];   // i kasir
 *   require_once __DIR__ . '/includes/provera_admina.php';
 *
 * Posle toga su dostupni $adminAuth, $adminId i $admin (objekat Admin).
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
