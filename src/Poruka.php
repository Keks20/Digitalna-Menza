<?php

/**
 * Kratka poruka korisniku koja preživi preusmeravanje (Post/Redirect/Get):
 * stranica je postavi pre header('Location: ...'), a sledeća stranica je prikaže jednom.
 */
class Poruka
{
    private const KLJUC = 'poruka';

    /** $tip: 'success' (zelena) ili 'danger' (crvena) */
    public static function postavi(string $tip, string $tekst): void
    {
        $_SESSION[self::KLJUC] = ['tip' => $tip, 'tekst' => $tekst];
    }

    /** Vraća poruku i briše je iz sesije: ['tip' => ..., 'tekst' => ...] ili null */
    public static function uzmi(): ?array
    {
        $poruka = $_SESSION[self::KLJUC] ?? null;
        unset($_SESSION[self::KLJUC]);
        return $poruka;
    }
}
