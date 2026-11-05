<?php

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/SmestajZaduzenje.php';

/**
 * Zaduženja za stanarinu (tabela smestaj_zaduzenja, uz podatke iz ugovora i sobe).
 */
class SmestajRepository extends BaseRepository
{
    protected $table = 'smestaj_zaduzenja';

    protected function mapRow(array $row)
    {
        return new SmestajZaduzenje($row);
    }

    /**
     * Admin unosi mesečno zaduženje; rok je 20. u mesecu.
     */
    public function create(array $data)
    {
        $rok = date('Y-m-20', strtotime($data['mesec']));
        $stmt = $this->conn->prepare('INSERT INTO smestaj_zaduzenja (ugovor_id, mesec, iznos, rok_placanja) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('isds', $data['ugovor_id'], $data['mesec'], $data['iznos'], $rok);
        $stmt->execute();
        return $this->conn->insert_id;
    }

    /**
     * Označava zaduženje kao plaćeno (poziva se posle uspešne transakcije).
     */
    public function update(int $id, array $data)
    {
        $stmt = $this->conn->prepare('UPDATE smestaj_zaduzenja SET placeno = 1, datum_placanja = NOW(), transakcija_id = ? WHERE id = ?');
        $stmt->bind_param('ii', $data['transakcija_id'], $id);
        return $stmt->execute();
    }

    /**
     * Sva zaduženja jednog studenta, najnovija prva: ['student_id' => 1].
     */
    public function list(array $conditions = [])
    {
        $stmt = $this->conn->prepare(
            'SELECT z.*, s.dom, s.paviljon, s.broj AS soba
             FROM smestaj_zaduzenja z
             JOIN smestaj_ugovori u ON u.id = z.ugovor_id
             JOIN sobe s ON s.id = u.soba_id
             WHERE u.student_id = ?
             ORDER BY z.mesec DESC'
        );
        $stmt->bind_param('i', $conditions['student_id']);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Zaduženje za karticu "Stanarina" na početnoj:
     * najstarije neplaćeno, a ako je sve plaćeno – zaduženje za tekući mesec.
     * Vraća null ako student ne živi u domu.
     */
    public function tekuceZaduzenje(int $studentId): ?SmestajZaduzenje
    {
        $stmt = $this->conn->prepare(
            'SELECT z.*, s.dom, s.paviljon, s.broj AS soba
             FROM smestaj_zaduzenja z
             JOIN smestaj_ugovori u ON u.id = z.ugovor_id
             JOIN sobe s ON s.id = u.soba_id
             WHERE u.student_id = ? AND u.aktivan = 1
               AND (z.placeno = 0 OR z.mesec = DATE_FORMAT(CURDATE(), \'%Y-%m-01\'))
             ORDER BY z.placeno ASC, z.mesec ASC
             LIMIT 1'
        );
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? $this->mapRow($row) : null;
    }

    /**
     * Nazivi svih domova (za izbor "Kome se šalje" kod obaveštenja): ['Dom 1', ...]
     */
    public function domovi(): array
    {
        $result = $this->conn->query('SELECT DISTINCT dom FROM sobe ORDER BY dom');
        return array_column($result->fetch_all(MYSQLI_ASSOC), 'dom');
    }
}
