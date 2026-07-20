<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['pelanggan_id'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Sesi tidak ditemukan.']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$stmtPesan = $conn->prepare("
    SELECT p.id, p.status_pesan, p.tgl_pesan, p.grand_total, m.nomor AS nomor_meja
    FROM pesan p
    JOIN meja m ON p.id_meja = m.id
    WHERE p.id_pelanggan = :id_pelanggan AND DATE(p.tgl_pesan) = CURDATE()
    ORDER BY p.tgl_pesan DESC
");
$stmtPesan->execute(['id_pelanggan' => $_SESSION['pelanggan_id']]);
$pesananList = $stmtPesan->fetchAll();

$hasil = [];
foreach ($pesananList as $p) {
    $stmtDetail = $conn->prepare("
        SELECT dp.quantity, dp.subtotal, mn.nama_menu
        FROM detail_pesan dp
        JOIN menu mn ON dp.id_menu = mn.id
        WHERE dp.id_pesan = :id_pesan
    ");
    $stmtDetail->execute(['id_pesan' => $p['id']]);
    $p['items'] = $stmtDetail->fetchAll();
    $hasil[] = $p;
}

echo json_encode(['ok' => true, 'pesanan' => $hasil]);