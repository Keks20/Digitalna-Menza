<?php

require_once __DIR__ . '/BaseAuth.php';

/**
 * Prijava studenta brojem studentske kartice i lozinkom.
 * Sesija, CSRF zaštita, ograničenje pokušaja prijave i odjava posle neaktivnosti su nasleđeni iz BaseAuth.
 */
class Auth extends BaseAuth
{
    protected function kljucAktivnosti(): string
    {
        return 'student_aktivnost';
    }

    protected function vrstaPrijave(): string
    {
        return 'student';
    }

    public function login(string $brojKartice, string $lozinka): bool
    {
        // Prihvatamo i "0012 3456 7890" i "001234567890"
        $brojKartice = substr(preg_replace('/\D/', '', $brojKartice), 0, 50);

        // Previše pogrešnih pokušaja za ovu karticu ili sa ove adrese -> pauza
        if ($this->prijavaNaPauzi($brojKartice)) {
            return false;
        }

        $stmt = $this->conn->prepare(
            'SELECT k.id AS kartica_id, k.lozinka_hash, k.status, s.id AS student_id, s.ime, s.prezime
             FROM kartice k
             JOIN studenti s ON s.id = k.student_id
             WHERE k.broj_kartice = ?
             LIMIT 1'
        );
        $stmt->bind_param('s', $brojKartice);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        // Ista poruka i za nepostojeću karticu i za pogrešnu lozinku,
        // da napadač ne može da sazna koji brojevi kartica postoje.
        if (!$row || !password_verify($lozinka, $row['lozinka_hash'])) {
            $this->neuspelaPrijava($brojKartice, 'Pogrešan broj kartice ili lozinka.');
            return false;
        }

        if ($row['status'] !== 'aktivna') {
            $this->greska = 'Kartica je blokirana. Javi se na šalter Studentskog centra.';
            return false;
        }

        // Nov ID sesije posle prijave (zaštita od "session fixation" napada)
        session_regenerate_id(true);
        $_SESSION['student_id'] = (int)$row['student_id'];
        $_SESSION['kartica_id'] = (int)$row['kartica_id'];
        $_SESSION['ime'] = $row['ime'];
        $_SESSION['prezime'] = $row['prezime'];
        $this->uspesnaPrijava($brojKartice);
        return true;
    }

    /**
     * Odjavljuje samo studenta – ako je u istom pregledaču prijavljen i zaposleni SC-a, on ostaje.
     */
    public function logout(): void
    {
        unset($_SESSION['student_id'], $_SESSION['kartica_id'], $_SESSION['ime'], $_SESSION['prezime'],
            $_SESSION[$this->kljucAktivnosti()]);
        session_regenerate_id(true);
    }

    /** Prijavljen je i nije predugo neaktivan (posle NEAKTIVNOST_MIN minuta sledi odjava) */
    public function check(): bool
    {
        return !empty($_SESSION['student_id']) && $this->aktivnaSesija();
    }

    public function studentId(): ?int
    {
        return $this->check() ? (int)$_SESSION['student_id'] : null;
    }

    public function karticaId(): ?int
    {
        return $this->check() ? (int)$_SESSION['kartica_id'] : null;
    }
}
