<?php

class Student
{
    public $id;
    public $ime;
    public $prezime;
    public $broj_indeksa;
    public $ustanova;
    public $email;
    public $kreirano;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->ime = $data['ime'] ?? '';
        $this->prezime = $data['prezime'] ?? '';
        $this->broj_indeksa = $data['broj_indeksa'] ?? '';
        $this->ustanova = $data['ustanova'] ?? '';
        $this->email = $data['email'] ?? '';
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
}
