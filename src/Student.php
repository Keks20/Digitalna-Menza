<?php

/**
 * Student i njegova kategorija.
 *
 * Kategorija (finansiranje + mesto stanovanja) određuje kupovinu obroka:
 *   budžet, van Kragujevca ili u domu        – sva tri obroka, osnovna cena
 *   samofinansiranje, van Kragujevca         – sva tri obroka, trostruka cena
 *   budžet, živi u Kragujevcu                – samo ručak, osnovna cena
 *   samofinansiranje, živi u Kragujevcu      – samo ručak, trostruka cena
 * Student sa aktivnim ugovorom u studentskom domu se računa kao da živi van Kragujevca.
 */
class Student
{
    /** id ručka u tabeli tipovi_obroka – jedini obrok za studente koji žive u Kragujevcu */
    public const RUCAK_ID = 2;

    /** Koliko puta je obrok skuplji za samofinansirajuće studente */
    public const FAKTOR_SAMOFINANSIRANJE = 3;

    public const FINANSIRANJA = [
        'budzet' => 'Budžet',
        'samofinansiranje' => 'Samofinansiranje',
    ];
    public const PREBIVALISTA = [
        'van_kragujevca' => 'Van Kragujevca',
        'kragujevac' => 'Kragujevac',
    ];

    /** Folder sa profilnim slikama (od korena projekta) */
    public const FOLDER_SLIKA = 'uploads/profilne/';

    public $id;
    public $ime;
    public $prezime;
    public $broj_indeksa;
    public $ustanova;
    public $email;
    public $telefon;
    public $slika;          // naziv fajla u uploads/profilne/ ili null
    public $finansiranje;   // budzet | samofinansiranje
    public $prebivaliste;   // van_kragujevca | kragujevac
    public $u_domu;         // true = ima aktivan ugovor u studentskom domu
    public $kreirano;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->ime = $data['ime'] ?? '';
        $this->prezime = $data['prezime'] ?? '';
        $this->broj_indeksa = $data['broj_indeksa'] ?? '';
        $this->ustanova = $data['ustanova'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->telefon = $data['telefon'] ?? null;
        $this->slika = $data['slika'] ?? null;
        $this->finansiranje = $data['finansiranje'] ?? 'budzet';
        $this->prebivaliste = $data['prebivaliste'] ?? 'van_kragujevca';
        $this->u_domu = !empty($data['u_domu']);
        $this->kreirano = $data['kreirano'] ?? null;
    }

    public function punoIme(): string
    {
        return $this->ime . ' ' . $this->prezime;
    }

    /** "Marko Marković" -> "MM" (za krug sa inicijalima) */
    public function inicijali(): string
    {
        return mb_substr($this->ime, 0, 1) . mb_substr($this->prezime, 0, 1);
    }

    /** Putanja do profilne slike za <img src>, ili null kad slika nije postavljena */
    public function putanjaSlike(): ?string
    {
        return $this->slika ? self::FOLDER_SLIKA . $this->slika : null;
    }

    public function jeSamofinansirajuci(): bool
    {
        return $this->finansiranje === 'samofinansiranje';
    }

    /** Živi u Kragujevcu i nije u studentskom domu -> ima pravo samo na ručak */
    public function samoRucak(): bool
    {
        return $this->prebivaliste === 'kragujevac' && !$this->u_domu;
    }

    /** Da li ovaj student sme da kupi obrok te vrste (id iz tipovi_obroka) */
    public function smeDaKupi(int $tipObrokaId): bool
    {
        return !$this->samoRucak() || $tipObrokaId === self::RUCAK_ID;
    }

    /** Cena jednog obroka za ovog studenta: osnovna cena iz baze, za samofinansirajuće trostruka */
    public function cenaObroka(float $osnovnaCena): float
    {
        return round($osnovnaCena * ($this->jeSamofinansirajuci() ? self::FAKTOR_SAMOFINANSIRANJE : 1), 2);
    }

    /** "Budžet · studentski dom", "Samofinansiranje · Kragujevac" ... */
    public function nazivKategorije(): string
    {
        if ($this->u_domu) {
            $mesto = 'studentski dom';
        } else {
            $mesto = $this->prebivaliste === 'kragujevac' ? 'Kragujevac' : 'van Kragujevca';
        }
        return (self::FINANSIRANJA[$this->finansiranje] ?? $this->finansiranje) . ' · ' . $mesto;
    }

    /** Rečenica o pravima kategorije (profil, kupovina) */
    public function opisKategorije(): string
    {
        $obroci = $this->samoRucak() ? 'Možeš da kupiš samo ručak' : 'Možeš da kupiš doručak, ručak i večeru';
        $cena = $this->jeSamofinansirajuci()
            ? 'po ceni za samofinansirajuće studente (' . self::FAKTOR_SAMOFINANSIRANJE . '× osnovna cena)'
            : 'po ceni za studente na budžetu';
        return $obroci . ' ' . $cena . '.';
    }
}
