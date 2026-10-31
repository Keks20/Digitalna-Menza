<?php

/**
 * Pomoćne funkcije za prikaz: novac, datumi i brojevi kartica na srpskom.
 */
class Format
{
    private const DANI = ['Nedelja', 'Ponedeljak', 'Utorak', 'Sreda', 'Četvrtak', 'Petak', 'Subota'];
    private const MESECI = [
        1 => 'januar', 'februar', 'mart', 'april', 'maj', 'jun',
        'jul', 'avgust', 'septembar', 'oktobar', 'novembar', 'decembar',
    ];

    /** 3240 -> "3.240,00 RSD" */
    public static function novac($iznos, bool $saZnakom = false): string
    {
        $iznos = (float)$iznos;
        $znak = ($saZnakom && $iznos > 0) ? '+' : '';
        return $znak . number_format($iznos, 2, ',', '.') . ' RSD';
    }

    /** "001234567890" -> "0012 3456 7890" */
    public static function brojKartice(string $broj): string
    {
        return trim(chunk_split($broj, 4, ' '));
    }

    /** "2026-10-31" -> "31.10.2026." */
    public static function datum(string $datum): string
    {
        return date('d.m.Y.', strtotime($datum));
    }

    /** "2026-10-31 12:41:00" -> "31.10. 12:41" */
    public static function datumVreme(string $datumVreme): string
    {
        return date('d.m. H:i', strtotime($datumVreme));
    }

    /** "2026-10-31" -> "Subota, 31. oktobar 2026." */
    public static function danSaDatumom(string $datum): string
    {
        $t = strtotime($datum);
        return self::DANI[(int)date('w', $t)] . ', ' . date('j', $t) . '. ' . self::MESECI[(int)date('n', $t)] . ' ' . date('Y', $t) . '.';
    }

    /** "2026-11-01" -> "novembar" */
    public static function mesec(string $datum): string
    {
        return self::MESECI[(int)date('n', strtotime($datum))];
    }

    /** "07:00:00" -> "07:00" */
    public static function vreme(string $vreme): string
    {
        return substr($vreme, 0, 5);
    }
}
