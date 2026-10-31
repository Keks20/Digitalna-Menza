<?php

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/Student.php';

class StudentRepository extends BaseRepository
{
    protected $table = 'studenti';

    protected function mapRow(array $row)
    {
        return new Student($row);
    }

    public function create(array $data)
    {
        $stmt = $this->conn->prepare('INSERT INTO studenti (ime, prezime, broj_indeksa, ustanova, email) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('sssss', $data['ime'], $data['prezime'], $data['broj_indeksa'], $data['ustanova'], $data['email']);
        $stmt->execute();
        return $this->conn->insert_id;
    }

    public function update(int $id, array $data)
    {
        $stmt = $this->conn->prepare('UPDATE studenti SET ime = ?, prezime = ?, broj_indeksa = ?, ustanova = ?, email = ? WHERE id = ?');
        $stmt->bind_param('sssssi', $data['ime'], $data['prezime'], $data['broj_indeksa'], $data['ustanova'], $data['email'], $id);
        return $stmt->execute();
    }

    public function list(array $conditions = [])
    {
        $result = $this->conn->query('SELECT * FROM studenti ORDER BY prezime, ime');
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
