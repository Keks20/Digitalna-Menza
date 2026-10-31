<?php

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/TipObroka.php';

class TipObrokaRepository extends BaseRepository
{
    protected $table = 'tipovi_obroka';

    protected function mapRow(array $row)
    {
        return new TipObroka($row);
    }

    public function create(array $data)
    {
        $stmt = $this->conn->prepare('INSERT INTO tipovi_obroka (naziv, cena, vreme_od, vreme_do) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('sdss', $data['naziv'], $data['cena'], $data['vreme_od'], $data['vreme_do']);
        $stmt->execute();
        return $this->conn->insert_id;
    }

    /**
     * Admin menja cenu i radno vreme. Već kupljeni obroci zadržavaju staru cenu
     * (ona je zapamćena u kupljeni_obroci.cena_po_obroku).
     */
    public function update(int $id, array $data)
    {
        $stmt = $this->conn->prepare('UPDATE tipovi_obroka SET naziv = ?, cena = ?, vreme_od = ?, vreme_do = ? WHERE id = ?');
        $stmt->bind_param('sdssi', $data['naziv'], $data['cena'], $data['vreme_od'], $data['vreme_do'], $id);
        return $stmt->execute();
    }

    /**
     * Vraća listu TipObroka objekata (doručak, ručak, večera) po redu.
     */
    public function list(array $conditions = [])
    {
        $result = $this->conn->query('SELECT * FROM tipovi_obroka ORDER BY id');
        $tipovi = [];
        foreach ($result->fetch_all(MYSQLI_ASSOC) as $row) {
            $tipovi[] = $this->mapRow($row);
        }
        return $tipovi;
    }
}
