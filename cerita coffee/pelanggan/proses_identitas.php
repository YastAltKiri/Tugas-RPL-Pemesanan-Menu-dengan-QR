<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$idMeja = $_POST['id_meja'] ?? '';
$nama = trim($_POST['nama'] ?? '');
$noTelp = trim($_POST['no_telp'] ?? '');

if ($idMeja === '' || $nama === '' || $noTelp === '') {
    header('Location: index.php?meja=' . urlencode($idMeja) . '&error=' . urlencode('Data belum lengkap.'));
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$cekPelanggan = $conn->prepare("SELECT id, nama FROM pelanggan WHERE no_telp_pelanggan = :telp");
$cekPelanggan->execute(['telp' => $noTelp]);
$pelanggan = $cekPelanggan->fetch();

if ($pelanggan) {
    $idPelanggan = $pelanggan['id'];
    $namaPelanggan = $pelanggan['nama']; 
} else {
    $idPelanggan = 'PL' . substr(str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT), 0, 8);
    $stmtPl = $conn->prepare("INSERT INTO pelanggan (id, no_telp_pelanggan, nama) VALUES (:id, :telp, :nama)");
    $stmtPl->execute(['id' => $idPelanggan, 'telp' => $noTelp, 'nama' => $nama]);
    $namaPelanggan = $nama;
}


$_SESSION['pelanggan_id'] = $idPelanggan;
$_SESSION['pelanggan_nama'] = $namaPelanggan;
$_SESSION['id_meja'] = $idMeja;

header('Location: menu.php');
exit;