<?php
require_once __DIR__ . '/../auth_check.php';
cekLogin('ADMIN');
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();
$mejaList = $conn->query("SELECT * FROM meja ORDER BY nomor ASC")->fetchAll();

$activePage = 'meja';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Meja - Cerita Coffee</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/includes/sidebar_header.php'; ?>

    <div class="admin-topbar">
        <h1>Kelola Meja</h1>
        <div class="admin-topbar-right">
            <button class="btn btn-primary btn-sm" onclick="bukaModalTambah()">+ Add Table</button>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <p class="msg-success"><?= htmlspecialchars($_GET['msg']) ?></p>
    <?php endif; ?>

    <?php if (empty($mejaList)): ?>
        <div class="card"><div class="empty-state">Belum ada meja.</div></div>
    <?php else: ?>
    <div class="item-grid">
        <?php foreach ($mejaList as $m): ?>
        <?php $gambarPath = "../assets/uploads/meja/{$m['id']}.png"; ?>
        <div class="item-card">
            <?php if (file_exists($gambarPath)): ?>
                <img class="item-card-img" src="<?= $gambarPath ?>?v=<?= filemtime($gambarPath) ?>" alt="QR Meja <?= htmlspecialchars($m['nomor']) ?>" style="object-fit:contain; background:#fff;">
            <?php else: ?>
                <div class="item-card-img-placeholder" style="font-size:1.4rem; font-family:'Fraunces',serif; font-weight:600; color:var(--espresso);">
                    Meja <?= htmlspecialchars($m['nomor']) ?>
                </div>
            <?php endif; ?>
            <div class="item-card-body">
                <div class="item-card-title">Meja <?= htmlspecialchars($m['nomor']) ?></div>
                <div class="item-card-sub">ID: <?= htmlspecialchars($m['id']) ?></div>
                <div class="item-card-actions">
                    <button type="button" class="btn btn-ghost btn-sm"
                        onclick="bukaModalEdit('<?= $m['id'] ?>', '<?= htmlspecialchars($m['nomor']) ?>')">Edit</button>
                    <a href="meja_hapus.php?id=<?= $m['id'] ?>" class="btn btn-danger btn-sm"
                       onclick="return confirm('Yakin hapus meja ini?')">Hapus</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- MODAL TAMBAH/EDIT MEJA -->
    <div class="modal-overlay" id="modal-meja">
        <div class="modal-box">
            <h3 class="modal-form-title" id="modal-meja-title">Tambah Meja</h3>
            <form action="meja_simpan.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="meja-id" value="">
                <label>Nomor Meja</label>
                <input type="number" name="nomor" id="meja-nomor" required>

                <label>Gambar QR <span style="font-weight:400;">(opsional, hasil generate dari web QR generator)</span></label>
                <input type="file" name="gambar" accept="image/*" style="margin-bottom:14px;">

                <div style="display:flex; gap:10px; margin-top:6px;">
                    <button type="button" class="btn btn-ghost" style="flex:1;" onclick="tutupModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex:1;">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function bukaModalTambah() {
            document.getElementById('modal-meja-title').textContent = 'Tambah Meja';
            document.getElementById('meja-id').value = '';
            document.getElementById('meja-nomor').value = '';
            document.getElementById('modal-meja').classList.add('show');
        }
        function bukaModalEdit(id, nomor) {
            document.getElementById('modal-meja-title').textContent = 'Edit Meja';
            document.getElementById('meja-id').value = id;
            document.getElementById('meja-nomor').value = nomor;
            document.getElementById('modal-meja').classList.add('show');
        }
        function tutupModal() {
            document.getElementById('modal-meja').classList.remove('show');
        }
    </script>

    </main>
</div>
</body>
</html>