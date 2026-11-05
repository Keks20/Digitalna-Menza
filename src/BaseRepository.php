<?php

require_once __DIR__ . '/CrudInterface.php';
require_once __DIR__ . '/Database.php';

/**
 * Apstraktna osnovna klasa za sve repozitorijume.
 *
 * Implementira CrudInterface i nudi zajedničku funkcionalnost
 * (mysqli konekcija, generički read() i delete()) koju sve naslednice dele.
 */
abstract class BaseRepository implements CrudInterface
{
    /** @var mysqli */
    protected $conn;

    /** @var string Naziv tabele u bazi (postavlja ga naslednica) */
    protected $table;

    public function __construct()
    {
        $this->conn = Database::getInstance();
    }

    /**
     * Generičko čitanje jednog reda po ID-u.
     * Naslednica mapira red u svoj objekat preko mapRow().
     */
    public function read(int $id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? $this->mapRow($row) : null;
    }

    /**
     * Generičko brisanje reda po ID-u.
     */
    public function delete(int $id)
    {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    /**
     * Svaka naslednica mora da definiše kako se red iz baze
     * pretvara u odgovarajući model objekat.
     */
    abstract protected function mapRow(array $row);
}
