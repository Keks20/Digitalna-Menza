<?php

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/StavkaJelovnika.php';

class JelovnikRepository extends BaseRepository
{
    protected $table = 'jelovnik';

    protected function mapRow(array $row)
    {
        return new StavkaJelovnika($row);
    }

    public function create(array $data)
    {
        $posno = !empty($data['posno']) ? 1 : 0;
        $redosled = $data['redosled'] ?? 1;
        $stmt = $this->conn->prepare('INSERT INTO jelovnik (menza_id, datum, tip_obroka_id, jelo, posno, redosled) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('isisii', $data['menza_id'], $data['datum'], $data['tip_obroka_id'], $data['jelo'], $posno, $redosled);
        $stmt->execute();
        return $this->conn->insert_id;
    }

    public function update(int $id, array $data)
    {
        $posno = !empty($data['posno']) ? 1 : 0;
        $stmt = $this->conn->prepare('UPDATE jelovnik SET jelo = ?, posno = ? WHERE id = ?');
        $stmt->bind_param('sii', $data['jelo'], $posno, $id);
        return $stmt->execute();
    }

    /**
     * Jelovnik jedne menze za period, npr. ['menza_id' => 1, 'od' => '2026-11-02', 'do' => '2026-11-08'].
     */
    public function list(array $conditions = [])
    {
        $stmt = $this->conn->prepare(
            'SELECT j.*, t.naziv AS obrok
             FROM jelovnik j JOIN tipovi_obroka t ON t.id = j.tip_obroka_id
             WHERE j.menza_id = ? AND j.datum BETWEEN ? AND ? AND j.objavljen = 1
             ORDER BY j.datum, j.tip_obroka_id, j.redosled'
        );
        $stmt->bind_param('iss', $conditions['menza_id'], $conditions['od'], $conditions['do']);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Jelovnik za jedan dan, grupisan po obroku:
     * [tip_obroka_id => [StavkaJelovnika, StavkaJelovnika, ...], ...]
     */
    public function zaDan(int $menzaId, string $datum): array
    {
        $grupisano = [];
        $redovi = $this->list(['menza_id' => $menzaId, 'od' => $datum, 'do' => $datum]);
        foreach ($redovi as $row) {
            $grupisano[(int)$row['tip_obroka_id']][] = $this->mapRow($row);
        }
        return $grupisano;
    }

    /**
     * Nedeljni jelovnik grupisan po danu pa po obroku:
     * ['2026-11-02' => [tip_obroka_id => [StavkaJelovnika, ...]], ...]
     */
    public function zaNedelju(int $menzaId, string $ponedeljak): array
    {
        $nedelja = date('Y-m-d', strtotime($ponedeljak . ' +6 days'));
        $grupisano = [];
        foreach ($this->list(['menza_id' => $menzaId, 'od' => $ponedeljak, 'do' => $nedelja]) as $row) {
            $grupisano[$row['datum']][(int)$row['tip_obroka_id']][] = $this->mapRow($row);
        }
        return $grupisano;
    }
}
