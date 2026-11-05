<?php

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/Transakcija.php';

/**
 * Evidencija novca. Sama transakcija NE menja stanje kartice –
 * to radi uplata/kupovina u jednoj transakciji baze zajedno sa ovim zapisom.
 */
class TransakcijaRepository extends BaseRepository
{
    protected $table = 'transakcije';

    protected function mapRow(array $row)
    {
        return new Transakcija($row);
    }

    public function create(array $data)
    {
        $status = $data['status'] ?? 'uspesno';
        $stanjePosle = $data['stanje_posle'] ?? null;
        $stmt = $this->conn->prepare('INSERT INTO transakcije (kartica_id, tip, nacin, iznos, stanje_posle, status, opis) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('issddss', $data['kartica_id'], $data['tip'], $data['nacin'], $data['iznos'], $stanjePosle, $status, $data['opis']);
        $stmt->execute();
        return $this->conn->insert_id;
    }

    /**
     * Menja se samo status (admin potvrđuje ili odbija IPS uplatu).
     */
    public function update(int $id, array $data)
    {
        $stmt = $this->conn->prepare('UPDATE transakcije SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $data['status'], $id);
        return $stmt->execute();
    }

    /**
     * Transakcije se ne brišu – to je evidencija novca.
     * Pogrešna uplata se ispravlja novom transakcijom ili statusom 'odbijeno'.
     */
    public function delete(int $id)
    {
        return false;
    }

    /**
     * Istorija jedne kartice, npr. ['kartica_id' => 1, 'tip' => 'uplata', 'limit' => 20].
     */
    public function list(array $conditions = [])
    {
        $karticaId = (int)($conditions['kartica_id'] ?? 0);
        $tip = $conditions['tip'] ?? '';
        $limit = (int)($conditions['limit'] ?? 50);

        if ($tip !== '') {
            $stmt = $this->conn->prepare('SELECT * FROM transakcije WHERE kartica_id = ? AND tip = ? ORDER BY kreirano DESC, id DESC LIMIT ?');
            $stmt->bind_param('isi', $karticaId, $tip, $limit);
        } else {
            $stmt = $this->conn->prepare('SELECT * FROM transakcije WHERE kartica_id = ? ORDER BY kreirano DESC, id DESC LIMIT ?');
            $stmt->bind_param('ii', $karticaId, $limit);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Poslednja uspešna uplata (za karticu "Poslednja uplata" na početnoj).
     */
    public function poslednjaUplata(int $karticaId): ?Transakcija
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM transakcije
             WHERE kartica_id = ? AND tip = 'uplata' AND status = 'uspesno'
             ORDER BY kreirano DESC, id DESC LIMIT 1"
        );
        $stmt->bind_param('i', $karticaId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? $this->mapRow($row) : null;
    }
}
