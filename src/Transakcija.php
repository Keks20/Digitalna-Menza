<?php

class Transakcija
{
    public $id;
    public $kartica_id;
    public $tip;          // uplata | obroci | smestaj
    public $nacin;        // platna_kartica | ips_qr | kartica
    public $iznos;        // + uplata, - obroci i smeštaj
    public $stanje_posle;
    public $status;       // uspesno | ceka_potvrdu | odbijeno
    public $opis;
    public $kreirano;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->kartica_id = $data['kartica_id'] ?? null;
        $this->tip = $data['tip'] ?? '';
        $this->nacin = $data['nacin'] ?? '';
        $this->iznos = (float)($data['iznos'] ?? 0);
        $this->stanje_posle = isset($data['stanje_posle']) ? (float)$data['stanje_posle'] : null;
        $this->status = $data['status'] ?? 'uspesno';
        $this->opis = $data['opis'] ?? '';
        $this->kreirano = $data['kreirano'] ?? null;
    }
}
