<?php

require_once __DIR__ . '/Format.php';

class Kartica
{
    public $id;
    public $student_id;
    public $broj_kartice;
    public $stanje;
    public $status;
    public $kreirano;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->student_id = $data['student_id'] ?? null;
        $this->broj_kartice = $data['broj_kartice'] ?? '';
        $this->stanje = (float)($data['stanje'] ?? 0);
        $this->status = $data['status'] ?? 'aktivna';
        $this->kreirano = $data['kreirano'] ?? null;
    }

    public function jeAktivna(): bool
    {
        return $this->status === 'aktivna';
    }

    /** "0012 3456 7890" */
    public function formatiranBroj(): string
    {
        return Format::brojKartice($this->broj_kartice);
    }
}
