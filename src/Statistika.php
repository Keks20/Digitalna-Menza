<?php

require_once __DIR__ . '/Database.php';

/**
 * Brojevi za administratorski pregled (admin_pocetna.php).
 * Samo čitanje – ništa se ne upisuje. Datumi se zadaju kao 'Y-m-d'.
 */
class Statistika
{
    /** @var mysqli */
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance();
    }

    /** Koliko je obroka prodato tog dana (zbir paketa iz uspešnih kupovina) */
    public function prodatoObroka(string $datum): int
    {
        $stmt = $this->conn->prepare(
            "SELECT COALESCE(SUM(k.kolicina), 0) AS broj
             FROM kupljeni_obroci k
             JOIN transakcije t ON t.id = k.transakcija_id
             WHERE t.status = 'uspesno' AND t.kreirano >= ? AND t.kreirano < ? + INTERVAL 1 DAY"
        );
        $stmt->bind_param('ss', $datum, $datum);
        $stmt->execute();
        return (int)$stmt->get_result()->fetch_assoc()['broj'];
    }

    /** Koliko je obroka izdato na kasama tog dana (sve menze) */
    public function izdatoObroka(string $datum): int
    {
        $stmt = $this->conn->prepare('SELECT COUNT(*) AS broj FROM iskorisceni_obroci WHERE iskorisceno >= ? AND iskorisceno < ? + INTERVAL 1 DAY');
        $stmt->bind_param('ss', $datum, $datum);
        $stmt->execute();
        return (int)$stmt->get_result()->fetch_assoc()['broj'];
    }

    /**
     * Koliko obroka je tog dana moglo da se izda: za svakog studenta i svaku vrstu obroka
     * po jedan, ako mu je na početku dana ostao bar jedan kupljen obrok te vrste za taj mesec.
     */
    public function mogucihObroka(string $datum): int
    {
        $mesec = date('Y-m-01', strtotime($datum));
        $stmt = $this->conn->prepare(
            'SELECT COUNT(*) AS broj
             FROM (SELECT kartica_id, tip_obroka_id, SUM(kolicina) AS kupljeno
                   FROM kupljeni_obroci
                   WHERE mesec = ?
                   GROUP BY kartica_id, tip_obroka_id) p
             WHERE p.kupljeno > (SELECT COUNT(*) FROM iskorisceni_obroci i
                                 WHERE i.kartica_id = p.kartica_id AND i.tip_obroka_id = p.tip_obroka_id
                                   AND i.iskorisceno >= ? AND i.iskorisceno < ?)'
        );
        $stmt->bind_param('sss', $mesec, $mesec, $datum);
        $stmt->execute();
        return (int)$stmt->get_result()->fetch_assoc()['broj'];
    }

    /** Broj aktivnih kartica sa stanjem ispod praga (npr. 500 RSD) */
    public function niskoStanje(float $prag): int
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS broj FROM kartice WHERE status = 'aktivna' AND stanje < ?");
        $stmt->bind_param('d', $prag);
        $stmt->execute();
        return (int)$stmt->get_result()->fetch_assoc()['broj'];
    }

    /**
     * Izdati obroci po danima, od najstarijeg: ['2026-10-13' => 812, '2026-10-14' => 845, ...]
     * Dani bez ijednog izdatog obroka su u nizu sa 0.
     */
    public function izdatoPoDanima(string $od, string $do): array
    {
        $stmt = $this->conn->prepare(
            'SELECT DATE(iskorisceno) AS dan, COUNT(*) AS broj
             FROM iskorisceni_obroci
             WHERE iskorisceno >= ? AND iskorisceno < ? + INTERVAL 1 DAY
             GROUP BY DATE(iskorisceno)'
        );
        $stmt->bind_param('ss', $od, $do);
        $stmt->execute();
        $poDanu = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'broj', 'dan');

        $dani = [];
        for ($dan = $od; $dan <= $do; $dan = date('Y-m-d', strtotime($dan . ' +1 day'))) {
            $dani[$dan] = (int)($poDanu[$dan] ?? 0);
        }
        return $dani;
    }

    /**
     * Izdato tog dana po menzama, uz dnevni kapacitet menze:
     * [['id' => 1, 'naziv' => 'Menza Ekonomski', 'izdato' => 412, 'kapacitet' => 600], ...]
     */
    public function poMenzama(string $datum): array
    {
        $stmt = $this->conn->prepare(
            'SELECT m.id, m.naziv, m.kapacitet_dnevno AS kapacitet,
                    (SELECT COUNT(*) FROM iskorisceni_obroci i
                     WHERE i.menza_id = m.id AND i.iskorisceno >= ? AND i.iskorisceno < ? + INTERVAL 1 DAY) AS izdato
             FROM menze m
             ORDER BY m.id'
        );
        $stmt->bind_param('ss', $datum, $datum);
        $stmt->execute();

        $menze = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $menze[] = ['id' => (int)$row['id'], 'naziv' => $row['naziv'], 'izdato' => (int)$row['izdato'], 'kapacitet' => (int)$row['kapacitet']];
        }
        return $menze;
    }

    /** Promena u procentima u odnosu na prethodnu vrednost (12.5 = +12,5%), null kad juče nije bilo ničega */
    public static function promena(float $danas, float $juce): ?float
    {
        if ($juce == 0) {
            return null;
        }
        return round(($danas - $juce) / $juce * 100, 1);
    }
}
