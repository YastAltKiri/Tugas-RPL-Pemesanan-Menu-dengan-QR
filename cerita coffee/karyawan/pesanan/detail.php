<?php
require_once __DIR__ . '/../../auth_check.php';
cekLogin('KARYAWAN');
require_once __DIR__ . '/../../config/database.php';

if (!isset($_GET['id'])) {
    die("ID pesan tidak ada.");
}

$db = new Database();
$conn = $db->getConnection();

// Ambil data pesanan
$stmt = $conn->prepare("
    SELECT p.*, m.nomor AS nomor_meja, pl.nama AS nama_pelanggan
    FROM pesan p
    JOIN meja m ON p.id_meja = m.id
    JOIN pelanggan pl ON p.id_pelanggan = pl.id
    WHERE p.id = :id
");
$stmt->execute(['id' => $_GET['id']]);
$pesan = $stmt->fetch();

if (!$pesan) {
    die("Pesanan tidak ditemukan.");
}

// Ambil detail item
$stmtDetail = $conn->prepare("
    SELECT dp.quantity, dp.subtotal, mn.nama_menu, mn.harga
    FROM detail_pesan dp
    JOIN menu mn ON dp.id_menu = mn.id
    WHERE dp.id_pesan = :id_pesan
");
$stmtDetail->execute(['id_pesan' => $pesan['id']]);
$items = $stmtDetail->fetchAll();

// Ambil data pembayaran (kalau ada)
$stmtBayar = $conn->prepare("SELECT * FROM bayar WHERE id_pesan = :id_pesan");
$stmtBayar->execute(['id_pesan' => $pesan['id']]);
$bayar = $stmtBayar->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Pesanan - Cerita Coffee</title>
</head>
<body>
    <h2>Detail Pesanan #<?= htmlspecialchars($pesan['id']) ?></h2>
    <a href="index.php">&larr; Kembali ke Daftar Pesanan</a>
    <br><br>

    <?php if (isset($_GET['msg'])): ?>
        <p style="color:green;"><?= htmlspecialchars($_GET['msg']) ?></p>
    <?php endif; ?>

    <p><strong>Meja:</strong> <?= htmlspecialchars($pesan['nomor_meja']) ?></p>
    <p><strong>Pelanggan:</strong> <?= htmlspecialchars($pesan['nama_pelanggan']) ?></p>
    <p><strong>Tanggal:</strong> <?= htmlspecialchars($pesan['tgl_pesan']) ?></p>
    <p><strong>Status saat ini:</strong> <?= htmlspecialchars($pesan['status_pesan']) ?></p>

    <h3>Item Pesanan</h3>
    <table border="1" cellpadding="8">
        <tr>
            <th>Menu</th>
            <th>Harga</th>
            <th>Qty</th>
            <th>Subtotal</th>
        </tr>
        <?php foreach ($items as $item): ?>
        <tr>
            <td><?= htmlspecialchars($item['nama_menu']) ?></td>
            <td>Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
            <td><?= htmlspecialchars($item['quantity']) ?></td>
            <td>Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <p><strong>Grand Total: Rp <?= number_format($pesan['grand_total'], 0, ',', '.') ?></strong></p>

    <h3>Status Pembayaran</h3>
    <?php if ($bayar): ?>
        <p>
            Dibayar via <?= htmlspecialchars($bayar['metode_bayar']) ?>
            sebesar Rp <?= number_format($bayar['jumlah_bayar'], 0, ',', '.') ?>
            — status: <?= htmlspecialchars($bayar['status_bayar']) ?>
        </p>
    <?php else: ?>
        <p>Belum ada pembayaran tercatat.
           <a href="konfirmasi_bayar.php?id=<?= urlencode($pesan['id']) ?>">Konfirmasi Pembayaran</a>
        </p>
    <?php endif; ?>

    <h3>Update Status Pesanan</h3>
    <form action="update_status.php" method="POST">
        <input type="hidden" name="id" value="<?= htmlspecialchars($pesan['id']) ?>">
        <select name="status_pesan">
            <option value="DIPROSES" <?= $pesan['status_pesan'] === 'DIPROSES' ? 'selected' : '' ?>>DIPROSES</option>
            <option value="SELESAI" <?= $pesan['status_pesan'] === 'SELESAI' ? 'selected' : '' ?>>SELESAI</option>
            <option value="BATAL" <?= $pesan['status_pesan'] === 'BATAL' ? 'selected' : '' ?>>BATAL</option>
        </select>
        <button type="submit">Update Status</button>
    </form>
</body>
</html>