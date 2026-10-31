<?php
/**
 * Uključi na vrh svake stranice koja je samo za prijavljene studente:
 *   require_once __DIR__ . '/includes/provera_prijave.php';
 * Posle toga su dostupni $auth, $studentId i $karticaId.
 */
require_once __DIR__ . '/../src/Auth.php';

$auth = new Auth();
if (!$auth->check()) {
    header('Location: login.php');
    exit;
}

$studentId = $auth->studentId();
$karticaId = $auth->karticaId();
