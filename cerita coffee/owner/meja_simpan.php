<?php
require_once __DIR__ . '/../auth_check.php';
cekLogin('ADMIN');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helper_gambar.php';

$nomor = trim($_POST['nomor'] ?? '');
if ($nomor === '') {
    die("Nomor meja wajib diisi.");
}

$db = new Database();
$conn = $db->getConnection();

try {
    if (isset($_POST['id']) && $_POST['id'] !== '') {
        $idMeja = $_POST['id'];
        $stmt = $conn->prepare("UPDATE meja SET nomor = :nomor WHERE id = :id");
        $stmt->execute(['nomor' => $nomor, 'id' => $idMeja]);
        $msg = "Meja berhasil diupdate.";
    } else {
        $stmt = $conn->prepare("INSERT INTO meja (nomor) VALUES (:nomor)");
        $stmt->execute(['nomor' => $nomor]);
        $idMeja = $conn->lastInsertId();
        $msg = "Meja berhasil ditambahkan.";
    }

    if (isset($_FILES['gambar'])) {
        simpanGambarMeja($_FILES['gambar'], $idMeja);
    }
} catch (Exception $e) {
    die("Gagal: " . $e->getMessage());
}

header('Location: meja.php?msg=' . urlencode($msg));
exit;