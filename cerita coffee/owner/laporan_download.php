<?php
require_once __DIR__ . '/../auth_check.php';
cekLogin('ADMIN');
require_once __DIR__ . '/../config/database.php';

$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("
    SELECT b.tgl_bayar, m.nomor AS nomor_meja, pl.nama AS nama_pelanggan,
           b.metode_bayar, b.jumlah_bayar, b.status_bayar, k.nama_karyawan
    FROM bayar b
    JOIN pesan p ON b.id_pesan = p.id
    JOIN meja m ON p.id_meja = m.id
    JOIN pelanggan pl ON p.id_pelanggan = pl.id
    JOIN karyawan k ON b.id_karyawan = k.id
    WHERE DATE(b.tgl_bayar) = :tanggal
    ORDER BY b.tgl_bayar ASC
");
$stmt->execute(['tanggal' => $tanggal]);
$data = $stmt->fetchAll();

$totalBerhasil = 0;
$jumlahBerhasil = 0;
$rekapMetode = ['CASH' => 0, 'QRIS' => 0];
foreach ($data as $row) {
    if ($row['status_bayar'] === 'BERHASIL') {
        $totalBerhasil += $row['jumlah_bayar'];
        $jumlahBerhasil++;
        $rekapMetode[$row['metode_bayar']] += $row['jumlah_bayar'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan <?= htmlspecialchars($tanggal) ?> - Cerita Coffee</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #ddd4c8; }
        .report-wrap { max-width: 720px; margin: 24px auto; padding: 0 16px; }
        .report {
            background: #fff;
            padding: 32px 36px;
            font-family: 'Courier New', monospace;
            color: #222;
            box-shadow: var(--shadow);
        }
        .report h2 { text-align: center; font-family: 'Courier New', monospace; margin: 0 0 2px; letter-spacing: 1px; }
        .report .sub { text-align: center; font-size: 0.85rem; margin-bottom: 4px; }
        .report hr { border: none; border-top: 1px dashed #999; margin: 14px 0; }
        .rp-summary { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 6px; }
        .rp-summary div { flex: 1; min-width: 140px; }
        .rp-summary .label { font-size: 0.72rem; text-transform: uppercase; color: #777; }
        .rp-summary .value { font-size: 1.05rem; font-weight: bold; }
        table.report-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; margin-top: 8px; }
        table.report-table th, table.report-table td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #eee; }
        table.report-table th { border-bottom: 2px solid #333; font-size: 0.72rem; text-transform: uppercase; }
        .rp-total-row td { border-top: 2px solid #333; border-bottom: none; font-weight: bold; padding-top: 10px; }
        .footer-note { text-align: center; font-size: 0.78rem; margin-top: 18px; color: #777; }
        .no-print { max-width: 720px; margin: 0 auto 14px; display: flex; gap: 10px; }
        .no-print .btn { flex: 0 0 auto; }

        @media print {
            body { background: #fff; }
            .no-print { display: none; }
            .report-wrap { margin: 0; padding: 0; max-width: 100%; }
            .report { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="report-wrap">
        <div class="no-print">
            <button class="btn btn-primary" onclick="window.print()">Cetak / Simpan PDF</button>
            <a href="dashboard.php" class="btn btn-ghost">Kembali</a>
        </div>

        <div class="report">
            <h2>CERITA COFFEE</h2>
            <div class="sub">Laporan Penjualan Harian</div>
            <div class="sub"><?= date('d F Y', strtotime($tanggal)) ?></div>
            <hr>

            <div class="rp-summary">
                <div><div class="label">Total Pendapatan</div><div class="value">Rp <?= number_format($totalBerhasil, 0, ',', '.') ?></div></div>
                <div><div class="label">Transaksi Berhasil</div><div class="value"><?= $jumlahBerhasil ?></div></div>
                <div><div class="label">Cash</div><div class="value">Rp <?= number_format($rekapMetode['CASH'], 0, ',', '.') ?></div></div>
                <div><div class="label">QRIS</div><div class="value">Rp <?= number_format($rekapMetode['QRIS'], 0, ',', '.') ?></div></div>
            </div>
            <hr>

            <?php if (empty($data)): ?>
                <p style="text-align:center; color:#999;">Tidak ada transaksi pada tanggal ini.</p>
            <?php else: ?>
            <table class="report-table">
                <tr>
                    <th>Waktu</th><th>Meja</th><th>Pelanggan</th><th>Metode</th><th>Status</th><th>Karyawan</th><th style="text-align:right;">Jumlah</th>
                </tr>
                <?php foreach ($data as $row): ?>
                <tr>
                    <td><?= date('H:i', strtotime($row['tgl_bayar'])) ?></td>
                    <td><?= htmlspecialchars($row['nomor_meja']) ?></td>
                    <td><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
                    <td><?= htmlspecialchars($row['metode_bayar']) ?></td>
                    <td><?= htmlspecialchars($row['status_bayar']) ?></td>
                    <td><?= htmlspecialchars($row['nama_karyawan']) ?></td>
                    <td style="text-align:right;">Rp <?= number_format($row['jumlah_bayar'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="rp-total-row">
                    <td colspan="6">TOTAL PENDAPATAN</td>
                    <td style="text-align:right;">Rp <?= number_format($totalBerhasil, 0, ',', '.') ?></td>
                </tr>
            </table>
            <?php endif; ?>

            <div class="footer-note">Dicetak pada <?= date('d/m/Y H:i') ?> — Cerita Coffee ☕</div>
        </div>
    </div>
</body>
</html>