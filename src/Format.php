<?php

/**
 * Pomoćne funkcije za prikaz: novac, datumi i brojevi kartica na srpskom.
 */
class Format
{
    private const DANI = ['Nedelja', 'Ponedeljak', 'Utorak', 'Sreda', 'Četvrtak', 'Petak', 'Subota'];
    private const DANI_KRATKO = ['Ned', 'Pon', 'Uto', 'Sre', 'Čet', 'Pet', 'Sub'];
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

    /** "2026-11-05" -> "Čet" (za dugmad sa danima u nedelji) */
    public static function danKratko(string $datum): string
    {
        return self::DANI_KRATKO[(int)date('w', strtotime($datum))];
    }

    /**
     * Opis nedelje od ponedeljka do nedelje:
     *   "2026-11-02" -> "2–8. novembar 2026."
     *   "2026-10-26" -> "26. oktobar – 1. novembar 2026."
     */
    public static function nedelja(string $ponedeljak): string
    {
        $od = strtotime($ponedeljak);
        $do = strtotime($ponedeljak . ' +6 days');
        $godina = date('Y', $do) . '.';

        if (date('n', $od) === date('n', $do)) {
            return date('j', $od) . '–' . date('j', $do) . '. ' . self::MESECI[(int)date('n', $do)] . ' ' . $godina;
        }
        $pocetak = date('j', $od) . '. ' . self::MESECI[(int)date('n', $od)];
        if (date('Y', $od) !== date('Y', $do)) {
            $pocetak .= ' ' . date('Y', $od) . '.';
        }
        return $pocetak . ' – ' . date('j', $do) . '. ' . self::MESECI[(int)date('n', $do)] . ' ' . $godina;
    }
}
