<?php

/**
 * QR bon za obrok.
 *
 * Tekst u QR kodu: "DM1.<kartica_id>.<tip_obroka_id>.<vreme>.<potpis>"
 *   vreme  – Unix vreme kad je bon napravljen
 *   potpis – HMAC-SHA256 (prvih 16 znakova) od kartice, tipa i vremena, sa tajnim ključem iz config.php
 *
 * Bez ključa niko ne može da napravi ispravan bon, a stari bon (npr. snimak ekrana)
 * prestaje da važi posle VAZI_SEKUNDI. Stranica Moji obroci zato traži novi bon svakih OSVEZAVANJE sekundi.
 * Da li student još ima obroka proverava kasa pri izdavanju, ne bon.
 */
class Bon
{
    public const OSVEZAVANJE = 60;
    public const VAZI_SEKUNDI = 120;
    private const PREFIKS = 'DM1';

    /** Pravi tekst bona za karticu i tip obroka */
    public static function napravi(int $karticaId, int $tipObrokaId, ?int $vreme = null): string
    {
        $vreme = $vreme ?? time();
        return self::PREFIKS . '.' . $karticaId . '.' . $tipObrokaId . '.' . $vreme . '.'
            . self::potpis($karticaId, $tipObrokaId, $vreme);
    }

    /**
     * Provera bona na kasi.
     * Vraća ['ispravan' => true, 'kartica_id' => 1, 'tip_obroka_id' => 2, 'vreme' => 1793915376]
     * ili ['ispravan' => false, 'greska' => 'Bon je istekao – student treba da osveži stranicu.']
     */
    public static function proveri(string $tekst, ?int $sada = null): array
    {
        $sada = $sada ?? time();
        $delovi = explode('.', trim($tekst));

        if (count($delovi) !== 5 || $delovi[0] !== self::PREFIKS
            || !ctype_digit($delovi[1]) || !ctype_digit($delovi[2]) || !ctype_digit($delovi[3])) {
            return ['ispravan' => false, 'greska' => 'Ovo nije QR bon Digitalne menze.'];
        }

        [, $karticaId, $tipObrokaId, $vreme, $potpis] = $delovi;
        $karticaId = (int)$karticaId;
        $tipObrokaId = (int)$tipObrokaId;
        $vreme = (int)$vreme;

        if (!hash_equals(self::potpis($karticaId, $tipObrokaId, $vreme), $potpis)) {
            return ['ispravan' => false, 'greska' => 'Bon nije ispravan (pogrešan potpis).'];
        }
        // Malo tolerancije unapred, ako sat na serveru i telefonu nije isti
        if ($vreme > $sada + 30) {
            return ['ispravan' => false, 'greska' => 'Bon nije ispravan (vreme u budućnosti).'];
        }
        if ($sada - $vreme > self::VAZI_SEKUNDI) {
            return ['ispravan' => false, 'greska' => 'Bon je istekao – student treba da osveži stranicu.'];
        }

        return ['ispravan' => true, 'kartica_id' => $karticaId, 'tip_obroka_id' => $tipObrokaId, 'vreme' => $vreme];
    }

    private static function potpis(int $karticaId, int $tipObrokaId, int $vreme): string
    {
        return substr(hash_hmac('sha256', $karticaId . '|' . $tipObrokaId . '|' . $vreme, self::tajna()), 0, 16);
    }

    private static function tajna(): string
    {
        static $tajna = null;
        if ($tajna === null) {
            $config = include __DIR__ . '/../config.php';
            $tajna = $config['qr_tajna'] ?? 'digitalna-menza-razvoj-2026';
        }
        return $tajna;
    }
}
