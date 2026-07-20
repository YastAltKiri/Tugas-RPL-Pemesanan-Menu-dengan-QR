<?php
require_once __DIR__ . '/../../auth_check.php';
cekLogin('KARYAWAN');
require_once __DIR__ . '/../../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->query("
    SELECT p.id, p.status_pesan, p.tgl_pesan, p.grand_total,
           m.nomor AS nomor_meja, pl.nama AS nama_pelanggan,
           b.id AS id_bayar
    FROM pesan p
    JOIN meja m ON p.id_meja = m.id
    JOIN pelanggan pl ON p.id_pelanggan = pl.id
    LEFT JOIN bayar b ON b.id_pesan = p.id
    ORDER BY p.tgl_pesan DESC
");
$pesananList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Pesanan - Cerita Coffee</title>
</head>
<body>
    <h2>Daftar Pesanan</h2>
    <a href="/karyawan/dashboard.php">&larr; Kembali ke Dashboard</a>
    <br><br>

    <?php if (isset($_GET['msg'])): ?>
        <p style="color:green;"><?= htmlspecialchars($_GET['msg']) ?></p>
    <?php endif; ?>

    <table border="1" cellpadding="8">
        <tr>
            <th>ID Pesan</th>
            <th>Meja</th>
            <th>Pelanggan</th>
            <th>Tanggal</th>
            <th>Total</th>
            <th>Status Pesanan</th>
            <th>Pembayaran</th>
            <th>Aksi</th>
        </tr>
        <?php foreach ($pesananList as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['id']) ?></td>
            <td><?= htmlspecialchars($p['nomor_meja']) ?></td>
            <td><?= htmlspecialchars($p['nama_pelanggan']) ?></td>
            <td><?= htmlspecialchars($p['tgl_pesan']) ?></td>
            <td>Rp <?= number_format($p['grand_total'], 0, ',', '.') ?></td>
            <td><?= htmlspecialchars($p['status_pesan']) ?></td>
            <td><?= $p['id_bayar'] ? 'Sudah dibayar' : 'Belum dibayar' ?></td>
            <td>
                <a href="detail.php?id=<?= urlencode($p['id']) ?>">Detail</a>
                <?php if (!$p['id_bayar']): ?>
                    | <a href="konfirmasi_bayar.php?id=<?= urlencode($p['id']) ?>">Konfirmasi Bayar</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>