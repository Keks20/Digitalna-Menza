<?php

class Transakcija
{
    public $id;
    public $kartica_id;
    public $tip;          // uplata | obroci | smestaj | refundacija
    public $nacin;        // platna_kartica | ips_qr | kartica
    public $iznos;        // + uplata, - obroci, smeštaj i refundacija
    public $stanje_posle;
    public $status;       // uspesno | ceka_potvrdu | odbijeno
    public $opis;
    public $refundirano;  // kada je uplata vraćena studentu (null = nije)
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
        $this->refundirano = $data['refundirano'] ?? null;
        $this->kreirano = $data['kreirano'] ?? null;
    }
}
