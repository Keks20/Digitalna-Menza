<?php

class Obavestenje
{
    public $id;
    public $naslov;
    public $tekst;
    public $ciljna_grupa;   // svi | menza | dom
    public $menza_id;
    public $dom;
    public $admin_id;
    public $kreirano;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->naslov = $data['naslov'] ?? '';
        $this->tekst = $data['tekst'] ?? '';
        $this->ciljna_grupa = $data['ciljna_grupa'] ?? 'svi';
        $this->menza_id = $data['menza_id'] ?? null;
        $this->dom = $data['dom'] ?? null;
        $this->admin_id = $data['admin_id'] ?? null;
        $this->kreirano = $data['kreirano'] ?? null;
    }
}
