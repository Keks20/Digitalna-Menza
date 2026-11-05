<?php

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/KupljeniObrok.php';

class KupljeniObrokRepository extends BaseRepository
{
    protected $table = 'kupljeni_obroci';

    protected function mapRow(array $row)
    {
        return new KupljeniObrok($row);
    }

    public function create(array $data)
    {
        $transakcijaId = $data['transakcija_id'] ?? null;
        $stmt = $this->conn->prepare('INSERT INTO kupljeni_obroci (kartica_id, tip_obroka_id, mesec, kolicina, cena_po_obroku, transakcija_id) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('iisidi', $data['kartica_id'], $data['tip_obroka_id'], $data['mesec'], $data['kolicina'], $data['cena_po_obroku'], $transakcijaId);
        $stmt->execute();
        return $this->conn->insert_id;
    }

    public function update(int $id, array $data)
    {
        $stmt = $this->conn->prepare('UPDATE kupljeni_obroci SET kolicina = ? WHERE id = ?');
        $stmt->bind_param('ii', $data['kolicina'], $id);
        return $stmt->execute();
    }

    /**
     * Paketi jedne kartice za jedan mesec, npr. ['kartica_id' => 1, 'mesec' => '2026-11-01'].
     */
    public function list(array $conditions = [])
    {
        $stmt = $this->conn->prepare('SELECT * FROM kupljeni_obroci WHERE kartica_id = ? AND mesec = ? ORDER BY tip_obroka_id');
        $stmt->bind_param('is', $conditions['kartica_id'], $conditions['mesec']);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Koliko je obroka kupljeno, iskorišćeno i preostalo u mesecu, po tipu obroka.
     * Vraća red za svaki tip (i kad ništa nije kupljeno), npr.
     * [['tip_obroka_id' => 2, 'naziv' => 'Ručak', 'kupljeno' => 10, 'iskorisceno' => 3, 'preostalo' => 7], ...]
     */
    public function stanjeZaMesec(int $karticaId, string $mesec): array
    {
        $stmt = $this->conn->prepare(
            'SELECT t.id AS tip_obroka_id, t.naziv,
                    COALESCE((SELECT SUM(k.kolicina) FROM kupljeni_obroci k
                              WHERE k.kartica_id = ? AND k.tip_obroka_id = t.id AND k.mesec = ?), 0) AS kupljeno,
                    (SELECT COUNT(*) FROM iskorisceni_obroci i
                     WHERE i.kartica_id = ? AND i.tip_obroka_id = t.id
                       AND i.iskorisceno >= ? AND i.iskorisceno < ? + INTERVAL 1 MONTH) AS iskorisceno
             FROM tipovi_obroka t
             ORDER BY t.id'
        );
        $stmt->bind_param('isiss', $karticaId, $mesec, $karticaId, $mesec, $mesec);
        $stmt->execute();

        $stanje = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $row['kupljeno'] = (int)$row['kupljeno'];
            $row['iskorisceno'] = (int)$row['iskorisceno'];
            $row['preostalo'] = max(0, $row['kupljeno'] - $row['iskorisceno']);
            $stanje[] = $row;
        }
        return $stanje;
    }

    /**
     * Poslednji izdati obroci (za "Poslednja iskorišćenja" na stranici Moji obroci), najnoviji prvi:
     * [['tip_obroka_id' => 1, 'obrok' => 'Doručak', 'menza' => 'Menza Ekonomski', 'iskorisceno' => '2026-10-13 08:12:00'], ...]
     */
    public function poslednjaIskoriscenja(int $karticaId, int $limit = 5): array
    {
        $stmt = $this->conn->prepare(
            'SELECT i.tip_obroka_id, t.naziv AS obrok, m.naziv AS menza, i.iskorisceno
             FROM iskorisceni_obroci i
             JOIN tipovi_obroka t ON t.id = i.tip_obroka_id
             JOIN menze m ON m.id = i.menza_id
             WHERE i.kartica_id = ?
             ORDER BY i.iskorisceno DESC, i.id DESC
             LIMIT ?'
        );
        $stmt->bind_param('ii', $karticaId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
