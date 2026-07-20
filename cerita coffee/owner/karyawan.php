<?php
require_once __DIR__ . '/../auth_check.php';
cekLogin('ADMIN');
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();
$karyawanList = $conn->query("
    SELECT k.id, k.nama_karyawan, k.no_telp_karyawan, k.no_ktp, u.username
    FROM karyawan k JOIN user u ON k.id_user = u.id
    ORDER BY k.nama_karyawan ASC
")->fetchAll();

$activePage = 'karyawan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akun Karyawan - Cerita Coffee</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/includes/sidebar_header.php'; ?>

    <div class="admin-topbar">
        <h1>Akun Karyawan</h1>
        <div class="admin-topbar-right">
            <button class="btn btn-primary btn-sm" onclick="bukaModalTambah()">+ Tambah Karyawan</button>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <p class="msg-success"><?= htmlspecialchars($_GET['msg']) ?></p>
    <?php endif; ?>

    <div class="card">
        <?php if (empty($karyawanList)): ?>
            <div class="empty-state">Belum ada karyawan.</div>
        <?php else: ?>
        <table>
            <tr><th>Nama</th><th>Username</th><th>No Telp</th><th>No KTP</th><th>Aksi</th></tr>
            <?php foreach ($karyawanList as $k): ?>
            <tr>
                <td data-label="Nama"><?= htmlspecialchars($k['nama_karyawan']) ?></td>
                <td data-label="Username"><?= htmlspecialchars($k['username']) ?></td>
                <td data-label="No Telp"><?= htmlspecialchars($k['no_telp_karyawan']) ?></td>
                <td data-label="No KTP"><?= htmlspecialchars($k['no_ktp']) ?></td>
                <td data-label="Aksi">
                    <button type="button" class="btn btn-ghost btn-sm"
                        onclick="bukaModalEdit('<?= $k['id'] ?>', '<?= htmlspecialchars(addslashes($k['username'])) ?>', '<?= htmlspecialchars(addslashes($k['nama_karyawan'])) ?>', '<?= htmlspecialchars(addslashes($k['no_telp_karyawan'])) ?>', '<?= htmlspecialchars(addslashes($k['no_ktp'])) ?>')">Edit</button>
                    <a href="karyawan_hapus.php?id=<?= $k['id'] ?>" class="btn btn-danger btn-sm"
                       onclick="return confirm('Yakin hapus karyawan ini? Akun login-nya juga akan terhapus.')">Hapus</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>

    <!-- MODAL TAMBAH/EDIT KARYAWAN -->
    <div class="modal-overlay" id="modal-karyawan">
        <div class="modal-box">
            <h3 class="modal-form-title" id="modal-karyawan-title">Tambah Karyawan</h3>
            <form action="karyawan_simpan.php" method="POST">
                <input type="hidden" name="id" id="karyawan-id" value="">

                <label>Nomor KTP</label>
                <input type="text" name="no_ktp" id="karyawan-ktp" maxlength="16" required>

                <label>Nama Lengkap</label>
                <input type="text" name="nama_karyawan" id="karyawan-nama" maxlength="50" required>

                <label>Nomor Telepon</label>
                <input type="text" name="no_telp_karyawan" id="karyawan-telp" maxlength="15" required>

                <label>Username</label>
                <input type="text" name="username" id="karyawan-username" maxlength="20" required>

                <label>Password <span id="karyawan-pw-label" style="font-weight:400;">(wajib untuk karyawan baru)</span></label>
                <input type="password" name="password" id="karyawan-password">

                <div style="display:flex; gap:10px; margin-top:6px;">
                    <button type="button" class="btn btn-ghost" style="flex:1;" onclick="tutupModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex:1;">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function bukaModalTambah() {
            document.getElementById('modal-karyawan-title').textContent = 'Tambah Karyawan';
            document.getElementById('karyawan-id').value = '';
            document.getElementById('karyawan-ktp').value = '';
            document.getElementById('karyawan-nama').value = '';
            document.getElementById('karyawan-telp').value = '';
            document.getElementById('karyawan-username').value = '';
            document.getElementById('karyawan-username').readOnly = false;
            document.getElementById('karyawan-password').required = true;
            document.getElementById('karyawan-pw-label').textContent = '(wajib untuk karyawan baru)';
            document.getElementById('modal-karyawan').classList.add('show');
        }
        function bukaModalEdit(id, username, nama, telp, ktp) {
            document.getElementById('modal-karyawan-title').textContent = 'Edit Karyawan';
            document.getElementById('karyawan-id').value = id;
            document.getElementById('karyawan-username').value = username;
            document.getElementById('karyawan-username').readOnly = true;
            document.getElementById('karyawan-nama').value = nama;
            document.getElementById('karyawan-telp').value = telp;
            document.getElementById('karyawan-ktp').value = ktp;
            document.getElementById('karyawan-password').required = false;
            document.getElementById('karyawan-pw-label').textContent = '(kosongkan jika tidak ingin ganti)';
            document.getElementById('modal-karyawan').classList.add('show');
        }
        function tutupModal() {
            document.getElementById('modal-karyawan').classList.remove('show');
        }
    </script>

    </main>
</div>
</body>
</html>