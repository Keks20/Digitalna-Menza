<?php

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/StavkaDnevnika.php';
require_once __DIR__ . '/Admin.php';

/**
 * Dnevnik akcija zaposlenih (tabela admin_dnevnik): ko je, kada, sa koje adrese i šta uradio.
 * Zapisi se samo dodaju i čitaju – izmena i brisanje nisu dozvoljeni, da dnevnik ostane verodostojan.
 */
class AdminDnevnikRepository extends BaseRepository
{
    protected $table = 'admin_dnevnik';

    /** Vrste akcija koje se beleže i njihovi nazivi za prikaz */
    public const AKCIJE = [
        'prijava' => 'Prijava',
        'kartica' => 'Kartica',
        'kategorija' => 'Kategorija',
        'cene' => 'Cene',
        'obavestenje' => 'Obaveštenje',
        'zaduzenja' => 'Zaduženja',
    ];

    private const NAJVISE_OPIS = 255;   // kolona admin_dnevnik.opis je VARCHAR(255)

    protected function mapRow(array $row)
    {
        return new StavkaDnevnika($row);
    }

    /**
     * Novi zapis: ['admin_id', 'admin_ime', 'akcija', 'opis', 'ip']. Vraća id zapisa.
     */
    public function create(array $data)
    {
        if (!isset(self::AKCIJE[$data['akcija']])) {
            throw new InvalidArgumentException('Nepoznata akcija za dnevnik: ' . $data['akcija']);
        }
        $opis = mb_substr($data['opis'], 0, self::NAJVISE_OPIS);
        $stmt = $this->conn->prepare('INSERT INTO admin_dnevnik (admin_id, admin_ime, akcija, opis, ip) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('issss', $data['admin_id'], $data['admin_ime'], $data['akcija'], $opis, $data['ip']);
        $stmt->execute();
        return $this->conn->insert_id;
    }

    /**
     * Kraći upis sa stranice: zaposleni koji je uradio akciju, vrsta akcije i opis.
     * IP adresa se čita iz zahteva.
     */
    public function upisi(Admin $admin, string $akcija, string $opis): int
    {
        return $this->create([
            'admin_id' => (int)$admin->id,
            'admin_ime' => $admin->ime_prezime,
            'akcija' => $akcija,
            'opis' => $opis,
            'ip' => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        ]);
    }

    public function update(int $id, array $data)
    {
        throw new LogicException('Zapisi u dnevniku se ne menjaju.');
    }

    public function delete(int $id)
    {
        throw new LogicException('Zapisi u dnevniku se ne brišu.');
    }

    /**
     * Zapisi, najnoviji prvo: ['akcija' => 'kartica', 'pretraga' => 'Marko', 'limit' => 30, 'offset' => 0].
     * Vraća [['id', 'admin_id', 'admin_ime', 'akcija', 'opis', 'ip', 'vreme'], ...]
     */
    public function list(array $conditions = [])
    {
        [$where, $tipovi, $vrednosti] = $this->uslov($conditions);
        $limit = max(1, (int)($conditions['limit'] ?? 30));
        $offset = max(0, (int)($conditions['offset'] ?? 0));

        $stmt = $this->conn->prepare(
            "SELECT id, admin_id, admin_ime, akcija, opis, ip, vreme
             FROM admin_dnevnik
             WHERE $where
             ORDER BY vreme DESC, id DESC
             LIMIT ? OFFSET ?"
        );
        $vrednosti[] = $limit;
        $vrednosti[] = $offset;
        $stmt->bind_param($tipovi . 'ii', ...$vrednosti);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Koliko zapisa odgovara filteru (za strane) */
    public function broj(array $conditions = []): int
    {
        [$where, $tipovi, $vrednosti] = $this->uslov($conditions);
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS broj FROM admin_dnevnik WHERE $where");
        if ($tipovi !== '') {
            $stmt->bind_param($tipovi, ...$vrednosti);
        }
        $stmt->execute();
        return (int)$stmt->get_result()->fetch_assoc()['broj'];
    }

    /** WHERE deo za filter: [sql, tipovi za bind_param, vrednosti] – tekst ide samo kroz parametre */
    private function uslov(array $conditions): array
    {
        $where = '1 = 1';
        $tipovi = '';
        $vrednosti = [];

        $akcija = (string)($conditions['akcija'] ?? '');
        if ($akcija !== '') {
            $where .= ' AND akcija = ?';
            $tipovi .= 's';
            $vrednosti[] = $akcija;
        }

        $pretraga = trim((string)($conditions['pretraga'] ?? ''));
        if ($pretraga !== '') {
            // % i _ iz unosa se traže kao obični znaci, a ne kao džokeri u LIKE
            $uzorak = '%' . addcslashes($pretraga, '\\%_') . '%';
            $where .= ' AND (opis LIKE ? OR admin_ime LIKE ?)';
            $tipovi .= 'ss';
            $vrednosti[] = $uzorak;
            $vrednosti[] = $uzorak;
        }

        return [$where, $tipovi, $vrednosti];
    }
}
