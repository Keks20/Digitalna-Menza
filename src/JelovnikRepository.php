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

    /**
     * Jelovnik za jedan dan za administratora – i objavljena jela i nacrt.
     * Grupisano po obroku: [tip_obroka_id => [StavkaJelovnika, ...], ...]
     */
    public function zaDanSvi(int $menzaId, string $datum): array
    {
        $stmt = $this->conn->prepare(
            'SELECT * FROM jelovnik
             WHERE menza_id = ? AND datum = ?
             ORDER BY tip_obroka_id, redosled'
        );
        $stmt->bind_param('is', $menzaId, $datum);
        $stmt->execute();

        $grupisano = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $grupisano[(int)$row['tip_obroka_id']][] = $this->mapRow($row);
        }
        return $grupisano;
    }

    /**
     * Čuva ceo jelovnik jednog dana u jednoj menzi: stari redovi se brišu, novi upisuju.
     * Sve u jednoj transakciji baze – ili se sačuva ceo dan, ili ništa.
     *
     * $stavke = [tip_obroka_id => [['jelo' => 'Pasulj', 'posno' => true], ...], ...]
     * $objavljen = true -> studenti ga vide; false -> nacrt ("U izmeni")
     */
    public function sacuvajDan(int $menzaId, string $datum, array $stavke, bool $objavljen): void
    {
        $this->conn->begin_transaction();
        try {
            $brisanje = $this->conn->prepare('DELETE FROM jelovnik WHERE menza_id = ? AND datum = ?');
            $brisanje->bind_param('is', $menzaId, $datum);
            $brisanje->execute();

            $upis = $this->conn->prepare(
                'INSERT INTO jelovnik (menza_id, datum, tip_obroka_id, jelo, posno, redosled, objavljen)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $objavljenBroj = $objavljen ? 1 : 0;
            foreach ($stavke as $tipObrokaId => $jela) {
                $redosled = 1;
                foreach ($jela as $jelo) {
                    $tip = (int)$tipObrokaId;
                    $naziv = $jelo['jelo'];
                    $posno = !empty($jelo['posno']) ? 1 : 0;
                    $upis->bind_param('isisiii', $menzaId, $datum, $tip, $naziv, $posno, $redosled, $objavljenBroj);
                    $upis->execute();
                    $redosled++;
                }
            }
            $this->conn->commit();
        } catch (mysqli_sql_exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    /**
     * Status svakog dana u nedelji za jednu menzu:
     * ['2026-11-02' => 'objavljen' | 'u_izmeni' | 'nije_unet', ...]
     */
    public function statusNedelje(int $menzaId, string $ponedeljak): array
    {
        $nedelja = date('Y-m-d', strtotime($ponedeljak . ' +6 days'));
        $stmt = $this->conn->prepare(
            'SELECT datum, SUM(objavljen = 0) AS nacrt
             FROM jelovnik
             WHERE menza_id = ? AND datum BETWEEN ? AND ?
             GROUP BY datum'
        );
        $stmt->bind_param('iss', $menzaId, $ponedeljak, $nedelja);
        $stmt->execute();

        $statusi = [];
        for ($i = 0; $i < 7; $i++) {
            $statusi[date('Y-m-d', strtotime($ponedeljak . ' +' . $i . ' days'))] = 'nije_unet';
        }
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $statusi[$row['datum']] = (int)$row['nacrt'] > 0 ? 'u_izmeni' : 'objavljen';
        }
        return $statusi;
    }
}
