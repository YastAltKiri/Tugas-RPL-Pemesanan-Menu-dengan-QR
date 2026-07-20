<?php
require_once __DIR__ . '/../auth_check.php';
cekLogin('ADMIN');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helper_gambar.php';


$namaMenu = trim($_POST['nama_menu'] ?? '');
$harga = trim($_POST['harga'] ?? '');

if ($namaMenu === '' || $harga === '') {
    die("Nama menu dan harga wajib diisi.");
}

$db = new Database();
$conn = $db->getConnection();

try {
    if (isset($_POST['id']) && $_POST['id'] !== '') {
        $idMenu = $_POST['id'];
        $stmt = $conn->prepare("UPDATE menu SET nama_menu = :nama, harga = :harga WHERE id = :id");
        $stmt->execute(['nama' => $namaMenu, 'harga' => $harga, 'id' => $idMenu]);
        $msg = "Menu berhasil diupdate.";
    } else {
        $stmt = $conn->prepare("INSERT INTO menu (nama_menu, harga) VALUES (:nama, :harga)");
        $stmt->execute(['nama' => $namaMenu, 'harga' => $harga]);
        $idMenu = $conn->lastInsertId();
        $msg = "Menu berhasil ditambahkan.";
    }

    if (isset($_FILES['gambar'])) {
        simpanGambarMenu($_FILES['gambar'], $idMenu);
    }
} catch (Exception $e) {
    die("Gagal: " . $e->getMessage());
}

header('Location: menu.php?msg=' . urlencode($msg));
exit;