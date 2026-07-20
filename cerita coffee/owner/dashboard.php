<?php
require_once __DIR__ . '/../auth_check.php';
cekLogin('ADMIN');
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$menuAktif = $conn->query("SELECT COUNT(*) AS c FROM menu")->fetch()['c'];

$pesananHariIni = $conn->query("SELECT COUNT(*) AS c FROM pesan WHERE DATE(tgl_pesan) = CURDATE()")->fetch()['c'];

$pesananBaru = $conn->query("
    SELECT COUNT(*) AS c FROM pesan p
    LEFT JOIN bayar b ON b.id_pesan = p.id
    WHERE DATE(p.tgl_pesan) = CURDATE() AND b.id IS NULL
")->fetch()['c'];

$pendapatanHariIni = $conn->query("
    SELECT COALESCE(SUM(jumlah_bayar), 0) AS total FROM bayar
    WHERE status_bayar = 'BERHASIL' AND DATE(tgl_bayar) = CURDATE()
")->fetch()['total'];

$pesananTerbaru = $conn->query("
    SELECT p.id, m.nomor AS nomor_meja, pl.nama AS nama_pelanggan, p.status_pesan, p.grand_total
    FROM pesan p
    JOIN meja m ON p.id_meja = m.id
    JOIN pelanggan pl ON p.id_pelanggan = pl.id
    ORDER BY p.tgl_pesan DESC
    LIMIT 6
")->fetchAll();

$menuTerlaris = $conn->query("
    SELECT mn.nama_menu, SUM(dp.quantity) AS total_qty
    FROM detail_pesan dp
    JOIN menu mn ON dp.id_menu = mn.id
    JOIN pesan p ON dp.id_pesan = p.id
    WHERE DATE(p.tgl_pesan) = CURDATE()
    GROUP BY dp.id_menu
    ORDER BY total_qty DESC
    LIMIT 6
")->fetchAll();

$badgeClass = ['DIPROSES' => 'badge-diproses', 'SELESAI' => 'badge-selesai', 'BATAL' => 'badge-batal'];
$activePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Cerita Coffee</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/includes/sidebar_header.php'; ?>

    <div class="admin-topbar">
        <h1>Dashboard</h1>
        <div class="admin-topbar-right">
            <span style="color: var(--espresso-light); font-size:0.85rem;">
                <?= date('d M Y', strtotime('now')) ?>
            </span>
            <span style="color: var(--espresso-light); font-size:0.85rem;">
                <?= htmlspecialchars($_SESSION['username']) ?>
            </span>
            <a href="/logout.php" class="btn btn-ghost btn-sm">Keluar</a>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <p class="msg-success"><?= htmlspecialchars($_GET['msg']) ?></p>
    <?php endif; ?>

    <div class="admin-stat-grid">
        <div class="stat-card">
            <div class="stat-label">Menu Aktif</div>
            <div class="stat-value"><?= $menuAktif ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Pesanan Baru</div>
            <div class="stat-value"><?= $pesananBaru ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Pesanan Hari Ini</div>
            <div class="stat-value"><?= $pesananHariIni ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Pendapatan Hari Ini</div>
            <div class="stat-value">Rp <?= number_format($pendapatanHariIni, 0, ',', '.') ?></div>
        </div>
    </div>

    <div class="dash-panel-grid">
        <div class="card">
            <h3 style="margin-top:0;">Pesanan Terbaru</h3>
            <?php if (empty($pesananTerbaru)): ?>
                <div class="empty-state">Belum ada pesanan.</div>
            <?php else: ?>
            <table>
                <tr><th>Meja</th><th>Pelanggan</th><th>Total</th><th>Status</th></tr>
                <?php foreach ($pesananTerbaru as $p): ?>
                <tr>
                    <td data-label="Meja"><?= htmlspecialchars($p['nomor_meja']) ?></td>
                    <td data-label="Pelanggan"><?= htmlspecialchars($p['nama_pelanggan']) ?></td>
                    <td data-label="Total">Rp <?= number_format($p['grand_total'], 0, ',', '.') ?></td>
                    <td data-label="Status"><span class="badge <?= $badgeClass[$p['status_pesan']] ?? '' ?>"><?= htmlspecialchars($p['status_pesan']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3 style="margin-top:0;">Menu Terlaris Hari Ini</h3>
            <?php if (empty($menuTerlaris)): ?>
                <div class="empty-state">Belum ada data penjualan hari ini.</div>
            <?php else: ?>
            <table>
                <tr><th>Menu</th><th>Terjual</th></tr>
                <?php foreach ($menuTerlaris as $m): ?>
                <tr>
                    <td data-label="Menu"><?= htmlspecialchars($m['nama_menu']) ?></td>
                    <td data-label="Terjual"><?= htmlspecialchars($m['total_qty']) ?> porsi</td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <div style="text-align:right; margin-top:20px;">
        <a href="laporan_download.php" class="btn btn-primary btn-sm">Unduh Laporan</a>
    </div>

    </main>
</div>
</body>
</html> 