<?php
require_once __DIR__ . '/../../auth_check.php';
cekLogin('KARYAWAN');
require_once __DIR__ . '/../../config/database.php';

if (!isset($_GET['id'])) {
    die("ID pesan tidak ada.");
}

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT * FROM pesan WHERE id = :id");
$stmt->execute(['id' => $_GET['id']]);
$pesan = $stmt->fetch();

if (!$pesan) {
    die("Pesanan tidak ditemukan.");
}

// Cek kalau sudah pernah dibayar
$cek = $conn->prepare("SELECT id FROM bayar WHERE id_pesan = :id_pesan");
$cek->execute(['id_pesan' => $pesan['id']]);
if ($cek->fetch()) {
    die("Pesanan ini sudah tercatat pembayarannya.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Konfirmasi Pembayaran - Cerita Coffee</title>
</head>
<body>
    <h2>Konfirmasi Pembayaran — Pesan #<?= htmlspecialchars($pesan['id']) ?></h2>
    <a href="detail.php?id=<?= urlencode($pesan['id']) ?>">&larr; Kembali</a>
    <br><br>

    <p><strong>Grand Total:</strong> Rp <?= number_format($pesan['grand_total'], 0, ',', '.') ?></p>

    <form action="proses_bayar.php" method="POST">
        <input type="hidden" name="id_pesan" value="<?= htmlspecialchars($pesan['id']) ?>">
        <input type="hidden" name="grand_total" value="<?= htmlspecialchars($pesan['grand_total']) ?>">

        <label>Metode Bayar:</label><br>
        <select name="metode_bayar" required>
            <option value="CASH">CASH</option>
            <option value="QRIS">QRIS</option>
        </select><br><br>

        <label>Jumlah Dibayar (Rp):</label><br>
        <input type="number" name="jumlah_bayar" step="0.01" min="0"
               value="<?= htmlspecialchars($pesan['grand_total']) ?>" required><br><br>

        <button type="submit">Simpan Pembayaran</button>
    </form>
</body>
</html>