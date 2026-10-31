<?php

/**
 * Jedno jelo u ponudi: menza + dan + obrok (npr. Ručak u Menzi Ekonomski 31.10.).
 */
class StavkaJelovnika
{
    public $id;
    public $menza_id;
    public $datum;
    public $tip_obroka_id;
    public $jelo;
    public $posno;
    public $redosled;
    public $objavljen;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->menza_id = $data['menza_id'] ?? null;
        $this->datum = $data['datum'] ?? null;
        $this->tip_obroka_id = $data['tip_obroka_id'] ?? null;
        $this->jelo = $data['jelo'] ?? '';
        $this->posno = (bool)($data['posno'] ?? false);
        $this->redosled = (int)($data['redosled'] ?? 1);
        $this->objavljen = (bool)($data['objavljen'] ?? true);
    }
}
