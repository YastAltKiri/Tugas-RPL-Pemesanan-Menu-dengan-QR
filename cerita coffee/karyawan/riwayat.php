<?php
require_once __DIR__ . '/../auth_check.php';
cekLogin('KARYAWAN');
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

$stmt = $conn->prepare("
    SELECT b.id, b.id_pesan, b.tgl_bayar, b.metode_bayar, b.jumlah_bayar, b.status_bayar,
           m.nomor AS nomor_meja, pl.nama AS nama_pelanggan, k.nama_karyawan
    FROM bayar b
    JOIN pesan p ON b.id_pesan = p.id
    JOIN meja m ON p.id_meja = m.id
    JOIN pelanggan pl ON p.id_pelanggan = pl.id
    JOIN karyawan k ON b.id_karyawan = k.id
    WHERE DATE(b.tgl_bayar) = :tanggal
    ORDER BY b.tgl_bayar DESC
");
$stmt->execute(['tanggal' => $tanggal]);
$riwayat = $stmt->fetchAll();

$totalBerhasil = 0;
foreach ($riwayat as $r) {
    if ($r['status_bayar'] === 'BERHASIL') {
        $totalBerhasil += $r['jumlah_bayar'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Riwayat Transaksi - Cerita Coffee</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="topbar">
        <span class="brand">Cerita Coffee</span>
        <div>
            <a href="dashboard.php">Dashboard</a>
            &nbsp;·&nbsp;
            <a href="/logout.php">Keluar</a>
        </div>
    </div>

    <div class="page">
        <h2>Riwayat Transaksi</h2>

        <div class="card">
            <form method="GET" style="display:flex; gap:10px; align-items:end; margin-bottom: 0;">
                <div style="flex:1;">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" value="<?= htmlspecialchars($tanggal) ?>" style="margin-bottom:0;">
                </div>
                <button type="submit" class="btn btn-primary" style="height:38px;">Filter</button>
                <a href="laporan_download.php?tanggal=<?= htmlspecialchars($tanggal) ?>" class="btn btn-ghost" style="height:38px; display:flex; align-items:center;">Unduh Laporan</a>
            </form>
        </div>

        <div class="card" style="display:flex; justify-content:space-between; align-items:center;">
            <span style="color: var(--espresso-light);">Total Penjualan Berhasil</span>
            <span style="font-family:'Fraunces',serif; font-size:1.3rem; font-weight:600;">
                Rp <?= number_format($totalBerhasil, 0, ',', '.') ?>
            </span>
        </div>

        <div class="card">
            <?php if (empty($riwayat)): ?>
                <div class="empty-state">Tidak ada transaksi pada tanggal ini.</div>
            <?php else: ?>
            <table>
                <tr>
                    <th>Jam</th><th>Meja</th><th>Pelanggan</th><th>Metode</th><th>Jumlah</th><th>Status</th><th>Diproses Oleh</th><th>Aksi</th>
                </tr>
                <?php foreach ($riwayat as $r): ?>
                <tr>
                    <td data-label="Jam"><?= date('H:i', strtotime($r['tgl_bayar'])) ?></td>
                    <td data-label="Meja"><?= htmlspecialchars($r['nomor_meja']) ?></td>
                    <td data-label="Pelanggan"><?= htmlspecialchars($r['nama_pelanggan']) ?></td>
                    <td data-label="Metode"><?= htmlspecialchars($r['metode_bayar']) ?></td>
                    <td data-label="Jumlah">Rp <?= number_format($r['jumlah_bayar'], 0, ',', '.') ?></td>
                    <td data-label="Status">
                        <span class="badge <?= $r['status_bayar'] === 'BERHASIL' ? 'badge-berhasil' : 'badge-gagal' ?>">
                            <?= htmlspecialchars($r['status_bayar']) ?>
                        </span>
                    </td>
                    <td data-label="Diproses Oleh"><?= htmlspecialchars($r['nama_karyawan']) ?></td>
                    <td data-label="Aksi">
                        <?php if ($r['status_bayar'] === 'BERHASIL'): ?>
                        <a href="struk.php?id=<?= htmlspecialchars($r['id_pesan']) ?>" target="_blank" class="btn btn-ghost btn-sm">Struk</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>