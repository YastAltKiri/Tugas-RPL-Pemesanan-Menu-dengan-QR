<?php
session_start();
require_once __DIR__ . '/../config/database.php';

function tampilkanError(string $pesan): void {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Cerita Coffee</title>
        <link rel="stylesheet" href="../assets/css/style.css">
    </head>
    <body>
        <div class="page" style="max-width: 420px; padding-top: 100px; text-align:center;">
            <h1>Cerita Coffee</h1>
            <div class="card">
                <p class="msg-error"><?= htmlspecialchars($pesan) ?></p>
                <p style="color: var(--espresso-light); font-size:0.88rem;">
                    Silakan scan ulang QR yang ada di meja Anda.
                </p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if (!isset($_GET['meja']) || trim($_GET['meja']) === '') {
    tampilkanError("QR tidak valid: parameter meja tidak ditemukan di URL.");
}

$db = new Database();
$conn = $db->getConnection();

$stmtMeja = $conn->prepare("SELECT * FROM meja WHERE id = :id");
$stmtMeja->execute(['id' => $_GET['meja']]);
$meja = $stmtMeja->fetch();

if (!$meja) {
    tampilkanError("Meja dengan ID tersebut tidak ditemukan di sistem.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cerita Coffee - Meja <?= htmlspecialchars($meja['nomor']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="page" style="max-width: 420px; padding-top: 60px;">
        <div style="text-align:center; margin-bottom: 24px;">
            <h1>Cerita Coffee</h1>
            <p style="color: var(--espresso-light); margin:0;">Meja Nomor <?= htmlspecialchars($meja['nomor']) ?></p>
        </div>

        <div class="card">
            <h3 style="margin-top:0;">Sebelum mulai pesan</h3>
            <p style="color: var(--espresso-light); font-size: 0.9rem;">Isi nama dan nomor telepon supaya pesanan bisa kami kenali.</p>

            <?php if (isset($_GET['error'])): ?>
                <p class="msg-error"><?= htmlspecialchars($_GET['error']) ?></p>
            <?php endif; ?>

            <form action="proses_identitas.php" method="POST">
                <input type="hidden" name="id_meja" value="<?= htmlspecialchars($meja['id']) ?>">

                <label>Nomor Telepon</label>
                <input type="text" name="no_telp" maxlength="15" placeholder="08xxxxxxxxxx" required>

                <label>Nama Anda</label>
                <input type="text" name="nama" maxlength="50" required>

                <button type="submit" class="btn btn-primary" style="width:100%;">Masuk ke List Menu</button>
            </form>
        </div>
    </div>
</body>
</html>