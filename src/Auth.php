<?php

require_once __DIR__ . '/Database.php';

/**
 * Prijava studenta brojem studentske kartice i lozinkom, sesija i CSRF zaštita.
 */
class Auth
{
    private $conn;

    /** @var string Poruka o grešci posle neuspešne prijave */
    private $greska = '';

    public function __construct()
    {
        $this->conn = Database::getInstance();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

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

    public function getGreska(): string
    {
        return $this->greska;
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();
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

    public function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function validateCsrfToken(string $token): bool
    {
        return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
