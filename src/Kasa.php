<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Bon.php';
require_once __DIR__ . '/Format.php';

/**
 * Izdavanje obroka na kasi menze.
 *
 * Tok: kasir skenira QR bon (ili upiše broj kartice) -> proveri() kaže da li obrok sme da se izda
 * i koliko će ostati -> kasir potvrdi -> izdaj() upisuje red u iskorisceni_obroci.
 *
 * Obrok se izdaje kad su ispunjeni svi uslovi:
 *   - kartica postoji i aktivna je
 *   - obrok se trenutno služi (radno vreme iz tipovi_obroka), osim ako je PROVERAVA_RADNO_VREME = false
 *   - student danas još nije dobio taj obrok (jedan obrok svake vrste dnevno, u bilo kojoj menzi)
 *   - za tekući mesec ima bar jedan neiskorišćen obrok te vrste
 *
 * izdaj() ponovo proverava sve to u transakciji, sa zaključanim redom kartice,
 * da dve kase u isto vreme ne bi izdale isti poslednji obrok.
 */
class Kasa
{
    /** Za probu van radnog vremena menze može da se stavi false */
    public const PROVERAVA_RADNO_VREME = true;

    /** @var mysqli */
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance();
    }

    /**
     * Tip obroka koji se služi u datom trenutku (npr. ručak od 11:30 do 15:00) ili null.
     * $tipovi = TipObroka[] iz TipObrokaRepository::list()
     */
    public static function trenutniObrok(array $tipovi, ?int $vreme = null): ?TipObroka
    {
        $sat = date('H:i:s', $vreme ?? time());
        foreach ($tipovi as $tip) {
            if ($sat >= $tip->vreme_od && $sat <= $tip->vreme_do) {
                return $tip;
            }
        }
        return null;
    }

    /** Broj kartice iz unosa ("0012 3456 7890" -> "001234567890") ili null ako nije 12 cifara */
    public static function ocistiBrojKartice(string $unos): ?string
    {
        $broj = preg_replace('/[\s-]/', '', $unos);
        return preg_match('/^\d{12}$/', $broj) ? $broj : null;
    }

    /** id kartice po broju sa kartice ili null */
    public function karticaPoBroju(string $brojKartice): ?int
    {
        $stmt = $this->conn->prepare('SELECT id FROM kartice WHERE broj_kartice = ?');
        $stmt->bind_param('s', $brojKartice);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? (int)$row['id'] : null;
    }

    /**
     * Provera pre izdavanja (ništa ne upisuje).
     * Vraća:
     *   ['ishod' => 'moze', 'student' => 'Marko Marković', 'broj_kartice' => '001234567890',
     *    'obrok' => 'Ručak', 'kartica_id' => 1, 'tip_obroka_id' => 2, 'preostalo_posle' => 6, 'kupljeno' => 10]
     * ili ishod 'odbijeno' / 'vec_izdato' sa 'razlog' (tekst za kasira) i podacima koliko ih ima.
     */
    public function proveri(int $karticaId, int $tipObrokaId, ?int $vreme = null): array
    {
        $vreme = $vreme ?? time();
        $podaci = $this->podaciZaProveru($karticaId, $tipObrokaId, $vreme, false);

        if ($podaci === null) {
            return ['ishod' => 'odbijeno', 'razlog' => 'Kartica ne postoji.'];
        }
        return $this->oceni($podaci, $vreme);
    }

    /**
     * Izdavanje obroka. Sve provere se ponavljaju unutar transakcije.
     * Vraća isti niz kao proveri(); ako je ishod 'izdato', red je upisan ('id' = novi red u iskorisceni_obroci).
     */
    public function izdaj(int $karticaId, int $tipObrokaId, int $menzaId, int $kasirId, ?int $vreme = null): array
    {
        $vreme = $vreme ?? time();

        $this->conn->begin_transaction();
        try {
            $podaci = $this->podaciZaProveru($karticaId, $tipObrokaId, $vreme, true);
            if ($podaci === null) {
                $this->conn->rollback();
                return ['ishod' => 'odbijeno', 'razlog' => 'Kartica ne postoji.'];
            }

            $rezultat = $this->oceni($podaci, $vreme);
            if ($rezultat['ishod'] !== 'moze') {
                $this->conn->rollback();
                return $rezultat;
            }

            $kada = date('Y-m-d H:i:s', $vreme);
            $stmt = $this->conn->prepare('INSERT INTO iskorisceni_obroci (kartica_id, tip_obroka_id, menza_id, kasir_id, iskorisceno) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('iiiis', $karticaId, $tipObrokaId, $menzaId, $kasirId, $kada);
            $stmt->execute();
            $rezultat['id'] = $this->conn->insert_id;
            $rezultat['ishod'] = 'izdato';

            $this->conn->commit();
            return $rezultat;
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    /**
     * Koliko je danas izdato po tipu obroka u jednoj menzi i koliko studenata ima taj obrok ovog meseca:
     * [tip_obroka_id => ['izdato' => 148, 'ima_obrok' => 220], ...]
     */
    public function danasUMenzi(int $menzaId, ?int $vreme = null): array
    {
        $vreme = $vreme ?? time();
        $danas = date('Y-m-d', $vreme);
        $mesec = date('Y-m-01', $vreme);

        $stmt = $this->conn->prepare(
            'SELECT t.id,
                    (SELECT COUNT(*) FROM iskorisceni_obroci i
                     WHERE i.tip_obroka_id = t.id AND i.menza_id = ?
                       AND i.iskorisceno >= ? AND i.iskorisceno < ? + INTERVAL 1 DAY) AS izdato,
                    (SELECT COUNT(DISTINCT k.kartica_id) FROM kupljeni_obroci k
                     WHERE k.tip_obroka_id = t.id AND k.mesec = ?) AS ima_obrok
             FROM tipovi_obroka t
             ORDER BY t.id'
        );
        $stmt->bind_param('isss', $menzaId, $danas, $danas, $mesec);
        $stmt->execute();

        $brojevi = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $brojevi[(int)$row['id']] = ['izdato' => (int)$row['izdato'], 'ima_obrok' => (int)$row['ima_obrok']];
        }
        return $brojevi;
    }

    // -----------------------------------------------------------------

    /** Sve što treba za odluku, u jednom prolazu; uz $zakljucaj red kartice ostaje zaključan do kraja transakcije */
    private function podaciZaProveru(int $karticaId, int $tipObrokaId, int $vreme, bool $zakljucaj): ?array
    {
        $sql = 'SELECT k.id, k.broj_kartice, k.status, s.ime, s.prezime
                FROM kartice k JOIN studenti s ON s.id = k.student_id
                WHERE k.id = ?' . ($zakljucaj ? ' FOR UPDATE' : '');
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $karticaId);
        $stmt->execute();
        $kartica = $stmt->get_result()->fetch_assoc();
        if (!$kartica) {
            return null;
        }

        $stmt = $this->conn->prepare('SELECT naziv, vreme_od, vreme_do FROM tipovi_obroka WHERE id = ?');
        $stmt->bind_param('i', $tipObrokaId);
        $stmt->execute();
        $tip = $stmt->get_result()->fetch_assoc();
        if (!$tip) {
            return null;
        }

        $danas = date('Y-m-d', $vreme);
        $mesec = date('Y-m-01', $vreme);

        $stmt = $this->conn->prepare('SELECT COALESCE(SUM(kolicina), 0) AS kupljeno FROM kupljeni_obroci WHERE kartica_id = ? AND tip_obroka_id = ? AND mesec = ?');
        $stmt->bind_param('iis', $karticaId, $tipObrokaId, $mesec);
        $stmt->execute();
        $kupljeno = (int)$stmt->get_result()->fetch_assoc()['kupljeno'];

        $stmt = $this->conn->prepare(
            'SELECT COUNT(*) AS ukupno,
                    SUM(iskorisceno >= ? AND iskorisceno < ? + INTERVAL 1 DAY) AS danas,
                    MAX(CASE WHEN iskorisceno >= ? AND iskorisceno < ? + INTERVAL 1 DAY THEN iskorisceno END) AS kad_danas
             FROM iskorisceni_obroci
             WHERE kartica_id = ? AND tip_obroka_id = ? AND iskorisceno >= ? AND iskorisceno < ? + INTERVAL 1 MONTH'
        );
        $stmt->bind_param('ssssiiss', $danas, $danas, $danas, $danas, $karticaId, $tipObrokaId, $mesec, $mesec);
        $stmt->execute();
        $iskorisceno = $stmt->get_result()->fetch_assoc();

        return [
            'kartica_id' => (int)$kartica['id'],
            'broj_kartice' => $kartica['broj_kartice'],
            'aktivna' => $kartica['status'] === 'aktivna',
            'student' => $kartica['ime'] . ' ' . $kartica['prezime'],
            'tip_obroka_id' => $tipObrokaId,
            'obrok' => $tip['naziv'],
            'vreme_od' => $tip['vreme_od'],
            'vreme_do' => $tip['vreme_do'],
            'kupljeno' => $kupljeno,
            'iskorisceno' => (int)$iskorisceno['ukupno'],
            'danas' => (int)$iskorisceno['danas'],
            'kad_danas' => $iskorisceno['kad_danas'],
        ];
    }

    /** Odluka na osnovu podataka; redosled provera je redosled poruka koje kasir vidi */
    private function oceni(array $p, int $vreme): array
    {
        $preostalo = max(0, $p['kupljeno'] - $p['iskorisceno']);
        $rezultat = [
            'kartica_id' => $p['kartica_id'],
            'tip_obroka_id' => $p['tip_obroka_id'],
            'student' => $p['student'],
            'broj_kartice' => $p['broj_kartice'],
            'obrok' => $p['obrok'],
            'kupljeno' => $p['kupljeno'],
            'preostalo_posle' => max(0, $preostalo - 1),
        ];

        if (!$p['aktivna']) {
            return $rezultat + ['ishod' => 'odbijeno', 'razlog' => 'Kartica je blokirana.'];
        }

        $sat = date('H:i:s', $vreme);
        if (self::PROVERAVA_RADNO_VREME && ($sat < $p['vreme_od'] || $sat > $p['vreme_do'])) {
            return $rezultat + ['ishod' => 'odbijeno',
                'razlog' => $p['obrok'] . ' se izdaje od ' . Format::vreme($p['vreme_od']) . ' do ' . Format::vreme($p['vreme_do']) . '.'];
        }

        if ($p['danas'] > 0) {
            return $rezultat + ['ishod' => 'vec_izdato',
                'razlog' => $p['obrok'] . ' je danas već izdat (u ' . date('H:i', strtotime($p['kad_danas'])) . ').'];
        }

        if ($preostalo < 1) {
            return $rezultat + ['ishod' => 'odbijeno',
                'razlog' => $p['kupljeno'] > 0 ? 'Iskorišćeni su svi obroci ove vrste za ovaj mesec.' : 'Student nema kupljen ' . mb_strtolower($p['obrok']) . ' za ovaj mesec.'];
        }

        return $rezultat + ['ishod' => 'moze'];
    }
}
