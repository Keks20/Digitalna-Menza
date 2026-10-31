<?php

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/Menza.php';

class MenzaRepository extends BaseRepository
{
    protected $table = 'menze';

    protected function mapRow(array $row)
    {
        return new Menza($row);
    }

    public function create(array $data)
    {
        $stmt = $this->conn->prepare('INSERT INTO menze (naziv, adresa, kapacitet_dnevno) VALUES (?, ?, ?)');
        $stmt->bind_param('ssi', $data['naziv'], $data['adresa'], $data['kapacitet_dnevno']);
        $stmt->execute();
        return $this->conn->insert_id;
    }

    public function update(int $id, array $data)
    {
        $stmt = $this->conn->prepare('UPDATE menze SET naziv = ?, adresa = ?, kapacitet_dnevno = ? WHERE id = ?');
        $stmt->bind_param('ssii', $data['naziv'], $data['adresa'], $data['kapacitet_dnevno'], $id);
        return $stmt->execute();
    }

    public function list(array $conditions = [])
    {
        $result = $this->conn->query('SELECT * FROM menze ORDER BY id');
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
