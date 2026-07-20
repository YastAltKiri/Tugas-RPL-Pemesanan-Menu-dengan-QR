<?php
session_start();
require_once __DIR__ . '/config/database.php';

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    header('Location: login.php?error=empty');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT id, username, password, role FROM user WHERE username = :username");
$stmt->execute(['username' => $username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    header('Location: login.php?error=invalid');
    exit;
}

// Login berhasil, simpan data ke session
$_SESSION['user_id']  = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role']     = $user['role'];

// Redirect sesuai role
if ($user['role'] === 'ADMIN') {
    header('Location: owner/dashboard.php');
} elseif ($user['role'] === 'KARYAWAN') {
    header('Location: karyawan/dashboard.php');
} else {
    header('Location: login.php?error=invalid');
}
exit;