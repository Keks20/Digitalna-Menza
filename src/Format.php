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

    /** "2026-11-01" -> "novembar 2026" */
    public static function mesecGodina(string $datum): string
    {
        return self::mesec($datum) . ' ' . date('Y', strtotime($datum));
    }

    /** "07:00:00" -> "07:00" */
    public static function vreme(string $vreme): string
    {
        return substr($vreme, 0, 5);
    }

    /** Cena obroka bez nepotrebnih decimala: 90 -> "90 RSD", 92.5 -> "92,50 RSD" */
    public static function cena($iznos): string
    {
        $iznos = (float)$iznos;
        $decimale = ($iznos == floor($iznos)) ? 0 : 2;
        return number_format($iznos, $decimale, ',', '.') . ' RSD';
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

    /** Broj transakcije za prikaz: (123, "2026-11-05 12:41:00") -> "TX-2026-11-000123" */
    public static function brojTransakcije(int $id, string $kreirano): string
    {
        return 'TX-' . date('Y-m', strtotime($kreirano)) . '-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);
    }

    /** 'platna_kartica' -> "Platnom karticom", 'ips_qr' -> "IPS QR", 'kartica' -> "Sa studentske kartice" */
    public static function nacinPlacanja(string $nacin): string
    {
        $nazivi = [
            'platna_kartica' => 'Platnom karticom',
            'ips_qr' => 'IPS QR',
            'kartica' => 'Sa studentske kartice',
        ];
        return $nazivi[$nacin] ?? $nacin;
    }

    /** 'uspesno' -> "Uspešno", 'ceka_potvrdu' -> "Čeka potvrdu", 'odbijeno' -> "Odbijeno" */
    public static function statusTransakcije(string $status): string
    {
        $nazivi = [
            'uspesno' => 'Uspešno',
            'ceka_potvrdu' => 'Čeka potvrdu',
            'odbijeno' => 'Odbijeno',
        ];
        return $nazivi[$status] ?? $status;
    }

    /** 'uplata' -> "Uplata", 'obroci' -> "Obroci", 'smestaj' -> "Smeštaj" */
    public static function tipTransakcije(string $tip): string
    {
        $nazivi = [
            'uplata' => 'Uplata',
            'obroci' => 'Obroci',
            'smestaj' => 'Smeštaj',
            'refundacija' => 'Refundacija',
        ];
        return $nazivi[$tip] ?? $tip;
    }

    /** Reč posle broja: (1, 'transakcija', 'transakcije', 'transakcija') -> "transakcija", 2 -> "transakcije", 5 -> "transakcija" */
    public static function mnozina(int $broj, string $jedan, string $dvaDoCetiri, string $pet): string
    {
        $poslednje = $broj % 10;
        $poslednjaDva = $broj % 100;
        if ($poslednje === 1 && $poslednjaDva !== 11) {
            return $jedan;
        }
        if ($poslednje >= 2 && $poslednje <= 4 && ($poslednjaDva < 12 || $poslednjaDva > 14)) {
            return $dvaDoCetiri;
        }
        return $pet;
    }
}
