<?php
require_once __DIR__ . '/../auth_check.php';
cekLogin('KARYAWAN');
require_once __DIR__ . '/../config/database.php';

$idPesan = $_POST['id_pesan'] ?? '';
$hasil = $_POST['hasil'] ?? '';
$metode = $_POST['metode'] ?? 'CASH';

$validHasil = ['BERHASIL', 'GAGAL'];
$validMetode = ['CASH', 'QRIS'];
if ($idPesan === '' || !in_array($hasil, $validHasil, true) || !in_array($metode, $validMetode, true)) {
    die("Data tidak valid.");
}

$db = new Database();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();

    // Pastikan belum ada catatan pembayaran untuk pesan ini
    $cek = $conn->prepare("SELECT id FROM bayar WHERE id_pesan = :id_pesan");
    $cek->execute(['id_pesan' => $idPesan]);
    if ($cek->fetch()) {
        throw new Exception("Pesanan ini sudah punya catatan pembayaran.");
    }

    // Ambil grand_total pesanan
    $stmtPesan = $conn->prepare("SELECT grand_total FROM pesan WHERE id = :id");
    $stmtPesan->execute(['id' => $idPesan]);
    $pesan = $stmtPesan->fetch();
    if (!$pesan) {
        throw new Exception("Pesanan tidak ditemukan.");
    }

    // Ambil id_karyawan dari akun yang sedang login
    $stmtK = $conn->prepare("SELECT id FROM karyawan WHERE id_user = :id_user");
    $stmtK->execute(['id_user' => $_SESSION['user_id']]);
    $karyawan = $stmtK->fetch();
    if (!$karyawan) {
        throw new Exception("Data karyawan untuk akun ini tidak ditemukan.");
    }

    $jumlahBayar = ($hasil === 'BERHASIL') ? $pesan['grand_total'] : 0.00;
    $idBayar = 'B' . substr(str_pad((string)random_int(0, 999999999), 9, '0', STR_PAD_LEFT), 0, 9);

    $stmtBayar = $conn->prepare("
        INSERT INTO bayar (id, id_pesan, id_karyawan, grand_total, tgl_bayar, metode_bayar, jumlah_bayar, status_bayar)
        VALUES (:id, :id_pesan, :id_karyawan, :grand_total, NOW(), :metode_bayar, :jumlah_bayar, :status_bayar)
    ");
    $stmtBayar->execute([
        'id' => $idBayar,
        'id_pesan' => $idPesan,
        'id_karyawan' => $karyawan['id'],
        'grand_total' => $pesan['grand_total'],
        'metode_bayar' => $metode,
        'jumlah_bayar' => $jumlahBayar,
        'status_bayar' => $hasil,
    ]);

    // Kalau pembayaran gagal, otomatis batalkan pesanan
    if ($hasil === 'GAGAL') {
        $stmtBatal = $conn->prepare("UPDATE pesan SET status_pesan = 'BATAL' WHERE id = :id");
        $stmtBatal->execute(['id' => $idPesan]);
    }

    $conn->commit();
    $msg = ($hasil === 'BERHASIL')
        ? "Pembayaran dicatat berhasil."
        : "Pembayaran gagal, pesanan dibatalkan.";

} catch (Exception $e) {
    $conn->rollBack();
    $msg = "Gagal: " . $e->getMessage();
}

header('Location: dashboard.php?msg=' . urlencode($msg));
exit;