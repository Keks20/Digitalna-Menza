-- =========================================================
-- Digitalna menza – struktura baze (menza_db)
-- Uvoz: phpMyAdmin -> Import -> ovaj fajl, pa zatim db/seed.sql
-- PAŽNJA: briše postojeću bazu menza_db i pravi je ponovo.
-- =========================================================

DROP DATABASE IF EXISTS menza_db;
CREATE DATABASE menza_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE menza_db;

-- ---------- Korisnici ----------

-- Kategorija studenta određuje cene i koje obroke sme da kupi:
--   finansiranje: budžet = osnovna cena obroka, samofinansiranje = trostruka cena
--   prebivaliste: ko živi u Kragujevcu (a nije u studentskom domu) kupuje samo ručak
CREATE TABLE studenti (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ime VARCHAR(50) NOT NULL,
  prezime VARCHAR(50) NOT NULL,
  broj_indeksa VARCHAR(20) NOT NULL,
  ustanova VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  telefon VARCHAR(20) NULL,
  slika VARCHAR(100) NULL COMMENT 'naziv fajla profilne slike u uploads/profilne/',
  finansiranje ENUM('budzet','samofinansiranje') NOT NULL DEFAULT 'budzet',
  prebivaliste ENUM('van_kragujevca','kragujevac') NOT NULL DEFAULT 'van_kragujevca',
  obavestenja_procitana DATETIME NULL COMMENT 'do kog trenutka je student video obaveštenja (zvonce)',
  kreirano DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_studenti_indeks (ustanova, broj_indeksa)
) ENGINE=InnoDB;

-- Radnici Studentskog centra: administrator (jelovnik, cene, uplate)
-- i kasir (skenira QR bon na kasi menze)
CREATE TABLE admini (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  korisnicko_ime VARCHAR(50) NOT NULL UNIQUE,
  lozinka_hash VARCHAR(255) NOT NULL,
  ime_prezime VARCHAR(100) NOT NULL,
  uloga ENUM('admin','kasir') NOT NULL DEFAULT 'admin',
  kreirano DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Studentska kartica: njom se student prijavljuje i na njoj je novac.
-- Jedan student ima tačno jednu karticu (1:1).
CREATE TABLE kartice (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL UNIQUE,
  broj_kartice CHAR(12) NOT NULL UNIQUE,
  lozinka_hash VARCHAR(255) NOT NULL,
  stanje DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status ENUM('aktivna','blokirana') NOT NULL DEFAULT 'aktivna',
  kreirano DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_kartice_student FOREIGN KEY (student_id) REFERENCES studenti(id) ON DELETE CASCADE,
  CONSTRAINT chk_kartice_stanje CHECK (stanje >= 0)
) ENGINE=InnoDB;

-- ---------- Menze, obroci i jelovnik ----------

CREATE TABLE menze (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  naziv VARCHAR(60) NOT NULL UNIQUE,
  adresa VARCHAR(120) NOT NULL,
  kapacitet_dnevno INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- Doručak, ručak, večera – sa trenutnom cenom i radnim vremenom
CREATE TABLE tipovi_obroka (
  id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  naziv VARCHAR(20) NOT NULL UNIQUE,
  cena DECIMAL(8,2) NOT NULL,
  vreme_od TIME NOT NULL,
  vreme_do TIME NOT NULL,
  CONSTRAINT chk_tipovi_cena CHECK (cena > 0)
) ENGINE=InnoDB;

-- Jedan red = jedno jelo u ponudi (menza + dan + obrok); posno = 1 za posno jelo
CREATE TABLE jelovnik (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  menza_id INT UNSIGNED NOT NULL,
  datum DATE NOT NULL,
  tip_obroka_id TINYINT UNSIGNED NOT NULL,
  jelo VARCHAR(150) NOT NULL,
  posno TINYINT(1) NOT NULL DEFAULT 0,
  redosled TINYINT UNSIGNED NOT NULL DEFAULT 1,
  objavljen TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_jelovnik_menza FOREIGN KEY (menza_id) REFERENCES menze(id) ON DELETE CASCADE,
  CONSTRAINT fk_jelovnik_tip FOREIGN KEY (tip_obroka_id) REFERENCES tipovi_obroka(id),
  INDEX idx_jelovnik_dan (menza_id, datum)
) ENGINE=InnoDB;

-- ---------- Novac ----------

-- Svaka promena stanja kartice. iznos je + za uplatu, - za obroke, smeštaj i refundaciju uplate.
-- Na stanje utiču samo transakcije sa statusom 'uspesno'.
-- refundirano: kada je uplata vraćena studentu (tada postoji i red tipa 'refundacija' sa istim iznosom u minusu).
CREATE TABLE transakcije (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kartica_id INT UNSIGNED NOT NULL,
  tip ENUM('uplata','obroci','smestaj','refundacija') NOT NULL,
  nacin ENUM('platna_kartica','ips_qr','kartica') NOT NULL,
  iznos DECIMAL(10,2) NOT NULL,
  stanje_posle DECIMAL(10,2) NULL,
  status ENUM('uspesno','ceka_potvrdu','odbijeno') NOT NULL DEFAULT 'uspesno',
  opis VARCHAR(255) NOT NULL,
  refundirano DATETIME NULL,
  kreirano DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_transakcije_kartica FOREIGN KEY (kartica_id) REFERENCES kartice(id),
  INDEX idx_transakcije_kartica (kartica_id, kreirano)
) ENGINE=InnoDB;

-- Kupljeni mesečni paket po tipu obroka (npr. 10 ručkova za novembar).
-- cena_po_obroku pamti cenu u trenutku kupovine.
CREATE TABLE kupljeni_obroci (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kartica_id INT UNSIGNED NOT NULL,
  tip_obroka_id TINYINT UNSIGNED NOT NULL,
  mesec DATE NOT NULL COMMENT 'prvi dan meseca za koji obroci važe',
  kolicina TINYINT UNSIGNED NOT NULL,
  cena_po_obroku DECIMAL(8,2) NOT NULL,
  transakcija_id INT UNSIGNED NULL,
  kreirano DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_kupljeni_kartica FOREIGN KEY (kartica_id) REFERENCES kartice(id),
  CONSTRAINT fk_kupljeni_tip FOREIGN KEY (tip_obroka_id) REFERENCES tipovi_obroka(id),
  CONSTRAINT fk_kupljeni_transakcija FOREIGN KEY (transakcija_id) REFERENCES transakcije(id) ON DELETE SET NULL,
  CONSTRAINT chk_kupljeni_kolicina CHECK (kolicina BETWEEN 1 AND 31),
  INDEX idx_kupljeni_mesec (kartica_id, mesec)
) ENGINE=InnoDB;

-- Jedan red = jedan izdat obrok (kasir skenirao QR bon)
CREATE TABLE iskorisceni_obroci (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kartica_id INT UNSIGNED NOT NULL,
  tip_obroka_id TINYINT UNSIGNED NOT NULL,
  menza_id INT UNSIGNED NOT NULL,
  kasir_id INT UNSIGNED NULL,
  iskorisceno DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_iskorisceni_kartica FOREIGN KEY (kartica_id) REFERENCES kartice(id),
  CONSTRAINT fk_iskorisceni_tip FOREIGN KEY (tip_obroka_id) REFERENCES tipovi_obroka(id),
  CONSTRAINT fk_iskorisceni_menza FOREIGN KEY (menza_id) REFERENCES menze(id),
  CONSTRAINT fk_iskorisceni_kasir FOREIGN KEY (kasir_id) REFERENCES admini(id) ON DELETE SET NULL,
  INDEX idx_iskorisceni_kartica (kartica_id, iskorisceno)
) ENGINE=InnoDB;

-- ---------- Smeštaj ----------

CREATE TABLE sobe (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  dom VARCHAR(50) NOT NULL,
  paviljon VARCHAR(10) NOT NULL,
  broj VARCHAR(10) NOT NULL,
  broj_kreveta TINYINT UNSIGNED NOT NULL DEFAULT 2,
  UNIQUE KEY uq_sobe (dom, paviljon, broj)
) ENGINE=InnoDB;

CREATE TABLE smestaj_ugovori (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  soba_id INT UNSIGNED NOT NULL,
  skolska_godina CHAR(7) NOT NULL COMMENT 'npr. 2026/27',
  mesecna_cena DECIMAL(10,2) NOT NULL,
  vazi_od DATE NOT NULL,
  vazi_do DATE NOT NULL,
  aktivan TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_ugovori_student FOREIGN KEY (student_id) REFERENCES studenti(id) ON DELETE CASCADE,
  CONSTRAINT fk_ugovori_soba FOREIGN KEY (soba_id) REFERENCES sobe(id)
) ENGINE=InnoDB;

-- Mesečna stanarina po ugovoru; rok je 20. u mesecu
CREATE TABLE smestaj_zaduzenja (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ugovor_id INT UNSIGNED NOT NULL,
  mesec DATE NOT NULL COMMENT 'prvi dan meseca',
  iznos DECIMAL(10,2) NOT NULL,
  rok_placanja DATE NOT NULL,
  placeno TINYINT(1) NOT NULL DEFAULT 0,
  datum_placanja DATETIME NULL,
  transakcija_id INT UNSIGNED NULL,
  CONSTRAINT fk_zaduzenja_ugovor FOREIGN KEY (ugovor_id) REFERENCES smestaj_ugovori(id) ON DELETE CASCADE,
  CONSTRAINT fk_zaduzenja_transakcija FOREIGN KEY (transakcija_id) REFERENCES transakcije(id) ON DELETE SET NULL,
  UNIQUE KEY uq_zaduzenja_mesec (ugovor_id, mesec)
) ENGINE=InnoDB;

-- ---------- Obaveštenja ----------

CREATE TABLE obavestenja (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  naslov VARCHAR(150) NOT NULL,
  tekst TEXT NOT NULL,
  ciljna_grupa ENUM('svi','menza','dom') NOT NULL DEFAULT 'svi',
  menza_id INT UNSIGNED NULL,
  dom VARCHAR(50) NULL,
  admin_id INT UNSIGNED NULL,
  kreirano DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_obavestenja_menza FOREIGN KEY (menza_id) REFERENCES menze(id) ON DELETE SET NULL,
  CONSTRAINT fk_obavestenja_admin FOREIGN KEY (admin_id) REFERENCES admini(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- Mini kviz ----------

CREATE TABLE kviz_pitanja (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tema VARCHAR(40) NOT NULL,
  tezina ENUM('lako','srednje','tesko') NOT NULL DEFAULT 'lako',
  pitanje VARCHAR(300) NOT NULL,
  odgovor_a VARCHAR(200) NOT NULL,
  odgovor_b VARCHAR(200) NOT NULL,
  odgovor_c VARCHAR(200) NOT NULL,
  odgovor_d VARCHAR(200) NOT NULL,
  tacan CHAR(1) NOT NULL,
  objasnjenje VARCHAR(400) NOT NULL,
  aktivno TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT chk_kviz_tacan CHECK (tacan IN ('a','b','c','d'))
) ENGINE=InnoDB;

CREATE TABLE kviz_rezultati (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NULL,
  ime VARCHAR(50) NOT NULL DEFAULT 'Gost',
  broj_tacnih TINYINT UNSIGNED NOT NULL,
  ukupno TINYINT UNSIGNED NOT NULL,
  trajanje_sek INT UNSIGNED NOT NULL,
  kreirano DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_kviz_student FOREIGN KEY (student_id) REFERENCES studenti(id) ON DELETE SET NULL
) ENGINE=InnoDB;
