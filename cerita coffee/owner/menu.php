<?php
require_once __DIR__ . '/../auth_check.php';
cekLogin('ADMIN');
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$keyword = trim($_GET['q'] ?? '');
if ($keyword !== '') {
    $stmt = $conn->prepare("SELECT * FROM menu WHERE nama_menu LIKE :kw ORDER BY nama_menu ASC");
    $stmt->execute(['kw' => '%' . $keyword . '%']);
    $menuList = $stmt->fetchAll();
} else {
    $menuList = $conn->query("SELECT * FROM menu ORDER BY nama_menu ASC")->fetchAll();
}

$activePage = 'menu';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Menu - Cerita Coffee</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/includes/sidebar_header.php'; ?>

    <div class="admin-topbar">
        <h1>Daftar Menu</h1>
        <div class="admin-topbar-right">
            <button class="btn btn-primary btn-sm" onclick="bukaModalTambah()">+ Add Item</button>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <p class="msg-success"><?= htmlspecialchars($_GET['msg']) ?></p>
    <?php endif; ?>

    <form method="GET" style="margin-bottom:18px;">
        <input type="text" name="q" class="search-input" placeholder="Cari menu..." value="<?= htmlspecialchars($keyword) ?>">
    </form>

    <?php if (empty($menuList)): ?>
        <div class="card"><div class="empty-state">Belum ada menu.</div></div>
    <?php else: ?>
    <div class="item-grid">
        <?php foreach ($menuList as $mn): ?>
        <?php $gambarPath = "../assets/uploads/menu/{$mn['id']}.jpg"; ?>
        <div class="item-card">
            <?php if (file_exists($gambarPath)): ?>
                <img class="item-card-img" src="<?= $gambarPath ?>?v=<?= filemtime($gambarPath) ?>" alt="">
            <?php else: ?>
                <div class="item-card-img-placeholder">☕</div>
            <?php endif; ?>
            <div class="item-card-body">
                <div class="item-card-title"><?= htmlspecialchars($mn['nama_menu']) ?></div>
                <div class="item-card-sub">Rp <?= number_format($mn['harga'], 0, ',', '.') ?></div>
                <div class="item-card-actions">
                    <button type="button" class="btn btn-ghost btn-sm"
                        onclick="bukaModalEdit('<?= $mn['id'] ?>', '<?= htmlspecialchars(addslashes($mn['nama_menu'])) ?>', '<?= $mn['harga'] ?>')">Edit</button>
                    <a href="menu_hapus.php?id=<?= $mn['id'] ?>" class="btn btn-danger btn-sm"
                       onclick="return confirm('Yakin hapus menu ini?')">Hapus</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- MODAL TAMBAH/EDIT MENU -->
    <div class="modal-overlay" id="modal-menu">
        <div class="modal-box">
            <h3 class="modal-form-title" id="modal-menu-title">Tambah Menu</h3>
            <form action="menu_simpan.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="menu-id" value="">

                <label>Nama Menu</label>
                <input type="text" name="nama_menu" id="menu-nama" maxlength="50" required>

                <label>Harga (Rp)</label>
                <input type="number" name="harga" id="menu-harga" step="0.01" min="0" required>

                <label>Gambar <span style="font-weight:400;">(opsional, JPG/PNG/WEBP maks 2MB)</span></label>
                <input type="file" name="gambar" accept="image/*" style="margin-bottom:14px;">

                <div style="display:flex; gap:10px; margin-top:6px;">
                    <button type="button" class="btn btn-ghost" style="flex:1;" onclick="tutupModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex:1;">Simpan Menu</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function bukaModalTambah() {
            document.getElementById('modal-menu-title').textContent = 'Tambah Menu';
            document.getElementById('menu-id').value = '';
            document.getElementById('menu-nama').value = '';
            document.getElementById('menu-harga').value = '';
            document.getElementById('modal-menu').classList.add('show');
        }
        function bukaModalEdit(id, nama, harga) {
            document.getElementById('modal-menu-title').textContent = 'Edit Menu';
            document.getElementById('menu-id').value = id;
            document.getElementById('menu-nama').value = nama;
            document.getElementById('menu-harga').value = harga;
            document.getElementById('modal-menu').classList.add('show');
        }
        function tutupModal() {
            document.getElementById('modal-menu').classList.remove('show');
        }
    </script>

    </main>
</div>
</body>
</html>