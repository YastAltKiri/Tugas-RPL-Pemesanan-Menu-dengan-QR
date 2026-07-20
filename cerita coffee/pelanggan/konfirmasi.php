<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['pelanggan_id']) || !isset($_SESSION['id_meja'])) {
    die("Sesi Anda belum diisi. Silakan scan ulang QR meja untuk memulai.");
}

$db = new Database();
$conn = $db->getConnection();

$stmtMeja = $conn->prepare("SELECT * FROM meja WHERE id = :id");
$stmtMeja->execute(['id' => $_SESSION['id_meja']]);
$meja = $stmtMeja->fetch();

$menuList = $conn->query("SELECT * FROM menu ORDER BY nama_menu ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pilih Menu - Cerita Coffee</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="page" style="max-width: 520px;">
        <div style="text-align:center; margin-bottom: 20px;">
            <h1>Cerita Coffee</h1>
            <p style="color: var(--espresso-light); margin:0;">
                Meja <?= htmlspecialchars($meja['nomor']) ?> · Halo, <?= htmlspecialchars($_SESSION['pelanggan_nama']) ?>
            </p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <p class="msg-error"><?= htmlspecialchars($_GET['error']) ?></p>
        <?php endif; ?>

        <div class="card">
            <h3 style="margin-top:0;">Menu</h3>
            <form action="proses_pesan.php" method="POST">
                <?php foreach ($menuList as $menu): ?>
                <div class="menu-item">
                    <div>
                        <div class="name"><?= htmlspecialchars($menu['nama_menu']) ?></div>
                        <div class="price">Rp <?= number_format($menu['harga'], 0, ',', '.') ?></div>
                    </div>
                    <input type="number" name="qty[<?= $menu['id'] ?>]" min="0" value="0">
                </div>
                <?php endforeach; ?>

                <button type="submit" class="btn btn-primary" style="width:100%; margin-top:20px;">
                    Konfirmasi Pesanan
                </button>
            </form>
        </div>
    </div>
</body>
</html>