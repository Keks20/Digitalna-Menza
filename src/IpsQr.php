<?php

require_once __DIR__ . '/Format.php';

/**
 * Podaci za uplatu Studentskom centru preko IPS QR koda (standard NBS za instant plaćanja).
 * Aplikacija banke skenira kod i sama popuni nalog: primaoca, račun, iznos, šifru i poziv na broj.
 * Račun primaoca je izmišljen.
 */
class IpsQr
{
    public const PRIMALAC = 'Studentski centar Kragujevac';
    public const RACUN = '840-0000123456789-12';
    public const SIFRA_PLACANJA = '289';
    public const SVRHA = 'Dopuna studentske kartice';

    /**
     * Podaci za prikaz u tabeli "Podaci za uplatu".
     * Poziv na broj je broj studentske kartice – po njemu se zna čija je uplata.
     */
    public static function podaci(float $iznos, string $brojKartice): array
    {
        return [
            'primalac' => self::PRIMALAC,
            'racun' => self::RACUN,
            'iznos' => Format::novac($iznos),
            'sifra' => self::SIFRA_PLACANJA,
            'poziv_na_broj' => $brojKartice,
            'svrha' => self::SVRHA,
        ];
    }

    /**
     * Tekst koji se upisuje u QR kod, po NBS IPS formatu:
     * K:PR|V:01|C:1|R:<18 cifara računa>|N:<primalac>|I:RSD<iznos>|SF:<šifra>|S:<svrha>|RO:00<poziv na broj>
     */
    public static function tekst(float $iznos, string $brojKartice): string
    {
        $racun = preg_replace('/\D/', '', self::RACUN);   // 840-0000123456789-12 -> 18 cifara
        $polja = [
            'K' => 'PR',
            'V' => '01',
            'C' => '1',
            'R' => $racun,
            'N' => self::PRIMALAC,
            'I' => 'RSD' . number_format($iznos, 2, ',', ''),
            'SF' => self::SIFRA_PLACANJA,
            'S' => self::SVRHA,
            'RO' => '00' . preg_replace('/\D/', '', $brojKartice),
        ];

        $delovi = [];
        foreach ($polja as $oznaka => $vrednost) {
            $delovi[] = $oznaka . ':' . $vrednost;
        }
        return implode('|', $delovi);
    }
}
