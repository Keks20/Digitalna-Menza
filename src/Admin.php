<?php

/**
 * Zaposleni u Studentskom centru: administrator (služba ishrane) ili kasir u menzi.
 */
class Admin
{
    public $id;
    public $korisnicko_ime;
    public $ime_prezime;
    public $uloga;        // admin | kasir
    public $kreirano;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->korisnicko_ime = $data['korisnicko_ime'] ?? '';
        $this->ime_prezime = $data['ime_prezime'] ?? '';
        $this->uloga = $data['uloga'] ?? 'admin';
        $this->kreirano = $data['kreirano'] ?? null;
    }

    public function jeAdmin(): bool
    {
        return $this->uloga === 'admin';
    }

    public function jeKasir(): bool
    {
        return $this->uloga === 'kasir';
    }

    /** "Jelena Petrović" -> "JP" (za krug sa inicijalima) */
    public function inicijali(): string
    {
        $inicijali = '';
        foreach (explode(' ', $this->ime_prezime) as $deo) {
            if ($deo !== '') {
                $inicijali .= mb_substr($deo, 0, 1);
            }
        }
        return mb_substr($inicijali, 0, 2);
    }

    /** "Administrator" / "Kasir" – za prikaz ispod imena */
    public function nazivUloge(): string
    {
        return $this->jeAdmin() ? 'Administrator' : 'Kasir';
    }
}
