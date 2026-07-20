<?php
require_once __DIR__ . '/../auth_check.php';
cekLogin('KARYAWAN');
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Filter dari query string
$filterStatus = $_GET['status'] ?? '';
$filterMeja = $_GET['meja'] ?? '';

// Ambil semua pesanan hari ini
$sql = "
    SELECT p.id, p.status_pesan, p.tgl_pesan, p.grand_total,m.id AS id_meja, m.nomor AS nomor_meja, pl.nama AS nama_pelanggan,b.status_bayar
    FROM pesan p
    JOIN meja m ON p.id_meja = m.id
    JOIN pelanggan pl ON p.id_pelanggan = pl.id
    LEFT JOIN bayar b ON b.id_pesan = p.id
    WHERE DATE(p.tgl_pesan) = CURDATE()
";
$params = [];

if ($filterStatus === 'BELUM_BAYAR') {
    $sql .= " AND b.id IS NULL";
} elseif (in_array($filterStatus, ['DIPROSES', 'SELESAI', 'BATAL'], true)) {
    $sql .= " AND p.status_pesan = :status";
    $params['status'] = $filterStatus;
}

if ($filterMeja !== '') {
    $sql .= " AND m.id = :meja";
    $params['meja'] = $filterMeja;
}

$sql .= " ORDER BY p.tgl_pesan DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$pesananList = $stmt->fetchAll();

// Ambil detail item untuk tiap pesanan
foreach ($pesananList as &$p) {
    $stmtDetail = $conn->prepare("
        SELECT dp.quantity, mn.id AS id_menu, mn.nama_menu
        FROM detail_pesan dp
        JOIN menu mn ON dp.id_menu = mn.id
        WHERE dp.id_pesan = :id_pesan
    ");
    $stmtDetail->execute(['id_pesan' => $p['id']]);
    $p['items'] = $stmtDetail->fetchAll();
}
unset($p);

// Statistik ringkas
$stmtStat = $conn->query("
    SELECT p.status_pesan, b.status_bayar
    FROM pesan p
    LEFT JOIN bayar b ON b.id_pesan = p.id
    WHERE DATE(p.tgl_pesan) = CURDATE()
");
$semuaPesananHariIni = $stmtStat->fetchAll();

$pesananBaru = 0;
$sedangDiproses = 0;
foreach ($semuaPesananHariIni as $s) {
    if ($s['status_bayar'] === null) {
        $pesananBaru++;
    } elseif ($s['status_bayar'] === 'BERHASIL' && $s['status_pesan'] === 'DIPROSES') {
        $sedangDiproses++;
    }
}

$stmtPendapatan = $conn->query("
    SELECT COALESCE(SUM(jumlah_bayar), 0) AS total
    FROM bayar
    WHERE status_bayar = 'BERHASIL' AND DATE(tgl_bayar) = CURDATE()
");
$pendapatanHariIni = $stmtPendapatan->fetch()['total'];

// Daftar meja yang punya pesanan hari ini (buat dropdown filter)
$daftarMeja = $conn->query("
    SELECT DISTINCT m.id, m.nomor
    FROM meja m
    JOIN pesan p ON p.id_meja = m.id
    WHERE DATE(p.tgl_pesan) = CURDATE()
    ORDER BY m.nomor ASC
")->fetchAll();

$badgeClass = ['DIPROSES' => 'badge-diproses', 'SELESAI' => 'badge-selesai', 'BATAL' => 'badge-batal'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Karyawan - Cerita Coffee</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="topbar">
        <span class="brand">Cerita Coffee</span>
        <div style="display:flex; align-items:center; gap:10px;">
            <span class="badge" style="background:#fff; color:var(--espresso);">Karyawan</span>
            <span class="topbar-user"><?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="riwayat.php">Riwayat</a>
            <a href="laporan_download.php">Unduh Laporan</a>
            <a href="/logout.php">Keluar</a>
        </div>
    </div>

    <div class="page">
        <?php if (isset($_GET['msg'])): ?>
            <p class="msg-success"><?= htmlspecialchars($_GET['msg']) ?></p>
        <?php endif; ?>

        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-label">Pesanan Baru</div>
                <div class="stat-value"><?= $pesananBaru ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Sedang Diproses</div>
                <div class="stat-value"><?= $sedangDiproses ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pendapatan Hari Ini</div>
                <div class="stat-value">Rp <?= number_format($pendapatanHariIni, 0, ',', '.') ?></div>
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:14px;">
            <div>
                <h3 style="margin-top:0; margin-bottom:2px;">Pesanan Masuk</h3>
                <p style="color: var(--espresso-light); font-size:0.88rem; margin:0;">Kelola pesanan yang masuk hari ini</p>
            </div>

            <form method="GET" class="filter-bar" style="margin-bottom:0;">
                <select name="status" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="BELUM_BAYAR" <?= $filterStatus === 'BELUM_BAYAR' ? 'selected' : '' ?>>Belum Dibayar</option>
                    <option value="DIPROSES" <?= $filterStatus === 'DIPROSES' ? 'selected' : '' ?>>Diproses</option>
                    <option value="SELESAI" <?= $filterStatus === 'SELESAI' ? 'selected' : '' ?>>Selesai</option>
                    <option value="BATAL" <?= $filterStatus === 'BATAL' ? 'selected' : '' ?>>Batal</option>
                </select>

                <select name="meja" onchange="this.form.submit()">
                    <option value="">Semua Meja</option>
                    <?php foreach ($daftarMeja as $m): ?>
                    <option value="<?= $m['id'] ?>" <?= $filterMeja == $m['id'] ? 'selected' : '' ?>>
                        Meja <?= htmlspecialchars($m['nomor']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <a href="dashboard.php" class="btn btn-ghost btn-sm">Refresh</a>
            </form>
        </div>
        <div style="margin-bottom:18px;"></div>

        <?php if (empty($pesananList)): ?>
            <div class="card"><div class="empty-state">Tidak ada pesanan yang sesuai filter.</div></div>
        <?php else: ?>
        <div class="order-grid">
            <?php foreach ($pesananList as $p): ?>
            <div class="order-card">
                <div class="order-card-header">
                    <div>
                        <div class="order-card-title">Meja <?= htmlspecialchars($p['nomor_meja']) ?></div>
                        <div class="order-card-sub"><?= htmlspecialchars($p['nama_pelanggan']) ?> · <?= date('H:i', strtotime($p['tgl_pesan'])) ?></div>
                    </div>
                    <span class="badge <?= $badgeClass[$p['status_pesan']] ?? '' ?>"><?= htmlspecialchars($p['status_pesan']) ?></span>
                </div>

                <?php foreach ($p['items'] as $item): ?>
                <?php $gambarPath = "../assets/uploads/menu/{$item['id_menu']}.jpg"; ?>
                <div class="order-item-row">
                    <?php if (file_exists($gambarPath)): ?>
                        <img class="order-item-thumb" src="<?= $gambarPath ?>?v=<?= filemtime($gambarPath) ?>" alt="">
                    <?php else: ?>
                        <div class="order-item-thumb"></div>
                    <?php endif; ?>
                    <span class="item-name"><?= htmlspecialchars($item['nama_menu']) ?></span>
                    <span>x<?= htmlspecialchars($item['quantity']) ?></span>
                </div>
                <?php endforeach; ?>

                <div class="order-card-footer">
                    <span class="amount">Rp <?= number_format($p['grand_total'], 0, ',', '.') ?></span>
                    <div class="order-card-actions">
                        <?php if (!$p['status_bayar']): ?>
                            <form action="set_bayar.php" method="POST" style="display:inline;">
                                <input type="hidden" name="id_pesan" value="<?= htmlspecialchars($p['id']) ?>">
                                <input type="hidden" name="hasil" value="BERHASIL">
                                <input type="hidden" name="metode" value="CASH">
                                <button type="submit" class="btn btn-success btn-sm">Cash ✓</button>
                            </form>
                            <form action="set_bayar.php" method="POST" style="display:inline;">
                                <input type="hidden" name="id_pesan" value="<?= htmlspecialchars($p['id']) ?>">
                                <input type="hidden" name="hasil" value="BERHASIL">
                                <input type="hidden" name="metode" value="QRIS">
                                <button type="submit" class="btn btn-success btn-sm">QRIS ✓</button>
                            </form>
                            <form action="set_bayar.php" method="POST" style="display:inline;">
                                <input type="hidden" name="id_pesan" value="<?= htmlspecialchars($p['id']) ?>">
                                <input type="hidden" name="hasil" value="GAGAL">
                                <button type="submit" class="btn btn-danger btn-sm">Gagal</button>
                            </form>
                        <?php elseif ($p['status_bayar'] === 'BERHASIL' && $p['status_pesan'] === 'DIPROSES'): ?>
                            <form action="set_status.php" method="POST" style="display:inline;">
                                <input type="hidden" name="id_pesan" value="<?= htmlspecialchars($p['id']) ?>">
                                <input type="hidden" name="status" value="SELESAI">
                                <button type="submit" class="btn btn-primary btn-sm">Tandai Selesai</button>
                            </form>
                            <a href="struk.php?id=<?= htmlspecialchars($p['id']) ?>" target="_blank" class="btn btn-ghost btn-sm">Struk</a>

                        <?php elseif ($p['status_bayar'] === 'BERHASIL'): ?>
                            <a href="struk.php?id=<?= htmlspecialchars($p['id']) ?>" target="_blank" class="btn btn-ghost btn-sm">Struk</a>

                        <?php else: ?>
                            <span style="color: var(--espresso-light); font-size:0.82rem;">—</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>