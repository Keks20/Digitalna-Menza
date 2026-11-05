<?php

require_once __DIR__ . '/BaseAuth.php';

/**
 * Prijava studenta brojem studentske kartice i lozinkom.
 * Sesija i CSRF zaštita su nasleđeni iz BaseAuth.
 */
class Auth extends BaseAuth
{
    public function login(string $brojKartice, string $lozinka): bool
    {
        // Prihvatamo i "0012 3456 7890" i "001234567890"
        $brojKartice = preg_replace('/\D/', '', $brojKartice);

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
            $this->greska = 'Pogrešan broj kartice ili lozinka.';
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
        return true;
    }

    /**
     * Odjavljuje samo studenta – ako je u istom pregledaču prijavljen i zaposleni SC-a, on ostaje.
     */
    public function logout(): void
    {
        unset($_SESSION['student_id'], $_SESSION['kartica_id'], $_SESSION['ime'], $_SESSION['prezime']);
        session_regenerate_id(true);
    }

    public function check(): bool
    {
        return !empty($_SESSION['student_id']);
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
