<?php

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/Admin.php';

class AdminRepository extends BaseRepository
{
    protected $table = 'admini';

    protected function mapRow(array $row)
    {
        return new Admin($row);
    }

    /**
     * Novi zaposleni; lozinka stiže kao običan tekst i ovde se hešira.
     */
    public function create(array $data)
    {
        $hash = password_hash($data['lozinka'], PASSWORD_DEFAULT);
        $uloga = $data['uloga'] ?? 'admin';
        $stmt = $this->conn->prepare('INSERT INTO admini (korisnicko_ime, lozinka_hash, ime_prezime, uloga) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $data['korisnicko_ime'], $hash, $data['ime_prezime'], $uloga);
        $stmt->execute();
        return $this->conn->insert_id;
    }

    /**
     * Override generičkog read() – ne vraćamo heš lozinke.
     */
    public function read(int $id)
    {
        $stmt = $this->conn->prepare('SELECT id, korisnicko_ime, ime_prezime, uloga, kreirano FROM admini WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? $this->mapRow($row) : null;
    }

    /**
     * Menjaju se ime i uloga (lozinka se menja posebno, uz proveru stare lozinke).
     */
    public function update(int $id, array $data)
    {
        $stmt = $this->conn->prepare('UPDATE admini SET ime_prezime = ?, uloga = ? WHERE id = ?');
        $stmt->bind_param('ssi', $data['ime_prezime'], $data['uloga'], $id);
        return $stmt->execute();
    }

    /**
     * Svi zaposleni, bez heševa lozinki: ['uloga' => 'kasir'] za samo kasire.
     */
    public function list(array $conditions = [])
    {
        $uloga = $conditions['uloga'] ?? '';
        if ($uloga !== '') {
            $stmt = $this->conn->prepare('SELECT id, korisnicko_ime, ime_prezime, uloga, kreirano FROM admini WHERE uloga = ? ORDER BY ime_prezime');
            $stmt->bind_param('s', $uloga);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        $result = $this->conn->query('SELECT id, korisnicko_ime, ime_prezime, uloga, kreirano FROM admini ORDER BY ime_prezime');
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
