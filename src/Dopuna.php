<?php

/**
 * Dopuna kartice koja je u toku – čuva se u sesiji između koraka:
 *   dopuna.php (iznos i način) -> dopuna_kartica.php ili dopuna_ips.php -> dopuna_uspesno.php
 */
class Dopuna
{
    public const NAJMANJI_IZNOS = 100;
    public const NAJVECI_IZNOS = 20000;
    public const PONUDJENI_IZNOSI = [500, 1000, 2000, 3000, 5000];
    public const NACINI = ['platna_kartica', 'ips_qr'];

    private const KLJUC = 'dopuna';

    /**
     * Provera iznosa i načina plaćanja. Vraća listu grešaka (prazna = sve ispravno).
     */
    public static function proveri($iznos, string $nacin): array
    {
        $greske = [];
        if (!is_numeric($iznos) || (float)$iznos != floor((float)$iznos)) {
            $greske[] = 'Iznos upiši kao ceo broj dinara.';
        } elseif ($iznos < self::NAJMANJI_IZNOS || $iznos > self::NAJVECI_IZNOS) {
            $greske[] = 'Iznos dopune mora biti od ' . number_format(self::NAJMANJI_IZNOS, 0, ',', '.')
                . ' do ' . number_format(self::NAJVECI_IZNOS, 0, ',', '.') . ' RSD.';
        }
        if (!in_array($nacin, self::NACINI, true)) {
            $greske[] = 'Izaberi način plaćanja.';
        }
        return $greske;
    }

    /** Početak nove dopune (prethodna nezavršena se zaboravlja) */
    public static function zapocni(int $iznos, string $nacin): void
    {
        $_SESSION[self::KLJUC] = ['iznos' => $iznos, 'nacin' => $nacin, 'transakcija_id' => null];
    }

    /** Dopuna u toku: ['iznos', 'nacin', 'transakcija_id'] ili null */
    public static function trenutna(): ?array
    {
        return $_SESSION[self::KLJUC] ?? null;
    }

    /** Pamti transakciju koja čeka potvrdu (IPS), da se pri osvežavanju ne pravi nova */
    public static function postaviTransakciju(int $transakcijaId): void
    {
        if (isset($_SESSION[self::KLJUC])) {
            $_SESSION[self::KLJUC]['transakcija_id'] = $transakcijaId;
        }
    }

    /** Kraj dopune (uspeh ili odustajanje) */
    public static function zavrsi(): void
    {
        unset($_SESSION[self::KLJUC]);
    }
}
