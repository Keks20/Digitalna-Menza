<?php

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/Student.php';

class StudentRepository extends BaseRepository
{
    protected $table = 'studenti';

    protected function mapRow(array $row)
    {
        return new Student($row);
    }

    public function create(array $data)
    {
        $stmt = $this->conn->prepare('INSERT INTO studenti (ime, prezime, broj_indeksa, ustanova, email) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('sssss', $data['ime'], $data['prezime'], $data['broj_indeksa'], $data['ustanova'], $data['email']);
        $stmt->execute();
        return $this->conn->insert_id;
    }

    /**
     * Student sa podatkom da li je u studentskom domu (u_domu = ima aktivan ugovor) – od toga zavisi kategorija.
     */
    public function read(int $id)
    {
        $stmt = $this->conn->prepare(
            'SELECT s.*, EXISTS(SELECT 1 FROM smestaj_ugovori u WHERE u.student_id = s.id AND u.aktivan = 1) AS u_domu
             FROM studenti s WHERE s.id = ?'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? $this->mapRow($row) : null;
    }

    public function update(int $id, array $data)
    {
        $stmt = $this->conn->prepare('UPDATE studenti SET ime = ?, prezime = ?, broj_indeksa = ?, ustanova = ?, email = ? WHERE id = ?');
        $stmt->bind_param('sssssi', $data['ime'], $data['prezime'], $data['broj_indeksa'], $data['ustanova'], $data['email'], $id);
        return $stmt->execute();
    }

    public function list(array $conditions = [])
    {
        $result = $this->conn->query('SELECT * FROM studenti ORDER BY prezime, ime');
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // =================================================================
    // Profil: kontakt podaci, profilna slika, pročitana obaveštenja
    // =================================================================

    /** Da li neki DRUGI student već koristi ovu e-adresu */
    public function emailZauzet(string $email, int $osimStudentaId): bool
    {
        $stmt = $this->conn->prepare('SELECT 1 FROM studenti WHERE email = ? AND id <> ? LIMIT 1');
        $stmt->bind_param('si', $email, $osimStudentaId);
        $stmt->execute();
        return (bool)$stmt->get_result()->fetch_row();
    }

    /** E-adresa i telefon iz profila ($telefon = null briše broj) */
    public function promeniKontakt(int $id, string $email, ?string $telefon): bool
    {
        $stmt = $this->conn->prepare('UPDATE studenti SET email = ?, telefon = ? WHERE id = ?');
        $stmt->bind_param('ssi', $email, $telefon, $id);
        return $stmt->execute();
    }

    /** Naziv fajla profilne slike (null = bez slike) */
    public function postaviSliku(int $id, ?string $slika): bool
    {
        $stmt = $this->conn->prepare('UPDATE studenti SET slika = ? WHERE id = ?');
        $stmt->bind_param('si', $slika, $id);
        return $stmt->execute();
    }

    /** Student je otvorio zvonce: sva dosadašnja obaveštenja se računaju kao pročitana */
    public function oznaciObavestenjaProcitanim(int $id): bool
    {
        $stmt = $this->conn->prepare('UPDATE studenti SET obavestenja_procitana = NOW() WHERE id = ?');
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    /** Kategorija studenta (admin "Studenti"); vrednosti moraju biti iz Student::FINANSIRANJA i Student::PREBIVALISTA */
    public function postaviKategoriju(int $id, string $finansiranje, string $prebivaliste): bool
    {
        if (!isset(Student::FINANSIRANJA[$finansiranje]) || !isset(Student::PREBIVALISTA[$prebivaliste])) {
            throw new InvalidArgumentException('Nepoznata kategorija studenta.');
        }
        $stmt = $this->conn->prepare('UPDATE studenti SET finansiranje = ?, prebivaliste = ? WHERE id = ?');
        $stmt->bind_param('ssi', $finansiranje, $prebivaliste, $id);
        return $stmt->execute();
    }

    // =================================================================
    // Admin "Studenti": pretraga studenata sa njihovim karticama
    // =================================================================

    /**
     * Studenti sa karticom, po prezimenu i imenu.
     * Uslovi: ['q' => ime, prezime, broj indeksa ili broj kartice (sa razmacima ili bez),
     *          'status' => '' | 'aktivna' | 'blokirana', 'limit' => 20, 'offset' => 0]
     * Vraća [['id', 'ime', 'prezime', 'broj_indeksa', 'ustanova', 'email', 'finansiranje', 'prebivaliste', 'u_domu',
     *         'kartica_id', 'broj_kartice', 'stanje', 'status'], ...]
     */
    public function pretraga(array $uslovi = []): array
    {
        [$where, $tipovi, $vrednosti] = $this->uslovPretrage($uslovi);
        $limit = (int)($uslovi['limit'] ?? 20);
        $offset = (int)($uslovi['offset'] ?? 0);

        $stmt = $this->conn->prepare(
            "SELECT s.id, s.ime, s.prezime, s.broj_indeksa, s.ustanova, s.email, s.finansiranje, s.prebivaliste,
                    EXISTS(SELECT 1 FROM smestaj_ugovori u WHERE u.student_id = s.id AND u.aktivan = 1) AS u_domu,
                    k.id AS kartica_id, k.broj_kartice, k.stanje, k.status
             FROM studenti s
             JOIN kartice k ON k.student_id = s.id
             WHERE $where
             ORDER BY s.prezime, s.ime, s.id
             LIMIT ? OFFSET ?"
        );
        $tipovi .= 'ii';
        $vrednosti[] = $limit;
        $vrednosti[] = $offset;
        $stmt->bind_param($tipovi, ...$vrednosti);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Koliko studenata odgovara pretrazi (za strane); isti uslovi kao pretraga() */
    public function brojUPretrazi(array $uslovi = []): int
    {
        [$where, $tipovi, $vrednosti] = $this->uslovPretrage($uslovi);
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS broj FROM studenti s JOIN kartice k ON k.student_id = s.id WHERE $where");
        if ($tipovi !== '') {
            $stmt->bind_param($tipovi, ...$vrednosti);
        }
        $stmt->execute();
        return (int)$stmt->get_result()->fetch_assoc()['broj'];
    }

    /** Broj kartica po statusu: ['aktivna' => 29, 'blokirana' => 1] (za dugmad filtera) */
    public function brojPoStatusuKartice(): array
    {
        $brojevi = ['aktivna' => 0, 'blokirana' => 0];
        $result = $this->conn->query('SELECT status, COUNT(*) AS broj FROM kartice GROUP BY status');
        foreach ($result->fetch_all(MYSQLI_ASSOC) as $row) {
            $brojevi[$row['status']] = (int)$row['broj'];
        }
        return $brojevi;
    }

    /** WHERE deo za pretragu: [sql, tipovi za bind_param, vrednosti] – tekst ide samo kroz parametre */
    private function uslovPretrage(array $uslovi): array
    {
        $where = '1 = 1';
        $tipovi = '';
        $vrednosti = [];

        $q = trim((string)($uslovi['q'] ?? ''));
        if ($q !== '') {
            // % i _ iz unosa se traže kao obični znaci, a ne kao džokeri u LIKE
            $tekst = '%' . addcslashes($q, '%_\\') . '%';
            $where .= " AND (s.ime LIKE ? OR s.prezime LIKE ?
                             OR CONCAT(s.ime, ' ', s.prezime) LIKE ? OR CONCAT(s.prezime, ' ', s.ime) LIKE ?
                             OR s.broj_indeksa LIKE ?";
            $tipovi .= 'sssss';
            array_push($vrednosti, $tekst, $tekst, $tekst, $tekst, $tekst);

            // Broj kartice se traži i bez razmaka: "0012 3456" -> "00123456"
            $cifre = preg_replace('/\D/', '', $q);
            if ($cifre !== '') {
                $where .= ' OR k.broj_kartice LIKE ?';
                $tipovi .= 's';
                $vrednosti[] = '%' . $cifre . '%';
            }
            $where .= ')';
        }

        $status = (string)($uslovi['status'] ?? '');
        if ($status !== '') {
            $where .= ' AND k.status = ?';
            $tipovi .= 's';
            $vrednosti[] = $status;
        }

        return [$where, $tipovi, $vrednosti];
    }
}
