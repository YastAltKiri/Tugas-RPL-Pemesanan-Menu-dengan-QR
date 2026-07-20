<?php
require_once __DIR__ . '/../auth_check.php';
cekLogin('ADMIN');
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();


    $stmt = $conn->query("SELECT id FROM pesan WHERE status_pesan = 'SELESAI'");
    $idPesanList = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($idPesanList)) {
        $conn->commit();
        header('Location: order.php?msg=' . urlencode('Tidak ada pesanan Selesai untuk dihapus.'));
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($idPesanList), '?'));


    $conn->prepare("DELETE FROM bayar WHERE id_pesan IN ($placeholders)")->execute($idPesanList);
    $conn->prepare("DELETE FROM detail_pesan WHERE id_pesan IN ($placeholders)")->execute($idPesanList);
    $conn->prepare("DELETE FROM pesan WHERE id IN ($placeholders)")->execute($idPesanList);

    $conn->commit();
    $msg = count($idPesanList) . " pesanan Selesai berhasil dihapus.";
} catch (Exception $e) {
    $conn->rollBack();
    $msg = "Gagal menghapus: " . $e->getMessage();
}

header('Location: order.php?msg=' . urlencode($msg));
exit;