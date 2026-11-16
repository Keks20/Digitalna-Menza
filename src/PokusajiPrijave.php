<?php

require_once __DIR__ . '/Database.php';

/**
 * Ograničenje broja pokušaja prijave (tabela pokusaji_prijave).
 *
 * Posle NAJVISE_PO_NALOGU pogrešnih pokušaja za isti nalog, ili NAJVISE_PO_ADRESI sa iste IP adrese,
 * u poslednjih PAUZA_MIN minuta prijava se odbija dok ne prođe PAUZA_MIN minuta od prvog od tih pokušaja.
 * Dok pauza traje, novi pokušaji se ne upisuju, pa se pauza ne produžava.
 * Vreme se računa samo u bazi (NOW()), da ne zavisi od podešavanja vremenske zone u PHP-u.
 */
class PokusajiPrijave
{
    public const NAJVISE_PO_NALOGU = 5;
    public const NAJVISE_PO_ADRESI = 20;
    public const PAUZA_MIN = 10;

    /** Koliko dugo se čuvaju stari pokušaji */
    private const CUVANJE_DANA = 1;

    /** @var mysqli */
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance();
    }

    /**
     * Koliko sekundi još traje pauza za ovaj nalog ili ovu adresu (0 = prijava je dozvoljena).
     * $vrsta: 'student' | 'zaposleni'
     */
    public function pauzaSekundi(string $vrsta, string $korisnik, string $ip): int
    {
        return max(
            $this->preostalo('vrsta = ? AND korisnik = ?', 'ss', [$vrsta, $korisnik], self::NAJVISE_PO_NALOGU),
            $this->preostalo('ip = ?', 's', [$ip], self::NAJVISE_PO_ADRESI)
        );
    }

    /**
     * Upisuje neuspešan pokušaj. Vraća koliko pokušaja je ostalo za ovaj nalog pre pauze (0 = pauza je počela).
     */
    public function zabelezi(string $vrsta, string $korisnik, string $ip): int
    {
        $stmt = $this->conn->prepare('INSERT INTO pokusaji_prijave (vrsta, korisnik, ip) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $vrsta, $korisnik, $ip);
        $stmt->execute();

        // Stari redovi više ne utiču ni na šta
        $dana = self::CUVANJE_DANA;
        $stmt = $this->conn->prepare('DELETE FROM pokusaji_prijave WHERE vreme < NOW() - INTERVAL ? DAY');
        $stmt->bind_param('i', $dana);
        $stmt->execute();

        $minuta = self::PAUZA_MIN;
        $stmt = $this->conn->prepare(
            'SELECT COUNT(*) AS broj FROM pokusaji_prijave
             WHERE vrsta = ? AND korisnik = ? AND vreme > NOW() - INTERVAL ? MINUTE'
        );
        $stmt->bind_param('ssi', $vrsta, $korisnik, $minuta);
        $stmt->execute();
        $broj = (int)$stmt->get_result()->fetch_assoc()['broj'];

        return max(0, self::NAJVISE_PO_NALOGU - $broj);
    }

    /** Posle uspešne prijave raniji pogrešni pokušaji za taj nalog se brišu */
    public function obrisi(string $vrsta, string $korisnik): void
    {
        $stmt = $this->conn->prepare('DELETE FROM pokusaji_prijave WHERE vrsta = ? AND korisnik = ?');
        $stmt->bind_param('ss', $vrsta, $korisnik);
        $stmt->execute();
    }

    /**
     * Sekunde do kraja pauze za dati uslov (nalog ili adresa); 0 ako pokušaja nema dovoljno za pauzu.
     * $uslov je uvek tekst iz ove klase, a vrednosti idu kroz bind_param.
     */
    private function preostalo(string $uslov, string $tipovi, array $vrednosti, int $najvise): int
    {
        $minuta = self::PAUZA_MIN;
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS broj,
                    TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(MIN(vreme), INTERVAL ? MINUTE)) AS preostalo
             FROM pokusaji_prijave
             WHERE $uslov AND vreme > NOW() - INTERVAL ? MINUTE"
        );
        $parametri = array_merge([$minuta], $vrednosti, [$minuta]);
        $stmt->bind_param('i' . $tipovi . 'i', ...$parametri);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ((int)$row['broj'] < $najvise) {
            return 0;
        }
        return max(1, (int)$row['preostalo']);
    }
}
