<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['pelanggan_id']) || !isset($_SESSION['id_meja'])) {
    die("Sesi Anda belum diisi. Silakan scan ulang QR meja untuk memulai.");
}

$idPelanggan = $_SESSION['pelanggan_id'];
$idMeja = $_SESSION['id_meja'];
$qtyList = $_POST['qty'] ?? [];

// Ambil menu yang qty-nya > 0
$itemDipesan = [];
foreach ($qtyList as $idMenu => $qty) {
    $qty = (int)$qty;
    if ($qty > 0) {
        $itemDipesan[$idMenu] = $qty;
    }
}

if (empty($itemDipesan)) {
    header('Location: menu.php?error=' . urlencode('Pilih minimal 1 menu.'));
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();

    // Ambil harga menu yang dipesan
    $placeholders = implode(',', array_fill(0, count($itemDipesan), '?'));
    $stmtMenu = $conn->prepare("SELECT id, harga FROM menu WHERE id IN ($placeholders)");
    $stmtMenu->execute(array_keys($itemDipesan));
    $hargaMenu = [];
    foreach ($stmtMenu->fetchAll() as $m) {
        $hargaMenu[$m['id']] = $m['harga'];
    }

    // Hitung grand total
    $grandTotal = 0;
    foreach ($itemDipesan as $idMenu => $qty) {
        $grandTotal += $hargaMenu[$idMenu] * $qty;
    }

    // Buat pesanan
    $idPesan = 'PS' . substr(str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT), 0, 8);
    $stmtPesan = $conn->prepare("
        INSERT INTO pesan (id, id_meja, id_pelanggan, status_pesan, tgl_pesan, grand_total)
        VALUES (:id, :id_meja, :id_pelanggan, 'DIPROSES', NOW(), :grand_total)
    ");
    $stmtPesan->execute([
        'id' => $idPesan,
        'id_meja' => $idMeja,
        'id_pelanggan' => $idPelanggan,
        'grand_total' => $grandTotal,
    ]);

    // Buat detail pesanan
    $stmtDetail = $conn->prepare("
        INSERT INTO detail_pesan (id, id_pesan, id_menu, quantity, subtotal)
        VALUES (:id, :id_pesan, :id_menu, :quantity, :subtotal)
    ");
    foreach ($itemDipesan as $idMenu => $qty) {
        $idDetail = 'DP' . substr(str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT), 0, 8);
        $stmtDetail->execute([
            'id' => $idDetail,
            'id_pesan' => $idPesan,
            'id_menu' => $idMenu,
            'quantity' => $qty,
            'subtotal' => $hargaMenu[$idMenu] * $qty,
        ]);
    }

    $conn->commit();
    header('Location: konfirmasi.php?id=' . urlencode($idPesan));
    exit;

} catch (Exception $e) {
    $conn->rollBack();
    header('Location: menu.php?error=' . urlencode('Gagal membuat pesanan: ' . $e->getMessage()));
    exit;
}