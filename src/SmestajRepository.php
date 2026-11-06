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

    // =================================================================
    // Stranica Smeštaj (student)
    // =================================================================

    /**
     * Aktivan ugovor studenta sa podacima o sobi ili null ako ne živi u domu:
     * ['id', 'skolska_godina', 'mesecna_cena', 'vazi_od', 'vazi_do', 'dom', 'paviljon', 'soba', 'broj_kreveta']
     */
    public function ugovorStudenta(int $studentId): ?array
    {
        $stmt = $this->conn->prepare(
            'SELECT u.id, u.skolska_godina, u.mesecna_cena, u.vazi_od, u.vazi_do,
                    s.dom, s.paviljon, s.broj AS soba, s.broj_kreveta
             FROM smestaj_ugovori u
             JOIN sobe s ON s.id = u.soba_id
             WHERE u.student_id = ? AND u.aktivan = 1
             ORDER BY u.vazi_od DESC
             LIMIT 1'
        );
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    /** Zaduženje samo ako pripada tom studentu (za plaćanje i potvrdu), inače null */
    public function zaduzenjeStudenta(int $studentId, int $zaduzenjeId): ?SmestajZaduzenje
    {
        $stmt = $this->conn->prepare(
            'SELECT z.*, s.dom, s.paviljon, s.broj AS soba
             FROM smestaj_zaduzenja z
             JOIN smestaj_ugovori u ON u.id = z.ugovor_id
             JOIN sobe s ON s.id = u.soba_id
             WHERE z.id = ? AND u.student_id = ?'
        );
        $stmt->bind_param('ii', $zaduzenjeId, $studentId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? $this->mapRow($row) : null;
    }

    // =================================================================
    // Admin – smeštaj (admin_smestaj.php)
    // =================================================================

    /**
     * Zaduženja za jedan mesec sa studentom i sobom, za admin tabelu.
     * Filter: ['status' => '' | 'placeno' | 'neplaceno' | 'kasni', 'dom' => 'Dom 1', 'pretraga' => 'Marko']
     */
    public function zaduzenjaZaMesec(string $mesec, array $filter = []): array
    {
        $sql = 'SELECT z.id, z.mesec, z.iznos, z.rok_placanja, z.placeno, z.datum_placanja, z.transakcija_id,
                       st.ime, st.prezime, st.broj_indeksa, k.broj_kartice,
                       s.dom, s.paviljon, s.broj AS soba
                FROM smestaj_zaduzenja z
                JOIN smestaj_ugovori u ON u.id = z.ugovor_id
                JOIN sobe s ON s.id = u.soba_id
                JOIN studenti st ON st.id = u.student_id
                JOIN kartice k ON k.student_id = st.id
                WHERE z.mesec = ?';
        $tipovi = 's';
        $vrednosti = [$mesec];

        $status = $filter['status'] ?? '';
        if ($status === 'placeno') {
            $sql .= ' AND z.placeno = 1';
        } elseif ($status === 'neplaceno') {
            $sql .= ' AND z.placeno = 0';
        } elseif ($status === 'kasni') {
            $sql .= ' AND z.placeno = 0 AND z.rok_placanja < CURDATE()';
        }

        if (($filter['dom'] ?? '') !== '') {
            $sql .= ' AND s.dom = ?';
            $tipovi .= 's';
            $vrednosti[] = $filter['dom'];
        }

        if (($filter['pretraga'] ?? '') !== '') {
            // Broj kartice se traži samo po ciframa ("0012 3456" -> "00123456"); bez cifara se ne traži po kartici
            $tekst = '%' . $filter['pretraga'] . '%';
            $cifre = preg_replace('/\D/', '', $filter['pretraga']);
            $sql .= ' AND (CONCAT(st.ime, \' \', st.prezime) LIKE ? OR st.broj_indeksa LIKE ?'
                  . ($cifre !== '' ? ' OR k.broj_kartice LIKE ?' : '') . ')';
            $tipovi .= 'ss';
            array_push($vrednosti, $tekst, $tekst);
            if ($cifre !== '') {
                $tipovi .= 's';
                $vrednosti[] = '%' . $cifre . '%';
            }
        }

        $sql .= ' ORDER BY z.placeno ASC, s.dom, s.paviljon, s.broj, st.prezime';

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($tipovi, ...$vrednosti);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Zbir za mesec: ['broj' => 24, 'placeno_broj' => 18, 'kasni_broj' => 3, 'ukupno' => 67200.0, 'naplaceno' => 50400.0]
     */
    public function zbirZaMesec(string $mesec): array
    {
        $stmt = $this->conn->prepare(
            'SELECT COUNT(*) AS broj,
                    COALESCE(SUM(placeno = 1), 0) AS placeno_broj,
                    COALESCE(SUM(placeno = 0 AND rok_placanja < CURDATE()), 0) AS kasni_broj,
                    COALESCE(SUM(iznos), 0) AS ukupno,
                    COALESCE(SUM(CASE WHEN placeno = 1 THEN iznos END), 0) AS naplaceno
             FROM smestaj_zaduzenja
             WHERE mesec = ?'
        );
        $stmt->bind_param('s', $mesec);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return [
            'broj' => (int)$row['broj'],
            'placeno_broj' => (int)$row['placeno_broj'],
            'kasni_broj' => (int)$row['kasni_broj'],
            'ukupno' => (float)$row['ukupno'],
            'naplaceno' => (float)$row['naplaceno'],
        ];
    }

    /**
     * Pravi zaduženja za mesec svim aktivnim ugovorima koji važe u tom mesecu
     * (iznos = mesečna cena iz ugovora, rok 20. u mesecu). Ugovori koji već imaju
     * zaduženje za taj mesec se preskaču. Vraća broj novih zaduženja.
     */
    public function napraviZaduzenja(string $mesec): int
    {
        $stmt = $this->conn->prepare(
            'INSERT INTO smestaj_zaduzenja (ugovor_id, mesec, iznos, rok_placanja)
             SELECT u.id, ?, u.mesecna_cena, DATE_FORMAT(?, \'%Y-%m-20\')
             FROM smestaj_ugovori u
             WHERE u.aktivan = 1 AND u.vazi_od <= LAST_DAY(?) AND u.vazi_do >= ?
               AND NOT EXISTS (SELECT 1 FROM smestaj_zaduzenja z WHERE z.ugovor_id = u.id AND z.mesec = ?)'
        );
        $stmt->bind_param('sssss', $mesec, $mesec, $mesec, $mesec, $mesec);
        $stmt->execute();
        return $stmt->affected_rows;
    }
}
