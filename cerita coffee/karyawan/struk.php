<?php
require_once __DIR__ . '/../auth_check.php';
cekLogin('KARYAWAN');
require_once __DIR__ . '/../config/database.php';

if (!isset($_GET['id'])) {
    die("ID pesan tidak ada.");
}

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("
    SELECT p.id, p.tgl_pesan, p.grand_total, m.nomor AS nomor_meja, pl.nama AS nama_pelanggan,
           b.tgl_bayar, b.metode_bayar, b.jumlah_bayar, b.status_bayar, k.nama_karyawan
    FROM pesan p
    JOIN meja m ON p.id_meja = m.id
    JOIN pelanggan pl ON p.id_pelanggan = pl.id
    JOIN bayar b ON b.id_pesan = p.id
    JOIN karyawan k ON b.id_karyawan = k.id
    WHERE p.id = :id
");
$stmt->execute(['id' => $_GET['id']]);
$pesan = $stmt->fetch();

if (!$pesan) {
    die("Struk tidak ditemukan, atau pesanan ini belum tercatat pembayarannya.");
}

$stmtDetail = $conn->prepare("
    SELECT dp.quantity, dp.subtotal, mn.nama_menu, mn.harga
    FROM detail_pesan dp
    JOIN menu mn ON dp.id_menu = mn.id
    WHERE dp.id_pesan = :id_pesan
");
$stmtDetail->execute(['id_pesan' => $pesan['id']]);
$items = $stmtDetail->fetchAll();

$kembalian = $pesan['jumlah_bayar'] - $pesan['grand_total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk #<?= htmlspecialchars($pesan['id']) ?> - Cerita Coffee</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #ddd4c8; }
        .receipt-wrap { max-width: 340px; margin: 24px auto; padding: 0 16px; }
        .receipt {
            background: #fff;
            padding: 24px 20px;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            color: #222;
            box-shadow: var(--shadow);
        }
        .receipt h2 {
            text-align: center;
            font-family: 'Courier New', monospace;
            font-size: 1.15rem;
            margin: 0 0 2px;
        }
        .receipt .sub { text-align: center; font-size: 0.78rem; margin-bottom: 12px; }
        .receipt hr { border: none; border-top: 1px dashed #999; margin: 10px 0; }
        .r-row { display: flex; justify-content: space-between; margin-bottom: 2px; }
        .r-item-name { flex: 1; }
        .r-total-row { display: flex; justify-content: space-between; font-weight: bold; margin-top: 6px; }
        .receipt .footer-note { text-align: center; font-size: 0.78rem; margin-top: 14px; }
        .no-print { max-width: 340px; margin: 0 auto 14px; display: flex; gap: 10px; }
        .no-print .btn { flex: 1; }

        @media print {
            body { background: #fff; }
            .no-print { display: none; }
            .receipt-wrap { margin: 0; padding: 0; max-width: 100%; }
            .receipt { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="receipt-wrap">
        <div class="no-print">
            <button class="btn btn-primary" onclick="window.print()">Cetak / Simpan PDF</button>
            <a href="dashboard.php" class="btn btn-ghost">Kembali</a>
        </div>

        <div class="receipt">
            <h2>CERITA COFFEE</h2>
            <div class="sub">Struk Transaksi</div>
            <hr>
            <div class="r-row"><span>No. Pesan</span><span><?= htmlspecialchars($pesan['id']) ?></span></div>
            <div class="r-row"><span>Meja</span><span><?= htmlspecialchars($pesan['nomor_meja']) ?></span></div>
            <div class="r-row"><span>Pelanggan</span><span><?= htmlspecialchars($pesan['nama_pelanggan']) ?></span></div>
            <div class="r-row"><span>Tanggal</span><span><?= date('d/m/Y H:i', strtotime($pesan['tgl_bayar'])) ?></span></div>
            <div class="r-row"><span>Kasir</span><span><?= htmlspecialchars($pesan['nama_karyawan']) ?></span></div>
            <hr>
            <?php foreach ($items as $item): ?>
            <div class="r-row">
                <span class="r-item-name"><?= htmlspecialchars($item['nama_menu']) ?></span>
            </div>
            <div class="r-row">
                <span><?= htmlspecialchars($item['quantity']) ?> x <?= number_format($item['harga'], 0, ',', '.') ?></span>
                <span><?= number_format($item['subtotal'], 0, ',', '.') ?></span>
            </div>
            <?php endforeach; ?>
            <hr>
            <div class="r-total-row"><span>TOTAL</span><span>Rp <?= number_format($pesan['grand_total'], 0, ',', '.') ?></span></div>
            <div class="r-row"><span>Bayar (<?= htmlspecialchars($pesan['metode_bayar']) ?>)</span><span>Rp <?= number_format($pesan['jumlah_bayar'], 0, ',', '.') ?></span></div>
            <?php if ($kembalian > 0): ?>
            <div class="r-row"><span>Kembali</span><span>Rp <?= number_format($kembalian, 0, ',', '.') ?></span></div>
            <?php endif; ?>
            <hr>
            <div class="footer-note">
                Terima kasih sudah mampir ke<br>Cerita Coffee 
            </div>
        </div>
    </div>
</body>
</html>