<?php

/**
 * Jedan zapis u dnevniku akcija zaposlenih (tabela admin_dnevnik).
 */
class StavkaDnevnika
{
    public $id;
    public $admin_id;     // null ako je nalog zaposlenog u međuvremenu obrisan
    public $admin_ime;    // ime i prezime u trenutku akcije
    public $akcija;       // ključ iz AdminDnevnikRepository::AKCIJE
    public $opis;
    public $ip;
    public $vreme;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->admin_id = $data['admin_id'] ?? null;
        $this->admin_ime = $data['admin_ime'] ?? '';
        $this->akcija = $data['akcija'] ?? '';
        $this->opis = $data['opis'] ?? '';
        $this->ip = $data['ip'] ?? '';
        $this->vreme = $data['vreme'] ?? null;
    }
}
