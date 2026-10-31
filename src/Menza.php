<?php

class Menza
{
    public $id;
    public $naziv;
    public $adresa;
    public $kapacitet_dnevno;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->naziv = $data['naziv'] ?? '';
        $this->adresa = $data['adresa'] ?? '';
        $this->kapacitet_dnevno = (int)($data['kapacitet_dnevno'] ?? 0);
    }
}
