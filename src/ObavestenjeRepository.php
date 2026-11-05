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
        $menzaId = $ciljnaGrupa === 'menza' ? ($data['menza_id'] ?? null) : null;
        $dom = $ciljnaGrupa === 'dom' ? ($data['dom'] ?? null) : null;
        $adminId = $data['admin_id'] ?? null;
        $stmt = $this->conn->prepare('INSERT INTO obavestenja (naslov, tekst, ciljna_grupa, menza_id, dom, admin_id) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('sssisi', $data['naslov'], $data['tekst'], $ciljnaGrupa, $menzaId, $dom, $adminId);
        $stmt->execute();
        return $this->conn->insert_id;
    }

    /**
     * Izmena teksta i kome je obaveštenje namenjeno; datum objave ostaje isti.
     */
    public function update(int $id, array $data)
    {
        $ciljnaGrupa = $data['ciljna_grupa'] ?? 'svi';
        $menzaId = $ciljnaGrupa === 'menza' ? ($data['menza_id'] ?? null) : null;
        $dom = $ciljnaGrupa === 'dom' ? ($data['dom'] ?? null) : null;
        $stmt = $this->conn->prepare('UPDATE obavestenja SET naslov = ?, tekst = ?, ciljna_grupa = ?, menza_id = ?, dom = ? WHERE id = ?');
        $stmt->bind_param('sssisi', $data['naslov'], $data['tekst'], $ciljnaGrupa, $menzaId, $dom, $id);
        return $stmt->execute();
    }

    /**
     * Najnovija obaveštenja (za administratora), sa nazivom menze i imenom autora: ['limit' => 20].
     */
    public function list(array $conditions = [])
    {
        $limit = (int)($conditions['limit'] ?? 20);
        $stmt = $this->conn->prepare(
            'SELECT o.*, m.naziv AS menza, a.ime_prezime AS autor
             FROM obavestenja o
             LEFT JOIN menze m ON m.id = o.menza_id
             LEFT JOIN admini a ON a.id = o.admin_id
             ORDER BY o.kreirano DESC, o.id DESC
             LIMIT ?'
        );
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Obaveštenja koja student treba da vidi, najnovija prva:
     *   – za sve studente,
     *   – za pojedinačne menze (student može da jede u bilo kojoj menzi),
     *   – za dom samo ako student ima aktivan ugovor u tom domu.
     */
    public function zaStudenta(int $studentId, int $limit = 20): array
    {
        $stmt = $this->conn->prepare(
            "SELECT o.*, m.naziv AS menza
             FROM obavestenja o
             LEFT JOIN menze m ON m.id = o.menza_id
             WHERE o.ciljna_grupa IN ('svi', 'menza')
                OR (o.ciljna_grupa = 'dom' AND o.dom IN (
                       SELECT s.dom
                       FROM smestaj_ugovori u
                       JOIN sobe s ON s.id = u.soba_id
                       WHERE u.student_id = ? AND u.aktivan = 1))
             ORDER BY o.kreirano DESC, o.id DESC
             LIMIT ?"
        );
        $stmt->bind_param('ii', $studentId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
