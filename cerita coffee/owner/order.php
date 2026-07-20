<?php
require_once __DIR__ . '/../auth_check.php';
cekLogin('ADMIN');
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$filterStatus = $_GET['status'] ?? '';

$sql = "
    SELECT p.id, p.tgl_pesan, p.status_pesan, p.grand_total,
           m.nomor AS nomor_meja, pl.nama AS nama_pelanggan, b.status_bayar
    FROM pesan p
    JOIN meja m ON p.id_meja = m.id
    JOIN pelanggan pl ON p.id_pelanggan = pl.id
    LEFT JOIN bayar b ON b.id_pesan = p.id
";
$params = [];
if (in_array($filterStatus, ['DIPROSES', 'SELESAI', 'BATAL'], true)) {
    $sql .= " WHERE p.status_pesan = :status";
    $params['status'] = $filterStatus;
}
$sql .= " ORDER BY p.tgl_pesan DESC LIMIT 100";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$orderList = $stmt->fetchAll();

$badgeClass = ['DIPROSES' => 'badge-diproses', 'SELESAI' => 'badge-selesai', 'BATAL' => 'badge-batal'];
$activePage = 'order';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order - Cerita Coffee</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/includes/sidebar_header.php'; ?>

    <div class="admin-topbar">
        <h1>Semua Pesanan</h1>
        <div class="admin-topbar-right">
            <form method="GET" style="display:inline;">
                <select name="status" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="DIPROSES" <?= $filterStatus === 'DIPROSES' ? 'selected' : '' ?>>Diproses</option>
                    <option value="SELESAI" <?= $filterStatus === 'SELESAI' ? 'selected' : '' ?>>Selesai</option>
                    <option value="BATAL" <?= $filterStatus === 'BATAL' ? 'selected' : '' ?>>Batal</option>
                </select>
            </form>
            <a href="order_hapus_selesai.php" class="btn btn-danger btn-sm"
               onclick="return confirm('Yakin hapus SEMUA pesanan berstatus Selesai? Aksi ini tidak bisa dibatalkan.')">Hapus Selesai</a>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <p class="msg-success"><?= htmlspecialchars($_GET['msg']) ?></p>
    <?php endif; ?>

    <div class="card">
        <?php if (empty($orderList)): ?>
            <div class="empty-state">Tidak ada pesanan.</div>
        <?php else: ?>
        <table>
            <tr><th>Waktu</th><th>Meja</th><th>Pelanggan</th><th>Total</th><th>Status Pesanan</th><th>Status Bayar</th></tr>
            <?php foreach ($orderList as $o): ?>
            <tr>
                <td data-label="Waktu"><?= date('d/m H:i', strtotime($o['tgl_pesan'])) ?></td>
                <td data-label="Meja"><?= htmlspecialchars($o['nomor_meja']) ?></td>
                <td data-label="Pelanggan"><?= htmlspecialchars($o['nama_pelanggan']) ?></td>
                <td data-label="Total">Rp <?= number_format($o['grand_total'], 0, ',', '.') ?></td>
                <td data-label="Status Pesanan"><span class="badge <?= $badgeClass[$o['status_pesan']] ?? '' ?>"><?= htmlspecialchars($o['status_pesan']) ?></span></td>
                <td data-label="Status Bayar">
                    <?php if ($o['status_bayar']): ?>
                        <span class="badge <?= $o['status_bayar'] === 'BERHASIL' ? 'badge-berhasil' : 'badge-gagal' ?>"><?= htmlspecialchars($o['status_bayar']) ?></span>
                    <?php else: ?>
                        <span style="color:var(--espresso-light); font-size:0.85rem;">Belum dibayar</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>

    </main>
</div>
</body>
</html>