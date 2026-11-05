<?php

require_once __DIR__ . '/Database.php';

/**
 * Apstraktna osnovna klasa za prijavu.
 *
 * Zajedničko za studente (Auth) i zaposlene SC-a (AdminAuth):
 * konekcija sa bazom, pokretanje sesije, CSRF zaštita i poruka o grešci.
 * Svaka naslednica sama definiše kako se proverava prijava (login, check, logout).
 */
abstract class BaseAuth
{
    /** @var mysqli */
    protected $conn;

    /** @var string Poruka o grešci posle neuspešne prijave */
    protected $greska = '';

    public function __construct()
    {
        $this->conn = Database::getInstance();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /** Proverava podatke za prijavu i, ako su ispravni, upisuje korisnika u sesiju */
    abstract public function login(string $korisnik, string $lozinka): bool;

    /** Da li je korisnik prijavljen */
    abstract public function check(): bool;

    /** Odjava */
    abstract public function logout(): void;

    public function getGreska(): string
    {
        return $this->greska;
    }

    /**
     * CSRF token: nasumičan niz koji se čuva u sesiji i šalje u skrivenom polju forme.
     * Tuđi sajt ne zna token, pa ne može da pošalje formu u ime prijavljenog korisnika.
     */
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
