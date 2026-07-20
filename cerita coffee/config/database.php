<?php
/**
 * File koneksi database - Cerita Coffee
 * Menggunakan PDO (PHP Data Objects) + MySQL
 */

class Database
{
    private string $host = "sql104.infinityfree.com"; // contoh: sql123.infinityfree.com
    private string $dbName = "if0_42347843_XXX"; // contoh: epiz_12345678_db_cerita_coffe
    private string $username = "if0_42347843"; // contoh: epiz_12345678
    private string $password = "KopiCerita123"; // password yang Anda buat di vPanel
    private ?PDO $conn = null;

    public function getConnection(): PDO
    {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbName};charset=utf8mb4";

            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            return $this->conn;
        } catch (PDOException $e) {
            die("Koneksi database gagal: " . $e->getMessage());
        }
    }
}