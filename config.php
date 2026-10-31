<?php
// Učitavanje podešavanja: lokalni config.local.php ako postoji, inače config.sample.php
if (file_exists(__DIR__ . '/config.local.php')) {
    return include __DIR__ . '/config.local.php';
}

return include __DIR__ . '/config.sample.php';
