<?php

require_once __DIR__ . '/Format.php';

/**
 * Pomoćne funkcije za rad sa danima i nedeljama (jelovnik za studente i za admina).
 */
class Kalendar
{
    /**
     * Datum iz adrese ('Y-m-d'), ili $podrazumevano ako nije zadat ili nije ispravan.
     * Format se proverava strogo, da u upit nikad ne ode nešto što nije datum.
     */
    public static function datumIzAdrese(?string $vrednost, string $podrazumevano): string
    {
        $vrednost = $vrednost ?? '';
        $datum = DateTime::createFromFormat('!Y-m-d', $vrednost);
        if ($datum && $datum->format('Y-m-d') === $vrednost) {
            return $vrednost;
        }
        return $podrazumevano;
    }

    /** Ponedeljak nedelje u kojoj je dati dan: "2026-11-05" -> "2026-11-02" */
    public static function ponedeljak(string $dan): string
    {
        $t = strtotime($dan);
        return date('Y-m-d', strtotime('-' . (date('N', $t) - 1) . ' days', $t));
    }

    /** Dan pomeren za $brojDana (može i negativno): ("2026-11-05", -1) -> "2026-11-04" */
    public static function pomeri(string $dan, int $brojDana): string
    {
        return date('Y-m-d', strtotime(($brojDana >= 0 ? '+' : '') . $brojDana . ' days', strtotime($dan)));
    }

    /**
     * Sedam dana nedelje u kojoj je izabrani dan:
     * [['datum' => '2026-11-02', 'dan' => 'Pon', 'broj' => 2, 'izabran' => false, 'danas' => false], ...]
     */
    public static function daniNedelje(string $izabraniDan): array
    {
        $ponedeljak = self::ponedeljak($izabraniDan);
        $danas = date('Y-m-d');
        $dani = [];
        for ($i = 0; $i < 7; $i++) {
            $datum = self::pomeri($ponedeljak, $i);
            $dani[] = [
                'datum' => $datum,
                'dan' => Format::danKratko($datum),
                'broj' => (int)date('j', strtotime($datum)),
                'izabran' => $datum === $izabraniDan,
                'danas' => $datum === $danas,
            ];
        }
        return $dani;
    }
}
