<?php
require_once __DIR__ . '/../../auth_check.php';
cekLogin('KARYAWAN');
require_once __DIR__ . '/../../config/database.php';

$id = $_POST['id'] ?? '';
$status = $_POST['status_pesan'] ?? '';

$validStatus = ['DIPROSES', 'SELESAI', 'BATAL'];
if ($id === '' || !in_array($status, $validStatus, true)) {
    die("Data tidak valid.");
}

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("UPDATE pesan SET status_pesan = :status WHERE id = :id");
$stmt->execute(['status' => $status, 'id' => $id]);

header('Location: detail.php?id=' . urlencode($id) . '&msg=' . urlencode('Status berhasil diupdate.'));
exit;