<?php
require_once __DIR__ . '/../auth_check.php';
cekLogin('ADMIN');
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$pelangganList = $conn->query("
    SELECT pl.id, pl.nama, pl.no_telp_pelanggan,
           COUNT(p.id) AS jumlah_pesanan,
           COALESCE(SUM(CASE WHEN p.status_pesan != 'BATAL' THEN p.grand_total ELSE 0 END), 0) AS total_belanja
    FROM pelanggan pl
    LEFT JOIN pesan p ON p.id_pelanggan = pl.id
    GROUP BY pl.id
    ORDER BY pl.nama ASC
")->fetchAll();

$activePage = 'pelanggan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pelanggan - Cerita Coffee</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/includes/sidebar_header.php'; ?>

    <div class="admin-topbar">
        <h1>Pelanggan Terdaftar</h1>
    </div>

    <div class="card">
        <?php if (empty($pelangganList)): ?>
            <div class="empty-state">Belum ada pelanggan terdaftar.</div>
        <?php else: ?>
        <table>
            <tr><th>Nama</th><th>No Telepon</th><th>Jumlah Pesanan</th><th>Total Belanja</th></tr>
            <?php foreach ($pelangganList as $p): ?>
            <tr>
                <td data-label="Nama"><?= htmlspecialchars($p['nama']) ?></td>
                <td data-label="No Telepon"><?= htmlspecialchars($p['no_telp_pelanggan']) ?></td>
                <td data-label="Jumlah Pesanan"><?= htmlspecialchars($p['jumlah_pesanan']) ?></td>
                <td data-label="Total Belanja">Rp <?= number_format($p['total_belanja'], 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>

    </main>
</div>
</body>
</html>