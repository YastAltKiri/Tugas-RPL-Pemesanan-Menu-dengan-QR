<?php
require_once __DIR__ . '/../auth_check.php';
cekLogin('ADMIN');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helper_gambar.php';

if (!isset($_GET['id'])) {
    die("ID meja tidak ada.");
}

$db = new Database();
$conn = $db->getConnection();

try {
    $stmt = $conn->prepare("DELETE FROM meja WHERE id = :id");
    $stmt->execute(['id' => $_GET['id']]);
    hapusGambarMeja($_GET['id']);
    $msg = "Meja berhasil dihapus.";
} catch (PDOException $e) {
    $msg = "Gagal hapus: meja ini masih punya riwayat pesanan.";
}

header('Location: meja.php?msg=' . urlencode($msg));
exit;