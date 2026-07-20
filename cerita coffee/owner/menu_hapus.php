<?php
require_once __DIR__ . '/../auth_check.php';
cekLogin('ADMIN');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helper_gambar.php';

if (!isset($_GET['id'])) {
    die("ID menu tidak ada.");
}

$db = new Database();
$conn = $db->getConnection();

try {
    $stmt = $conn->prepare("DELETE FROM menu WHERE id = :id");
    $stmt->execute(['id' => $_GET['id']]);
    hapusGambarMenu($_GET['id']);
    $msg = "Menu berhasil dihapus.";
} catch (PDOException $e) {
    $msg = "Gagal hapus: menu ini masih dipakai di riwayat pesanan.";
}

header('Location: menu.php?msg=' . urlencode($msg));
exit;