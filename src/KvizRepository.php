<?php

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/KvizPitanje.php';

/**
 * Pitanja mini kviza (kviz_pitanja) i rezultati rešavanja (kviz_rezultati).
 */
class KvizRepository extends BaseRepository
{
    protected $table = 'kviz_pitanja';

    protected function mapRow(array $row)
    {
        return new KvizPitanje($row);
    }

    public function create(array $data)
    {
        $aktivno = (int)($data['aktivno'] ?? 1);
        $stmt = $this->conn->prepare(
            'INSERT INTO kviz_pitanja (tema, tezina, pitanje, odgovor_a, odgovor_b, odgovor_c, odgovor_d, tacan, objasnjenje, aktivno)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssssssssi', $data['tema'], $data['tezina'], $data['pitanje'],
            $data['odgovor_a'], $data['odgovor_b'], $data['odgovor_c'], $data['odgovor_d'],
            $data['tacan'], $data['objasnjenje'], $aktivno);
        $stmt->execute();
        return $this->conn->insert_id;
    }

    public function update(int $id, array $data)
    {
        $aktivno = (int)($data['aktivno'] ?? 1);
        $stmt = $this->conn->prepare(
            'UPDATE kviz_pitanja
             SET tema = ?, tezina = ?, pitanje = ?, odgovor_a = ?, odgovor_b = ?, odgovor_c = ?, odgovor_d = ?,
                 tacan = ?, objasnjenje = ?, aktivno = ?
             WHERE id = ?'
        );
        $stmt->bind_param('sssssssssii', $data['tema'], $data['tezina'], $data['pitanje'],
            $data['odgovor_a'], $data['odgovor_b'], $data['odgovor_c'], $data['odgovor_d'],
            $data['tacan'], $data['objasnjenje'], $aktivno, $id);
        return $stmt->execute();
    }

    /**
     * Sva pitanja po redu unosa: ['aktivno' => 1] vraća samo aktivna.
     * @return KvizPitanje[]
     */
    public function list(array $conditions = [])
    {
        if (isset($conditions['aktivno'])) {
            $aktivno = (int)$conditions['aktivno'];
            $stmt = $this->conn->prepare('SELECT * FROM kviz_pitanja WHERE aktivno = ? ORDER BY id');
            $stmt->bind_param('i', $aktivno);
        } else {
            $stmt = $this->conn->prepare('SELECT * FROM kviz_pitanja ORDER BY id');
        }
        $stmt->execute();
        return array_map([$this, 'mapRow'], $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    /**
     * id-jevi nasumično izabranih aktivnih pitanja (za jedan kviz).
     * @return int[]
     */
    public function nasumicnaPitanja(int $broj): array
    {
        $stmt = $this->conn->prepare('SELECT id FROM kviz_pitanja WHERE aktivno = 1 ORDER BY RAND() LIMIT ?');
        $stmt->bind_param('i', $broj);
        $stmt->execute();
        return array_map('intval', array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'id'));
    }

    /**
     * Pitanja po id-jevima, istim redom kojim su id-jevi dati.
     * Pitanje koje je u međuvremenu obrisano se preskače.
     * @param int[] $ids
     * @return KvizPitanje[] [id => KvizPitanje]
     */
    public function poIdjevima(array $ids): array
    {
        $ids = array_values(array_map('intval', $ids));
        if (empty($ids)) {
            return [];
        }

        $mesta = implode(', ', array_fill(0, count($ids), '?'));
        $stmt = $this->conn->prepare("SELECT * FROM kviz_pitanja WHERE id IN ($mesta)");
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();

        $poId = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $poId[(int)$row['id']] = $this->mapRow($row);
        }

        $pitanja = [];
        foreach ($ids as $id) {
            if (isset($poId[$id])) {
                $pitanja[$id] = $poId[$id];
            }
        }
        return $pitanja;
    }

    /**
     * Upis rezultata; $studentId je null za neprijavljenog korisnika. Vraća id novog reda.
     */
    public function sacuvajRezultat(?int $studentId, string $ime, int $brojTacnih, int $ukupno, int $trajanjeSek): int
    {
        $stmt = $this->conn->prepare(
            'INSERT INTO kviz_rezultati (student_id, ime, broj_tacnih, ukupno, trajanje_sek) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('isiii', $studentId, $ime, $brojTacnih, $ukupno, $trajanjeSek);
        $stmt->execute();
        return $this->conn->insert_id;
    }

    /**
     * Najbolji rezultati: više tačnih je bolje, a kod istog broja tačnih brže rešavanje.
     * Vraća [['id', 'ime', 'broj_tacnih', 'ukupno', 'trajanje_sek', 'kreirano', 'student_id'], ...]
     */
    public function najboljiRezultati(int $limit = 10): array
    {
        $stmt = $this->conn->prepare(
            'SELECT id, student_id, ime, broj_tacnih, ukupno, trajanje_sek, kreirano
             FROM kviz_rezultati
             ORDER BY broj_tacnih DESC, trajanje_sek ASC, kreirano ASC, id ASC
             LIMIT ?'
        );
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
