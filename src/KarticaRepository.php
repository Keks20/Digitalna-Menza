<?php

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/Kartica.php';

class KarticaRepository extends BaseRepository
{
    protected $table = 'kartice';

    protected function mapRow(array $row)
    {
        return new Kartica($row);
    }

    /**
     * Nova kartica; lozinka stiže kao običan tekst i ovde se hešira.
     * Stanje je uvek 0 – novac ulazi samo kroz transakcije.
     */
    public function create(array $data)
    {
        $hash = password_hash($data['lozinka'], PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare('INSERT INTO kartice (student_id, broj_kartice, lozinka_hash) VALUES (?, ?, ?)');
        $stmt->bind_param('iss', $data['student_id'], $data['broj_kartice'], $hash);
        $stmt->execute();
        return $this->conn->insert_id;
    }

    /**
     * Override generičkog read() – ne vraćamo heš lozinke.
     */
    public function read(int $id)
    {
        $stmt = $this->conn->prepare('SELECT id, student_id, broj_kartice, stanje, status, kreirano FROM kartice WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? $this->mapRow($row) : null;
    }

    /**
     * Menja se samo status (aktivna / blokirana).
     * Stanje se nikad ne menja ovde, već kroz uplatu ili kupovinu (transakcije).
     */
    public function update(int $id, array $data)
    {
        $stmt = $this->conn->prepare('UPDATE kartice SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $data['status'], $id);
        return $stmt->execute();
    }

    public function list(array $conditions = [])
    {
        $sql = 'SELECT k.id, k.broj_kartice, k.stanje, k.status, s.ime, s.prezime
                FROM kartice k JOIN studenti s ON s.id = k.student_id
                ORDER BY s.prezime, s.ime';
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Promena lozinke iz profila: proverava staru lozinku pre upisa nove.
     */
    public function promeniLozinku(int $id, string $staraLozinka, string $novaLozinka): bool
    {
        $stmt = $this->conn->prepare('SELECT lozinka_hash FROM kartice WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row || !password_verify($staraLozinka, $row['lozinka_hash'])) {
            return false;
        }

        $hash = password_hash($novaLozinka, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare('UPDATE kartice SET lozinka_hash = ? WHERE id = ?');
        $stmt->bind_param('si', $hash, $id);
        return $stmt->execute();
    }
}
