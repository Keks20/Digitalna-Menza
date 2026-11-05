<?php

require_once __DIR__ . '/BaseAuth.php';

/**
 * Prijava zaposlenih u SC-u (administrator i kasir) korisničkim imenom i lozinkom.
 *
 * Podaci o prijavi zaposlenog čuvaju se pod drugim ključevima u sesiji nego student
 * (admin_id, admin_uloga), pa se studentska i administratorska prijava ne mešaju.
 */
class AdminAuth extends BaseAuth
{
    /** Gde svaka uloga ide posle prijave */
    public const POCETNA_ZA_ULOGU = [
        'admin' => 'admin_pocetna.php',
        'kasir' => 'kasir_bonovi.php',
    ];

    public function login(string $korisnickoIme, string $lozinka): bool
    {
        $korisnickoIme = trim($korisnickoIme);

        $stmt = $this->conn->prepare('SELECT id, lozinka_hash, ime_prezime, uloga FROM admini WHERE korisnicko_ime = ? LIMIT 1');
        $stmt->bind_param('s', $korisnickoIme);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        // Ista poruka za nepostojeće ime i pogrešnu lozinku
        if (!$row || !password_verify($lozinka, $row['lozinka_hash'])) {
            $this->greska = 'Pogrešno korisničko ime ili lozinka.';
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$row['id'];
        $_SESSION['admin_uloga'] = $row['uloga'];
        $_SESSION['admin_ime'] = $row['ime_prezime'];
        return true;
    }

    /**
     * Odjavljuje samo zaposlenog – ako je u istom pregledaču prijavljen i student, on ostaje.
     */
    public function logout(): void
    {
        unset($_SESSION['admin_id'], $_SESSION['admin_uloga'], $_SESSION['admin_ime']);
        session_regenerate_id(true);
    }

    public function check(): bool
    {
        return !empty($_SESSION['admin_id']);
    }

    public function adminId(): ?int
    {
        return $this->check() ? (int)$_SESSION['admin_id'] : null;
    }

    public function uloga(): ?string
    {
        return $this->check() ? $_SESSION['admin_uloga'] : null;
    }

    /** Da li prijavljeni zaposleni ima jednu od datih uloga, npr. ['admin'] ili ['admin', 'kasir'] */
    public function imaUlogu(array $uloge): bool
    {
        return $this->check() && in_array($this->uloga(), $uloge, true);
    }

    /** Početna stranica za ulogu prijavljenog zaposlenog */
    public function pocetnaStrana(): string
    {
        return self::POCETNA_ZA_ULOGU[$this->uloga()] ?? 'admin_login.php';
    }
}
