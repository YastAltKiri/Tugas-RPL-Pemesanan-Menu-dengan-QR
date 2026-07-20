<?php
require_once __DIR__ . '/../auth_check.php';
cekLogin('ADMIN');
require_once __DIR__ . '/../config/database.php';

$namaKaryawan = trim($_POST['nama_karyawan'] ?? '');
$noTelp = trim($_POST['no_telp_karyawan'] ?? '');
$noKtp = trim($_POST['no_ktp'] ?? '');
$password = $_POST['password'] ?? '';

if ($namaKaryawan === '' || $noTelp === '' || $noKtp === '') {
    die("Semua field wajib diisi.");
}

$db = new Database();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();

    if (isset($_POST['id']) && $_POST['id'] !== '') {
        $stmt = $conn->prepare("
            UPDATE karyawan
            SET nama_karyawan = :nama, no_telp_karyawan = :telp, no_ktp = :ktp
            WHERE id = :id
        ");
        $stmt->execute([
            'nama' => $namaKaryawan, 'telp' => $noTelp, 'ktp' => $noKtp, 'id' => $_POST['id'],
        ]);

        if ($password !== '') {
            $stmtUser = $conn->prepare("
                UPDATE user u JOIN karyawan k ON k.id_user = u.id
                SET u.password = :password WHERE k.id = :id
            ");
            $stmtUser->execute([
                'password' => password_hash($password, PASSWORD_DEFAULT), 'id' => $_POST['id'],
            ]);
        }
        $msg = "Karyawan berhasil diupdate.";
    } else {
        $username = trim($_POST['username'] ?? '');
        if ($username === '' || $password === '') {
            throw new Exception("Username dan password wajib diisi untuk karyawan baru.");
        }

        $cek = $conn->prepare("SELECT id FROM user WHERE username = :username");
        $cek->execute(['username' => $username]);
        if ($cek->fetch()) {
            throw new Exception("Username sudah dipakai, pilih yang lain.");
        }

        $idUser = 'U' . substr(str_pad((string)random_int(0, 999999999), 9, '0', STR_PAD_LEFT), 0, 9);

        $stmtUser = $conn->prepare("
            INSERT INTO user (id, username, password, role) VALUES (:id, :username, :password, 'KARYAWAN')
        ");
        $stmtUser->execute([
            'id' => $idUser, 'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        $stmtKaryawan = $conn->prepare("
            INSERT INTO karyawan (id_user, nama_karyawan, no_telp_karyawan, no_ktp)
            VALUES (:id_user, :nama, :telp, :ktp)
        ");
        $stmtKaryawan->execute([
            'id_user' => $idUser, 'nama' => $namaKaryawan, 'telp' => $noTelp, 'ktp' => $noKtp,
        ]);

        $msg = "Karyawan berhasil ditambahkan.";
    }

    $conn->commit();
    header('Location: karyawan.php?msg=' . urlencode($msg));
    exit;

} catch (Exception $e) {
    $conn->rollBack();
    die("Gagal: " . $e->getMessage());
}