<?php
/**
 * Izgled jednog obaveštenja – isti na početnoj, na "Sve poruke" i kod administratora.
 *
 * Upotreba:
 *   require_once __DIR__ . '/delovi/obavestenja_izgled.php';
 *   $izgled = izgledObavestenja($poruka);   // $poruka = red iz ObavestenjeRepository
 *   $izgled['ikonica'], $izgled['boja'], $izgled['kome']
 */
if (!function_exists('izgledObavestenja')) {
    function izgledObavestenja(array $o): array
    {
        $grupa = $o['ciljna_grupa'] ?? (!empty($o['menza_id']) ? 'menza' : 'svi');

        if ($grupa === 'menza') {
            return ['ikonica' => 'upozorenje', 'boja' => 'tint-orange', 'kome' => $o['menza'] ?? 'Menza'];
        }
        if ($grupa === 'dom') {
            return ['ikonica' => 'kuca', 'boja' => 'tint-green', 'kome' => $o['dom'] ?? 'Dom'];
        }
        return ['ikonica' => 'megafon', 'boja' => 'tint-blue', 'kome' => 'Svi studenti'];
    }
}
