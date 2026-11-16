<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Greske.php';
require_once __DIR__ . '/PokusajiPrijave.php';

// Svaka stranica pravi Auth ili AdminAuth pre bilo čega drugog, pa se hvatanje grešaka uključuje ovde
Greske::ukljuci();

/**
 * Apstraktna osnovna klasa za prijavu.
 *
 * Zajedničko za studente (Auth) i zaposlene SC-a (AdminAuth):
 * konekcija sa bazom, pokretanje sesije, CSRF zaštita, poruka o grešci,
 * ograničenje broja pokušaja prijave i odjava posle neaktivnosti.
 * Svaka naslednica sama definiše kako se proverava prijava (login, check, logout).
 */
abstract class BaseAuth
{
    /** Posle koliko minuta bez ijednog zahteva se korisnik odjavljuje */
    public const NEAKTIVNOST_MIN = 20;

    /** @var mysqli */
    protected $conn;

    /** @var string Poruka o grešci posle neuspešne prijave */
    protected $greska = '';

    /** @var bool Korisnik je u ovom zahtevu odjavljen zbog neaktivnosti */
    protected $sesijaIstekla = false;

    public function __construct()
    {
        $this->conn = Database::getInstance();
        if (session_status() === PHP_SESSION_NONE) {
            // Kolačić sesije: JavaScript ga ne vidi (HttpOnly), ne šalje se uz zahteve sa drugih sajtova
            // osim običnog klika na link (SameSite=Lax), a preko HTTPS-a ide samo šifrovano (Secure)
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            ]);
            // ID sesije se prima samo iz kolačića i samo ako ga je izdao server
            ini_set('session.use_only_cookies', '1');
            ini_set('session.use_strict_mode', '1');
            session_start();
        }
    }

    /** Proverava podatke za prijavu i, ako su ispravni, upisuje korisnika u sesiju */
    abstract public function login(string $korisnik, string $lozinka): bool;

    /** Da li je korisnik prijavljen */
    abstract public function check(): bool;

    /** Odjava */
    abstract public function logout(): void;

    /** Ključ u sesiji pod kojim se pamti vreme poslednjeg zahteva ovog korisnika */
    abstract protected function kljucAktivnosti(): string;

    /** Vrsta naloga u tabeli pokusaji_prijave: 'student' | 'zaposleni' */
    abstract protected function vrstaPrijave(): string;

    public function getGreska(): string
    {
        return $this->greska;
    }

    /** Da li je korisnik upravo odjavljen zato što predugo nije bio aktivan (za poruku na prijavi) */
    public function sesijaIstekla(): bool
    {
        return $this->sesijaIstekla;
    }

    /**
     * Poziva se iz check() prijavljenog korisnika: ako je od poslednjeg zahteva prošlo više od
     * NEAKTIVNOST_MIN minuta, korisnik se odjavljuje; inače se beleži vreme ovog zahteva.
     */
    protected function aktivnaSesija(): bool
    {
        $kljuc = $this->kljucAktivnosti();
        $poslednja = (int)($_SESSION[$kljuc] ?? 0);

        if (time() - $poslednja > self::NEAKTIVNOST_MIN * 60) {
            $this->logout();
            $this->sesijaIstekla = true;
            return false;
        }

        $_SESSION[$kljuc] = time();
        return true;
    }

    /**
     * Da li je prijava za ovaj nalog (ili sa ove adrese) trenutno zaustavljena zbog previše pogrešnih pokušaja.
     * Ako jeste, postavlja poruku o grešci. Proverava se pre lozinke, pa ni tačna lozinka ne prolazi dok pauza traje.
     */
    protected function prijavaNaPauzi(string $korisnik): bool
    {
        $sekundi = (new PokusajiPrijave())->pauzaSekundi($this->vrstaPrijave(), $korisnik, $this->adresaKlijenta());
        if ($sekundi <= 0) {
            return false;
        }
        $this->greska = $this->porukaOPauzi($sekundi);
        return true;
    }

    /**
     * Upisuje pogrešan pokušaj i postavlja poruku o grešci: $poruka, uz upozorenje kad ostane malo pokušaja,
     * ili poruku o pauzi ako je ovo bio poslednji dozvoljeni pokušaj.
     */
    protected function neuspelaPrijava(string $korisnik, string $poruka): void
    {
        $ostalo = (new PokusajiPrijave())->zabelezi($this->vrstaPrijave(), $korisnik, $this->adresaKlijenta());

        if ($ostalo === 0) {
            $this->greska = $this->porukaOPauzi(PokusajiPrijave::PAUZA_MIN * 60);
        } elseif ($ostalo <= 2) {
            $this->greska = $poruka . ' Još ' . $ostalo . ($ostalo === 1 ? ' pokušaj' : ' pokušaja')
                . ' pre pauze od ' . PokusajiPrijave::PAUZA_MIN . ' minuta.';
        } else {
            $this->greska = $poruka;
        }
    }

    /**
     * Posle uspešne prijave: raniji pogrešni pokušaji za taj nalog se brišu,
     * a neaktivnost se meri od ovog trenutka.
     */
    protected function uspesnaPrijava(string $korisnik): void
    {
        (new PokusajiPrijave())->obrisi($this->vrstaPrijave(), $korisnik);
        $_SESSION[$this->kljucAktivnosti()] = time();
    }

    /** IP adresa sa koje stiže zahtev (zaglavlja koja klijent može sam da upiše se ne koriste) */
    public function adresaKlijenta(): string
    {
        return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    }

    private function porukaOPauzi(int $sekundi): string
    {
        $minuta = max(1, (int)ceil($sekundi / 60));
        return 'Previše pogrešnih pokušaja prijave. Pokušaj ponovo za ' . $minuta . ' min.';
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
