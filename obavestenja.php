<?php
/**
 * LOGIKA: sva obaveštenja za prijavljenog studenta ("Sva obaveštenja" iz zvonceta).
 * Izgled je u views/obavestenja.view.php.
 *
 * Student vidi obaveštenja za sve studente, za menze i za dom u kome živi.
 *
 * Prikaz dobija:
 *   $student  Student  za zaglavlje (ime, inicijali)
 *   $poruke   array    [['naslov', 'tekst', 'kreirano', 'ciljna_grupa', 'menza_id', 'menza', 'dom', 'novo'], ...] najnovije prvo;
 *                      novo = 1 za obaveštenja koja student do sada nije video
 *
 * Otvaranjem ove stranice sva obaveštenja postaju pročitana (broj na zvoncetu nestaje).
 */
require_once __DIR__ . '/includes/provera_prijave.php';
require_once __DIR__ . '/src/StudentRepository.php';
require_once __DIR__ . '/src/ObavestenjeRepository.php';
require_once __DIR__ . '/src/Format.php';

const BROJ_OBAVESTENJA = 50;

$student = (new StudentRepository())->read($studentId);
$poruke = (new ObavestenjeRepository())->zaStudenta($studentId, BROJ_OBAVESTENJA);

// Lista je učitana sa oznakom "novo", pa tek onda sve postaje pročitano
if ($zvonce['neprocitano'] > 0) {
    (new StudentRepository())->oznaciObavestenjaProcitanim($studentId);
    $zvonce['neprocitano'] = 0;
}

require __DIR__ . '/views/obavestenja.view.php';
