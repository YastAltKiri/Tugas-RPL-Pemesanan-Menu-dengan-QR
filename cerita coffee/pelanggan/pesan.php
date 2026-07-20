<?php
require_once __DIR__ . '/../config/database.php';

if (!isset($_GET['meja'])) {
    die("QR tidak valid: meja tidak ditemukan.");
}

$db = new Database();
$conn = $db->getConnection();

$stmtMeja = $conn->prepare("SELECT * FROM meja WHERE id = :id");
$stmtMeja->execute(['id' => $_GET['meja']]);
$meja = $stmtMeja->fetch();

if (!$meja) {
    die("Meja tidak ditemukan.");
}

$menuList = $conn->query("SELECT * FROM menu ORDER BY nama_menu ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pesan - Meja <?= htmlspecialchars($meja['nomor']) ?> - Cerita Coffee</title>
</head>
<body>
    <h2>Cerita Coffee — Meja <?= htmlspecialchars($meja['nomor']) ?></h2>
    <p>Silakan pilih menu yang ingin dipesan.</p>

    <?php if (isset($_GET['error'])): ?>
        <p style="color:red;"><?= htmlspecialchars($_GET['error']) ?></p>
    <?php endif; ?>

    <form action="proses_pesan.php" method="POST">
        <input type="hidden" name="id_meja" value="<?= htmlspecialchars($meja['id']) ?>">

        <table border="1" cellpadding="8">
            <tr>
                <th>Menu</th>
                <th>Harga</th>
                <th>Jumlah</th>
            </tr>
            <?php foreach ($menuList as $menu): ?>
            <tr>
                <td><?= htmlspecialchars($menu['nama_menu']) ?></td>
                <td>Rp <?= number_format($menu['harga'], 0, ',', '.') ?></td>
                <td>
                    <input type="number" name="qty[<?= $menu['id'] ?>]" min="0" value="0" style="width:60px;">
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <br>
        <label>Nama Anda:</label><br>
        <input type="text" name="nama" maxlength="50" required><br><br>

        <label>No Telepon (untuk konfirmasi):</label><br>
        <input type="text" name="no_telp" maxlength="15" placeholder="08xxxxxxxxxx" required><br><br>

        <button type="submit">Buat Pesanan</button>
    </form>
</body>
</html>