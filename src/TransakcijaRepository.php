<?php

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/Transakcija.php';
require_once __DIR__ . '/Format.php';

/**
 * Evidencija novca.
 *
 * Obični create() samo upisuje red i NE menja stanje kartice. Novac se pomera
 * metodama uplati(), potvrdiUplatu() i kupiObroke(): one u jednoj transakciji baze menjaju
 * stanje kartice i upisuju transakciju, pa stanje i istorija nikad ne mogu da se razilaze.
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

    // =================================================================
    // Istorija transakcija (stranica istorija.php)
    // =================================================================

    /**
     * Transakcije jedne kartice za stranicu "Istorija", najnovije prve.
     * Filter: ['tip' => '' | 'uplata' | 'obroci' | 'smestaj', 'od' => '2026-09-01', 'do' => '2026-10-19',
     *          'limit' => 10, 'offset' => 0]  – bez limita vraća sve (za izvoz u CSV i PDF).
     *
     * @return Transakcija[]
     */
    public function istorija(int $karticaId, array $filter = []): array
    {
        [$where, $tipovi, $vrednosti] = $this->uslovIstorije($karticaId, $filter);
        $sql = "SELECT * FROM transakcije WHERE $where ORDER BY kreirano DESC, id DESC";

        if (isset($filter['limit'])) {
            $sql .= ' LIMIT ? OFFSET ?';
            $tipovi .= 'ii';
            $vrednosti[] = (int)$filter['limit'];
            $vrednosti[] = (int)($filter['offset'] ?? 0);
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($tipovi, ...$vrednosti);
        $stmt->execute();
        $result = $stmt->get_result();

        $transakcije = [];
        while ($row = $result->fetch_assoc()) {
            $transakcije[] = $this->mapRow($row);
        }
        return $transakcije;
    }

    /** Koliko transakcija ima za isti filter (za "Prikazano 10 od 24" i broj strana) */
    public function brojUIstoriji(int $karticaId, array $filter = []): int
    {
        [$where, $tipovi, $vrednosti] = $this->uslovIstorije($karticaId, $filter);
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS broj FROM transakcije WHERE $where");
        $stmt->bind_param($tipovi, ...$vrednosti);
        $stmt->execute();
        return (int)$stmt->get_result()->fetch_assoc()['broj'];
    }

    /**
     * Zbir uspešnih transakcija po tipu u periodu: ['uplata' => 10000.0, 'obroci' => -5320.0, 'smestaj' => -2800.0]
     * Uplate su pozitivne, a obroci i smeštaj negativni (tako su i upisani).
     */
    public function zbirPoTipu(int $karticaId, string $od, string $do): array
    {
        $stmt = $this->conn->prepare(
            "SELECT tip, SUM(iznos) AS zbir
             FROM transakcije
             WHERE kartica_id = ? AND status = 'uspesno'
               AND kreirano >= ? AND kreirano < DATE_ADD(?, INTERVAL 1 DAY)
             GROUP BY tip"
        );
        $stmt->bind_param('iss', $karticaId, $od, $do);
        $stmt->execute();
        $result = $stmt->get_result();

        $zbirovi = ['uplata' => 0.0, 'obroci' => 0.0, 'smestaj' => 0.0];
        while ($row = $result->fetch_assoc()) {
            $zbirovi[$row['tip']] = (float)$row['zbir'];
        }
        return $zbirovi;
    }

    /**
     * Zajednički WHERE za istoriju: [sql, tipovi za bind_param, vrednosti].
     * Datum "do" je uključen ceo dan (do ponoći sledećeg dana).
     */
    private function uslovIstorije(int $karticaId, array $filter): array
    {
        $where = 'kartica_id = ?';
        $tipovi = 'i';
        $vrednosti = [$karticaId];

        if (($filter['tip'] ?? '') !== '') {
            $where .= ' AND tip = ?';
            $tipovi .= 's';
            $vrednosti[] = $filter['tip'];
        }
        if (($filter['od'] ?? '') !== '') {
            $where .= ' AND kreirano >= ?';
            $tipovi .= 's';
            $vrednosti[] = $filter['od'];
        }
        if (($filter['do'] ?? '') !== '') {
            $where .= ' AND kreirano < DATE_ADD(?, INTERVAL 1 DAY)';
            $tipovi .= 's';
            $vrednosti[] = $filter['do'];
        }
        return [$where, $tipovi, $vrednosti];
    }

    // =================================================================
    // Uplate (dopuna kartice)
    // =================================================================

    /**
     * Uspešna uplata: povećava stanje kartice i upisuje transakciju sa novim stanjem.
     * Vraća id transakcije.
     */
    public function uplati(int $karticaId, float $iznos, string $nacin, string $opis): int
    {
        $this->conn->begin_transaction();
        try {
            $novoStanje = $this->dodajNaStanje($karticaId, $iznos);

            $status = 'uspesno';
            $tip = 'uplata';
            $stmt = $this->conn->prepare('INSERT INTO transakcije (kartica_id, tip, nacin, iznos, stanje_posle, status, opis) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('issddss', $karticaId, $tip, $nacin, $iznos, $novoStanje, $status, $opis);
            $stmt->execute();
            $id = $this->conn->insert_id;

            $this->conn->commit();
            return $id;
        } catch (mysqli_sql_exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    /**
     * Uplata koja ne menja stanje: 'ceka_potvrdu' (IPS uplata dok banka ne javi)
     * ili 'odbijeno' (banka odbila karticu). Vraća id transakcije.
     */
    public function zabeleziUplatu(int $karticaId, float $iznos, string $nacin, string $status, string $opis): int
    {
        return $this->create([
            'kartica_id' => $karticaId,
            'tip' => 'uplata',
            'nacin' => $nacin,
            'iznos' => $iznos,
            'stanje_posle' => null,
            'status' => $status,
            'opis' => $opis,
        ]);
    }

    /**
     * Potvrda uplate koja čeka (admin ili simulacija prijema IPS uplate):
     * status postaje 'uspesno' i novac se dodaje na karticu.
     * Vraća false ako transakcija ne postoji ili više ne čeka potvrdu.
     */
    public function potvrdiUplatu(int $id): bool
    {
        $this->conn->begin_transaction();
        try {
            // FOR UPDATE zaključava red, pa dva istovremena klika ne mogu dvaput da dodaju novac
            $stmt = $this->conn->prepare(
                "SELECT kartica_id, iznos FROM transakcije
                 WHERE id = ? AND tip = 'uplata' AND status = 'ceka_potvrdu'
                 FOR UPDATE"
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) {
                $this->conn->rollback();
                return false;
            }

            $novoStanje = $this->dodajNaStanje((int)$row['kartica_id'], (float)$row['iznos']);

            $stmt = $this->conn->prepare("UPDATE transakcije SET status = 'uspesno', stanje_posle = ? WHERE id = ?");
            $stmt->bind_param('di', $novoStanje, $id);
            $stmt->execute();

            $this->conn->commit();
            return true;
        } catch (mysqli_sql_exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    /**
     * Odbijanje uplate koja čeka potvrdu (stanje se ne menja).
     * Vraća false ako transakcija ne postoji ili više ne čeka potvrdu.
     */
    public function odbijUplatu(int $id): bool
    {
        $stmt = $this->conn->prepare("UPDATE transakcije SET status = 'odbijeno' WHERE id = ? AND tip = 'uplata' AND status = 'ceka_potvrdu'");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->affected_rows === 1;
    }

    /**
     * Uplate svih studenata za administratora, najnovije prve.
     * Uslovi: ['status' => 'ceka_potvrdu' | 'odbijeno' | 'uspesno' | '', 'pretraga' => ime, broj kartice ili broj transakcije, 'limit' => 50]
     */
    public function uplateZaAdmina(array $conditions = []): array
    {
        $status = $conditions['status'] ?? '';
        $pretraga = trim($conditions['pretraga'] ?? '');
        $limit = (int)($conditions['limit'] ?? 50);

        $sql = "SELECT t.*, k.broj_kartice, s.ime, s.prezime
                FROM transakcije t
                JOIN kartice k ON k.id = t.kartica_id
                JOIN studenti s ON s.id = k.student_id
                WHERE t.tip = 'uplata'";
        $tipovi = '';
        $vrednosti = [];

        if ($status !== '') {
            $sql .= ' AND t.status = ?';
            $tipovi .= 's';
            $vrednosti[] = $status;
        }
        if ($pretraga !== '') {
            $cifre = preg_replace('/\D/', '', $pretraga);
            if ($cifre === '') {
                // Samo slova: pretraga po imenu i prezimenu
                $sql .= " AND CONCAT(s.ime, ' ', s.prezime) LIKE ?";
                $tipovi .= 's';
                $vrednosti[] = '%' . $pretraga . '%';
            } else {
                // Ima cifara: i po broju kartice (sa ili bez razmaka) i po broju transakcije
                $sql .= " AND (CONCAT(s.ime, ' ', s.prezime) LIKE ? OR k.broj_kartice LIKE ? OR t.id = ?)";
                $tipovi .= 'ssi';
                $vrednosti[] = '%' . $pretraga . '%';
                $vrednosti[] = '%' . $cifre . '%';
                $vrednosti[] = (int)$cifre;
            }
        }
        $sql .= ' ORDER BY t.kreirano DESC, t.id DESC LIMIT ?';
        $tipovi .= 'i';
        $vrednosti[] = $limit;

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($tipovi, ...$vrednosti);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Broj i zbir uspešnih uplata za jedan dan: ['broj' => 47, 'zbir' => 48500.0]
     */
    public function uplateZaDan(string $datum): array
    {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS broj, COALESCE(SUM(iznos), 0) AS zbir
             FROM transakcije
             WHERE tip = 'uplata' AND status = 'uspesno' AND DATE(kreirano) = ?"
        );
        $stmt->bind_param('s', $datum);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return ['broj' => (int)$row['broj'], 'zbir' => (float)$row['zbir']];
    }

    /** Koliko uplata čeka potvrdu administratora */
    public function brojUplataNaCekanju(): int
    {
        $result = $this->conn->query("SELECT COUNT(*) AS broj FROM transakcije WHERE tip = 'uplata' AND status = 'ceka_potvrdu'");
        return (int)$result->fetch_assoc()['broj'];
    }

    /**
     * Dodaje iznos na stanje kartice i vraća novo stanje.
     * Poziva se samo unutar otvorene transakcije baze (red kartice ostaje zaključan do commit-a).
     */
    // =================================================================
    // Kupovina obroka (plaćanje sa studentske kartice)
    // =================================================================

    /**
     * Kupovina mesečnih obroka: u jednoj transakciji baze skida novac sa kartice,
     * upisuje transakciju tipa 'obroci' i pakete u kupljeni_obroci.
     *
     * $stavke: [tip_obroka_id => ['kolicina' => 10, 'cena' => 120.0], ...] – samo tipovi sa kolicinom > 0
     * $najviseUMesecu: koliko obroka jednog tipa sme da bude kupljeno za ceo mesec (broj dana u mesecu)
     *
     * Vraća id transakcije. Baca RuntimeException sa porukom za studenta kad nema dovoljno novca,
     * kad je kartica blokirana ili kad bi se prešlo ograničenje za mesec.
     */
    public function kupiObroke(int $karticaId, string $mesec, array $stavke, int $najviseUMesecu, string $opis): int
    {
        $ukupno = 0.0;
        foreach ($stavke as $stavka) {
            $ukupno += $stavka['kolicina'] * $stavka['cena'];
        }
        $ukupno = round($ukupno, 2);

        $this->conn->begin_transaction();
        try {
            // Red kartice se zaključava – dve kupovine u isto vreme ne mogu obe da prođu sa istim novcem
            $stmt = $this->conn->prepare('SELECT stanje, status FROM kartice WHERE id = ? FOR UPDATE');
            $stmt->bind_param('i', $karticaId);
            $stmt->execute();
            $kartica = $stmt->get_result()->fetch_assoc();
            if (!$kartica) {
                throw new RuntimeException('Kartica ne postoji.');
            }
            if ($kartica['status'] !== 'aktivna') {
                throw new RuntimeException('Kartica je blokirana – obratite se Studentskom centru.');
            }
            if ((float)$kartica['stanje'] < $ukupno) {
                throw new RuntimeException('Nema dovoljno novca na kartici. Potrebno je ' . Format::novac($ukupno)
                    . ', a na kartici je ' . Format::novac($kartica['stanje']) . '.');
            }

            // Ograničenje po mesecu se proverava ponovo ovde, posle zaključavanja kartice
            $stmt = $this->conn->prepare('SELECT COALESCE(SUM(kolicina), 0) AS kupljeno FROM kupljeni_obroci WHERE kartica_id = ? AND tip_obroka_id = ? AND mesec = ?');
            foreach ($stavke as $tipId => $stavka) {
                $stmt->bind_param('iis', $karticaId, $tipId, $mesec);
                $stmt->execute();
                $kupljeno = (int)$stmt->get_result()->fetch_assoc()['kupljeno'];
                if ($kupljeno + $stavka['kolicina'] > $najviseUMesecu) {
                    throw new RuntimeException('Za jedan mesec može da se kupi najviše ' . $najviseUMesecu . ' obroka svake vrste.');
                }
            }

            $novoStanje = $this->dodajNaStanje($karticaId, -$ukupno);

            $tip = 'obroci';
            $nacin = 'kartica';
            $status = 'uspesno';
            $iznos = -$ukupno;
            $stmt = $this->conn->prepare('INSERT INTO transakcije (kartica_id, tip, nacin, iznos, stanje_posle, status, opis) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('issddss', $karticaId, $tip, $nacin, $iznos, $novoStanje, $status, $opis);
            $stmt->execute();
            $transakcijaId = $this->conn->insert_id;

            $stmt = $this->conn->prepare('INSERT INTO kupljeni_obroci (kartica_id, tip_obroka_id, mesec, kolicina, cena_po_obroku, transakcija_id) VALUES (?, ?, ?, ?, ?, ?)');
            foreach ($stavke as $tipId => $stavka) {
                $stmt->bind_param('iisidi', $karticaId, $tipId, $mesec, $stavka['kolicina'], $stavka['cena'], $transakcijaId);
                $stmt->execute();
            }

            $this->conn->commit();
            return $transakcijaId;
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    private function dodajNaStanje(int $karticaId, float $iznos): float
    {
        $stmt = $this->conn->prepare('SELECT stanje FROM kartice WHERE id = ? FOR UPDATE');
        $stmt->bind_param('i', $karticaId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) {
            throw new RuntimeException('Kartica ne postoji.');
        }

        $novoStanje = round((float)$row['stanje'] + $iznos, 2);
        $stmt = $this->conn->prepare('UPDATE kartice SET stanje = ? WHERE id = ?');
        $stmt->bind_param('di', $novoStanje, $karticaId);
        $stmt->execute();
        return $novoStanje;
    }
}
