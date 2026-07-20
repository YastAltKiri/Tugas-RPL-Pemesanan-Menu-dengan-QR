<?php
require_once __DIR__ . '/../auth_check.php';
cekLogin('ADMIN');
require_once __DIR__ . '/../config/database.php';

if (!isset($_GET['id'])) {
    die("ID karyawan tidak ada.");
}

$db = new Database();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare("SELECT id_user FROM karyawan WHERE id = :id");
    $stmt->execute(['id' => $_GET['id']]);
    $row = $stmt->fetch();

    if (!$row) {
        throw new Exception("Karyawan tidak ditemukan.");
    }

    $stmtDelK = $conn->prepare("DELETE FROM karyawan WHERE id = :id");
    $stmtDelK->execute(['id' => $_GET['id']]);

    $stmtDelU = $conn->prepare("DELETE FROM user WHERE id = :id_user");
    $stmtDelU->execute(['id_user' => $row['id_user']]);

    $conn->commit();
    $msg = "Karyawan berhasil dihapus.";
} catch (Exception $e) {
    $conn->rollBack();
    $msg = "Gagal hapus: " . $e->getMessage();
}

header('Location: karyawan.php?msg=' . urlencode($msg));
exit;