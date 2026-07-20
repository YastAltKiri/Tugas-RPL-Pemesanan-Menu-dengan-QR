<?php
require_once __DIR__ . '/../auth_check.php';
cekLogin('KARYAWAN');
require_once __DIR__ . '/../config/database.php';

$idPesan = $_POST['id_pesan'] ?? '';
$status = $_POST['status'] ?? '';

$validStatus = ['DIPROSES', 'SELESAI'];
if ($idPesan === '' || !in_array($status, $validStatus, true)) {
    die("Data tidak valid.");
}

$db = new Database();
$conn = $db->getConnection();

// Pastikan pesanan ini sudah dibayar BERHASIL sebelum status boleh diubah
$cek = $conn->prepare("SELECT status_bayar FROM bayar WHERE id_pesan = :id_pesan");
$cek->execute(['id_pesan' => $idPesan]);
$bayar = $cek->fetch();

if (!$bayar || $bayar['status_bayar'] !== 'BERHASIL') {
    die("Pesanan ini belum punya pembayaran berhasil, status tidak bisa diubah.");
}

$stmt = $conn->prepare("UPDATE pesan SET status_pesan = :status WHERE id = :id");
$stmt->execute(['status' => $status, 'id' => $idPesan]);

header('Location: dashboard.php?msg=' . urlencode('Status pesanan berhasil diubah.'));
exit;