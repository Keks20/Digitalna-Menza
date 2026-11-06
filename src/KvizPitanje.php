<?php

/**
 * Jedno pitanje mini kviza o aplikaciji (tabela kviz_pitanja).
 */
class KvizPitanje
{
    public const SLOVA = ['a', 'b', 'c', 'd'];

    private const NAZIVI_TEZINE = ['lako' => 'Lako', 'srednje' => 'Srednje', 'tesko' => 'Teško'];

    public $id;
    public $tema;
    public $tezina;      // lako | srednje | tesko
    public $pitanje;
    public $odgovor_a;
    public $odgovor_b;
    public $odgovor_c;
    public $odgovor_d;
    public $tacan;       // a | b | c | d
    public $objasnjenje;
    public $aktivno;

    public function __construct(array $data = [])
    {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->tema = $data['tema'] ?? '';
        $this->tezina = $data['tezina'] ?? 'lako';
        $this->pitanje = $data['pitanje'] ?? '';
        $this->odgovor_a = $data['odgovor_a'] ?? '';
        $this->odgovor_b = $data['odgovor_b'] ?? '';
        $this->odgovor_c = $data['odgovor_c'] ?? '';
        $this->odgovor_d = $data['odgovor_d'] ?? '';
        $this->tacan = $data['tacan'] ?? 'a';
        $this->objasnjenje = $data['objasnjenje'] ?? '';
        $this->aktivno = (int)($data['aktivno'] ?? 1);
    }

    /** Ponuđeni odgovori: ['a' => '...', 'b' => '...', 'c' => '...', 'd' => '...'] */
    public function odgovori(): array
    {
        return [
            'a' => $this->odgovor_a,
            'b' => $this->odgovor_b,
            'c' => $this->odgovor_c,
            'd' => $this->odgovor_d,
        ];
    }

    /** Tekst tačnog odgovora */
    public function tacanOdgovor(): string
    {
        return $this->odgovori()[$this->tacan] ?? '';
    }

    public function jeTacan(?string $slovo): bool
    {
        return $slovo !== null && $slovo === $this->tacan;
    }

    /** 'tesko' -> "Teško" (za oznaku pored pitanja) */
    public function nazivTezine(): string
    {
        return self::NAZIVI_TEZINE[$this->tezina] ?? $this->tezina;
    }
}
