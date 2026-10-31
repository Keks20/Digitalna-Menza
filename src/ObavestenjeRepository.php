<?php

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/Obavestenje.php';

class ObavestenjeRepository extends BaseRepository
{
    protected $table = 'obavestenja';

    protected function mapRow(array $row)
    {
        return new Obavestenje($row);
    }

    public function create(array $data)
    {
        $ciljnaGrupa = $data['ciljna_grupa'] ?? 'svi';
        $menzaId = $data['menza_id'] ?? null;
        $dom = $data['dom'] ?? null;
        $adminId = $data['admin_id'] ?? null;
        $stmt = $this->conn->prepare('INSERT INTO obavestenja (naslov, tekst, ciljna_grupa, menza_id, dom, admin_id) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('sssisi', $data['naslov'], $data['tekst'], $ciljnaGrupa, $menzaId, $dom, $adminId);
        $stmt->execute();
        return $this->conn->insert_id;
    }

    public function update(int $id, array $data)
    {
        $stmt = $this->conn->prepare('UPDATE obavestenja SET naslov = ?, tekst = ? WHERE id = ?');
        $stmt->bind_param('ssi', $data['naslov'], $data['tekst'], $id);
        return $stmt->execute();
    }

    /**
     * Najnovija obaveštenja sa nazivom menze: ['limit' => 3].
     */
    public function list(array $conditions = [])
    {
        $limit = (int)($conditions['limit'] ?? 20);
        $stmt = $this->conn->prepare(
            'SELECT o.*, m.naziv AS menza
             FROM obavestenja o LEFT JOIN menze m ON m.id = o.menza_id
             ORDER BY o.kreirano DESC, o.id DESC
             LIMIT ?'
        );
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
