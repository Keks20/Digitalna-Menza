<?php
/**
 * LOGIKA: student je otvorio zvonce – sva dosadašnja obaveštenja postaju pročitana.
 * Nema prikaza: stranicu poziva js/zvonce.js (fetch) i dobija JSON.
 *
 * POST: csrf_token
 * Odgovor: {"uspeh": true}                       – obaveštenja su označena kao pročitana
 *          {"uspeh": false, "poruka": "..."}     – sa statusom 405 (nije POST) ili 403 (pogrešan token)
 * Bez prijave: preusmeravanje na login.php (iz includes/provera_prijave.php).
 */
require_once __DIR__ . '/includes/provera_prijave.php';
require_once __DIR__ . '/src/StudentRepository.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['uspeh' => false, 'poruka' => 'Dozvoljen je samo POST.']);
    exit;
}

if (!$auth->validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(403);
    echo json_encode(['uspeh' => false, 'poruka' => 'Sesija je istekla.']);
    exit;
}

(new StudentRepository())->oznaciObavestenjaProcitanim($studentId);

echo json_encode(['uspeh' => true]);
