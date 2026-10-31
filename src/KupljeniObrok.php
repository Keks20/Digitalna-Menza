<?php

/**
 * Mesečni paket jednog tipa obroka (npr. 10 ručkova za novembar).
 */
class KupljeniObrok
{
    public $id;
    public $kartica_id;
    public $tip_obroka_id;
    public $mesec;
    public $kolicina;
    public $cena_po_obroku;
    public $transakcija_id;
    public $kreirano;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->kartica_id = $data['kartica_id'] ?? null;
        $this->tip_obroka_id = $data['tip_obroka_id'] ?? null;
        $this->mesec = $data['mesec'] ?? null;
        $this->kolicina = (int)($data['kolicina'] ?? 0);
        $this->cena_po_obroku = (float)($data['cena_po_obroku'] ?? 0);
        $this->transakcija_id = $data['transakcija_id'] ?? null;
        $this->kreirano = $data['kreirano'] ?? null;
    }
}
