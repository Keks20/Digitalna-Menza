<?php

/**
 * Zajedničko hvatanje grešaka: korisnik umesto PHP greške (putanje fajlova, upiti, podaci o bazi)
 * vidi stranu views/greska.view.php, a prava greška se upisuje u PHP dnevnik grešaka (error_log).
 *
 * Uključuje se jednom, iz src/BaseAuth.php, pa važi na svakoj stranici.
 * Za razvoj: 'prikazi_greske' => true u config.local.php vraća uobičajen PHP prikaz grešaka.
 */
class Greske
{
    private static $ukljuceno = false;

    /** Da se strana za grešku ne ispiše dva puta (izuzetak, pa kraj skripte) */
    private static $prikazano = false;

    public static function ukljuci(): void
    {
        // Iz komandne linije (php -l, probne skripte) greške se ispisuju kao i obično
        if (self::$ukljuceno || PHP_SAPI === 'cli') {
            return;
        }
        self::$ukljuceno = true;

        $config = include __DIR__ . '/../config.php';
        if (!empty($config['prikazi_greske'])) {
            return;
        }

        ini_set('display_errors', '0');
        ini_set('log_errors', '1');
        set_exception_handler([self::class, 'izuzetak']);
        register_shutdown_function([self::class, 'krajSkripte']);
    }

    /** Izuzetak koji nijedna stranica nije uhvatila (npr. baza ne radi) */
    public static function izuzetak(Throwable $e): void
    {
        error_log('Digitalna menza: ' . get_class($e) . ': ' . $e->getMessage()
            . ' u ' . $e->getFile() . ':' . $e->getLine());
        self::prikazi();
    }

    /** Fatalna greška koja prekida skriptu, a nije izuzetak */
    public static function krajSkripte(): void
    {
        $greska = error_get_last();
        $fatalne = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if ($greska !== null && in_array($greska['type'], $fatalne, true)) {
            self::prikazi();
        }
    }

    private static function prikazi(): void
    {
        if (self::$prikazano) {
            return;
        }
        self::$prikazano = true;

        // Deo stranice koji je već pripremljen za slanje se odbacuje
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
            header('Cache-Control: no-store');
        }

        $kodGreske = 500;
        require __DIR__ . '/../views/greska.view.php';
    }
}
