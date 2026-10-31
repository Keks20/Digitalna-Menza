<?php

/**
 * Mesečna stanarina po ugovoru o smeštaju (sa podacima o sobi kad dolazi iz JOIN upita).
 */
class SmestajZaduzenje
{
    public $id;
    public $ugovor_id;
    public $mesec;
    public $iznos;
    public $rok_placanja;
    public $placeno;
    public $datum_placanja;
    public $transakcija_id;

    // Iz JOIN-a sa ugovorom i sobom
    public $dom;
    public $paviljon;
    public $soba;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->ugovor_id = $data['ugovor_id'] ?? null;
        $this->mesec = $data['mesec'] ?? null;
        $this->iznos = (float)($data['iznos'] ?? 0);
        $this->rok_placanja = $data['rok_placanja'] ?? null;
        $this->placeno = (bool)($data['placeno'] ?? false);
        $this->datum_placanja = $data['datum_placanja'] ?? null;
        $this->transakcija_id = $data['transakcija_id'] ?? null;
        $this->dom = $data['dom'] ?? null;
        $this->paviljon = $data['paviljon'] ?? null;
        $this->soba = $data['soba'] ?? null;
    }

    public function kasni(): bool
    {
        return !$this->placeno && $this->rok_placanja < date('Y-m-d');
    }
}
