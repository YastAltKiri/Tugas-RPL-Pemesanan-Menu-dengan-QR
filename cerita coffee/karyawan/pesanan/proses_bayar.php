<?php
require_once __DIR__ . '/../../auth_check.php';
cekLogin('KARYAWAN');
require_once __DIR__ . '/../../config/database.php';

$idPesan = $_POST['id_pesan'] ?? '';
$grandTotal = $_POST['grand_total'] ?? '';
$metodeBayar = $_POST['metode_bayar'] ?? '';
$jumlahBayar = $_POST['jumlah_bayar'] ?? '';

$validMetode = ['CASH', 'QRIS'];
if ($idPesan === '' || !in_array($metodeBayar, $validMetode, true) || $jumlahBayar === '') {
    die("Data pembayaran tidak valid.");
}

$db = new Database();
$conn = $db->getConnection();

// Ambil id_karyawan dari session yang sedang login
$stmtK = $conn->prepare("SELECT id FROM karyawan WHERE id_user = :id_user");
$stmtK->execute(['id_user' => $_SESSION['user_id']]);
$karyawan = $stmtK->fetch();

if (!$karyawan) {
    die("Data karyawan untuk akun ini tidak ditemukan.");
}

// Tentukan status pembayaran: BERHASIL kalau jumlah bayar >= grand total
$statusBayar = ((float)$jumlahBayar >= (float)$grandTotal) ? 'BERHASIL' : 'GAGAL';

// Generate ID bayar unik
$idBayar = 'B' . substr(str_pad((string)random_int(0, 999999999), 9, '0', STR_PAD_LEFT), 0, 9);

$stmt = $conn->prepare("
    INSERT INTO bayar (id, id_pesan, id_karyawan, grand_total, tgl_bayar, metode_bayar, jumlah_bayar, status_bayar)
    VALUES (:id, :id_pesan, :id_karyawan, :grand_total, NOW(), :metode_bayar, :jumlah_bayar, :status_bayar)
");
$stmt->execute([
    'id' => $idBayar,
    'id_pesan' => $idPesan,
    'id_karyawan' => $karyawan['id'],
    'grand_total' => $grandTotal,
    'metode_bayar' => $metodeBayar,
    'jumlah_bayar' => $jumlahBayar,
    'status_bayar' => $statusBayar,
]);

header('Location: detail.php?id=' . urlencode($idPesan) . '&msg=' . urlencode('Pembayaran berhasil dicatat.'));
exit;