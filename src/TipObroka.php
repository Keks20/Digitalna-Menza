<?php

require_once __DIR__ . '/Format.php';

class TipObroka
{
    public $id;
    public $naziv;
    public $cena;
    public $vreme_od;
    public $vreme_do;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->naziv = $data['naziv'] ?? '';
        $this->cena = (float)($data['cena'] ?? 0);
        $this->vreme_od = $data['vreme_od'] ?? '';
        $this->vreme_do = $data['vreme_do'] ?? '';
    }

    /** "07:00–09:30" */
    public function radnoVreme(): string
    {
        return Format::vreme($this->vreme_od) . '–' . Format::vreme($this->vreme_do);
    }
}
