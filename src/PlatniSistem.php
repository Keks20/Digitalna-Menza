<?php

/**
 * Simulacija banke za plaćanje platnom karticom.
 *
 * U pravom sistemu podatke kartice unosi korisnik na stranici banke (3-D Secure)
 * i oni nikad ne stižu do servera aplikacije. Ovde se provera i naplata samo simuliraju:
 * podaci kartice se ne čuvaju, u opisu transakcije ostaju samo poslednje 4 cifre.
 *
 * Test kartice (bilo koje ime, datum isteka u budućnosti, bilo koji CVC od 3 cifre):
 *   4242 4242 4242 4242  – plaćanje prolazi
 *   4000 0000 0000 0002  – banka odbija (nedovoljno sredstava)
 */
class PlatniSistem
{
    public const ODBIJENA_KARTICA = '4000000000000002';

    /**
     * Provera podataka kartice pre slanja banci.
     * Vraća greške po poljima: ['broj' => '...', 'ime' => '...', 'istek' => '...', 'cvc' => '...'] (prazan niz = sve ispravno)
     */
    public function proveriPodatke(string $broj, string $ime, string $istek, string $cvc): array
    {
        $greske = [];
        $broj = self::samoCifre($broj);

        if (strlen($broj) < 13 || strlen($broj) > 19 || !self::luhn($broj)) {
            $greske['broj'] = 'Broj kartice nije ispravan.';
        }

        if (mb_strlen(trim($ime)) < 3) {
            $greske['ime'] = 'Upiši ime i prezime kao na kartici.';
        }

        // Datum isteka u obliku MM/GG; kartica važi do kraja tog meseca
        if (!preg_match('/^(\d{2})\s*\/\s*(\d{2})$/', trim($istek), $m) || (int)$m[1] < 1 || (int)$m[1] > 12) {
            $greske['istek'] = 'Datum isteka upiši kao MM/GG.';
        } else {
            $poslednjiDan = date('Y-m-t', mktime(0, 0, 0, (int)$m[1], 1, 2000 + (int)$m[2]));
            if ($poslednjiDan < date('Y-m-d')) {
                $greske['istek'] = 'Kartica je istekla.';
            }
        }

        if (!preg_match('/^\d{3}$/', trim($cvc))) {
            $greske['cvc'] = 'CVC su 3 cifre sa poleđine kartice.';
        }

        return $greske;
    }

    /**
     * Zahtev banci za naplatu. Vraća ['odobreno' => bool, 'poruka' => '...'].
     */
    public function naplati(string $broj, float $iznos): array
    {
        if (self::samoCifre($broj) === self::ODBIJENA_KARTICA) {
            return ['odobreno' => false, 'poruka' => 'Banka je odbila plaćanje: nedovoljno sredstava na računu.'];
        }
        return ['odobreno' => true, 'poruka' => 'Plaćanje je odobreno.'];
    }

    /** "4242 4242 4242 4242" -> "4242" (za opis transakcije) */
    public static function poslednjeCetiri(string $broj): string
    {
        return substr(self::samoCifre($broj), -4);
    }

    private static function samoCifre(string $tekst): string
    {
        return preg_replace('/\D/', '', $tekst);
    }

    /**
     * Luhn provera: kontrolna cifra koju imaju svi brojevi platnih kartica.
     * Hvata većinu grešaka u kucanju (jedna pogrešna cifra, dve zamenjene susedne cifre).
     */
    private static function luhn(string $broj): bool
    {
        $zbir = 0;
        $duplira = false;
        for ($i = strlen($broj) - 1; $i >= 0; $i--) {
            $cifra = (int)$broj[$i];
            if ($duplira) {
                $cifra *= 2;
                if ($cifra > 9) {
                    $cifra -= 9;
                }
            }
            $zbir += $cifra;
            $duplira = !$duplira;
        }
        return $zbir % 10 === 0;
    }
}
