<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['pelanggan_id']) || !isset($_SESSION['id_meja'])) {
    die("Sesi Anda belum diisi. Silakan scan ulang QR meja untuk memulai.");
}

$db = new Database();
$conn = $db->getConnection();

$stmtMeja = $conn->prepare("SELECT * FROM meja WHERE id = :id");
$stmtMeja->execute(['id' => $_SESSION['id_meja']]);
$meja = $stmtMeja->fetch();

$menuList = $conn->query("SELECT * FROM menu ORDER BY nama_menu ASC")->fetchAll();

$menuJs = array_map(fn($m) => [
    'id' => $m['id'],
    'nama' => $m['nama_menu'],
    'harga' => (float)$m['harga'],
], $menuList);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pesan - Cerita Coffee</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="page" style="max-width: 480px;">
        <div style="text-align:center; margin-bottom: 18px;">
            <h1>Cerita Coffee</h1>
            <p style="color: var(--espresso-light); margin:0;">
                Meja <?= htmlspecialchars($meja['nomor']) ?> · Halo, <?= htmlspecialchars($_SESSION['pelanggan_nama']) ?>
            </p>
        </div>

        <!-- PILIHAN MENU (selalu terlihat) -->
        <h3 style="margin-top:0;">Pilihan Menu</h3>
        <p style="color: var(--espresso-light); font-size:0.85rem; margin-top:-6px;">Pesan menu yang Anda mau</p>

        <div class="menu-grid">
            <?php foreach ($menuList as $menu): ?>
            <?php $gambarPath = "../assets/uploads/menu/{$menu['id']}.jpg"; ?>
            <div class="menu-card">
                <?php if (file_exists($gambarPath)): ?>
                    <img class="menu-card-img" src="<?= $gambarPath ?>?v=<?= filemtime($gambarPath) ?>" alt="">
                <?php else: ?>
                    <div class="menu-card-img-placeholder">☕</div>
                <?php endif; ?>
                <div class="menu-card-body">
                    <div class="menu-card-name"><?= htmlspecialchars($menu['nama_menu']) ?></div>
                    <div class="menu-card-price">Rp <?= number_format($menu['harga'], 0, ',', '.') ?></div>
                    <div class="qty-stepper">
                        <button type="button" class="qty-btn" onclick="ubahQty('<?= $menu['id'] ?>', -1)">−</button>
                        <span class="qty-value" id="qty-<?= $menu['id'] ?>">0</span>
                        <button type="button" class="qty-btn" onclick="ubahQty('<?= $menu['id'] ?>', 1)">+</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- KERANJANG & RIWAYAT (digabung, dibedakan sub-tab) -->
        <h3>Keranjang & Riwayat</h3>
        <div class="sub-tabs">
            <button class="sub-tab-btn active" data-subtab="pesanan" onclick="showSubTab('pesanan')">Pesanan</button>
            <button class="sub-tab-btn" data-subtab="riwayat" onclick="showSubTab('riwayat')">Riwayat Hari Ini</button>
        </div>

        <div class="card" id="subtab-pesanan">
            <div id="cart-items">
                <div class="empty-state" id="cart-empty">Belum ada menu dipilih.</div>
            </div>
            <div class="total-row">
                <span>Total</span>
                <span>Rp <span id="cart-total-text">0</span></span>
            </div>
            <button class="btn btn-primary" style="width:100%; margin-top:16px;" id="btn-buka-konfirmasi" onclick="bukaModalKonfirmasi()" disabled>
                Kirim Pesanan
            </button>
        </div>

        <div class="card" id="subtab-riwayat" style="display:none;">
            <div id="riwayat-container">
                <div class="empty-state">Memuat riwayat...</div>
            </div>
        </div>
    </div>

    <!-- MODAL KONFIRMASI -->
    <div class="modal-overlay" id="modal-konfirmasi">
        <div class="modal-box">
            <h3 style="margin-top:0;">Konfirmasi Pesanan</h3>
            <p style="color: var(--espresso-light); font-size:0.88rem;">
                Periksa pesanan sebelum dikirim ke dapur. Pembayaran dilakukan langsung ke kasir/karyawan.
            </p>
            <div id="confirm-items"></div>
            <div class="total-row">
                <span>Total</span>
                <span>Rp <span id="confirm-total-text">0</span></span>
            </div>
            <div style="display:flex; gap:10px; margin-top:18px;">
                <button class="btn btn-ghost" style="flex:1;" onclick="tutupModal('modal-konfirmasi')">Batal</button>
                <button class="btn btn-primary" style="flex:1;" id="btn-kirim" onclick="kirimPesanan()">Konfirmasi</button>
            </div>
        </div>
    </div>

    <!-- MODAL SUKSES -->
    <div class="modal-overlay" id="modal-sukses">
        <div class="modal-box" style="text-align:center;">
            <div style="font-size:2.4rem; margin-bottom:6px;">✅</div>
            <h3 style="margin-top:0;">Pesanan Terkirim!</h3>
            <p style="color: var(--espresso-light);">Silakan lakukan pembayaran ke kasir/karyawan kami.</p>
            <button class="btn btn-primary" style="width:100%; margin-top:8px;" onclick="tutupModalSukses()">
                Selesai
            </button>
        </div>
    </div>

    <script>
        const menuData = <?= json_encode($menuJs) ?>;
        const cart = {};

        function formatRupiah(angka) {
            return new Intl.NumberFormat('id-ID').format(angka);
        }

        function ubahQty(id, delta) {
            const current = cart[id] || 0;
            const next = Math.max(0, current + delta);
            cart[id] = next;
            document.getElementById('qty-' + id).textContent = next;
            renderKeranjang();
        }

        function hitungTotal() {
            let total = 0, count = 0;
            for (const id in cart) {
                if (cart[id] > 0) {
                    const menu = menuData.find(m => m.id == id);
                    total += menu.harga * cart[id];
                    count += cart[id];
                }
            }
            return { total, count };
        }

        function renderDaftarItem(containerId) {
            const container = document.getElementById(containerId);
            container.innerHTML = '';
            for (const id in cart) {
                if (cart[id] > 0) {
                    const menu = menuData.find(m => m.id == id);
                    const gambarPath = `../assets/uploads/menu/${id}.jpg`;
                    const subtotal = menu.harga * cart[id];
                    const div = document.createElement('div');
                    div.className = 'cart-item';
                    div.innerHTML = `
                        <div>
                            <div class="name">${menu.nama}</div>
                            <div class="price">${cart[id]} x Rp ${formatRupiah(menu.harga)}</div>
                        </div>
                        <div>Rp ${formatRupiah(subtotal)}</div>
                    `;
                    container.appendChild(div);
                }
            }
        }

        function renderKeranjang() {
            const { total, count } = hitungTotal();
            const btn = document.getElementById('btn-buka-konfirmasi');
            const empty = document.getElementById('cart-empty');

            if (count > 0) {
                renderDaftarItem('cart-items');
                btn.disabled = false;
            } else {
                document.getElementById('cart-items').innerHTML = '<div class="empty-state" id="cart-empty">Belum ada menu dipilih.</div>';
                btn.disabled = true;
            }
            document.getElementById('cart-total-text').textContent = formatRupiah(total);
        }

        function showSubTab(tab) {
            document.querySelectorAll('.sub-tab-btn').forEach(el => el.classList.remove('active'));
            document.querySelector('.sub-tab-btn[data-subtab="' + tab + '"]').classList.add('active');
            document.getElementById('subtab-pesanan').style.display = tab === 'pesanan' ? 'block' : 'none';
            document.getElementById('subtab-riwayat').style.display = tab === 'riwayat' ? 'block' : 'none';
            if (tab === 'riwayat') {
                muatRiwayat();
            }
        }

        function bukaModalKonfirmasi() {
            renderDaftarItem('confirm-items');
            const { total } = hitungTotal();
            document.getElementById('confirm-total-text').textContent = formatRupiah(total);
            document.getElementById('modal-konfirmasi').classList.add('show');
        }

        function tutupModal(id) {
            document.getElementById(id).classList.remove('show');
        }

        function kirimPesanan() {
            const btn = document.getElementById('btn-kirim');
            btn.disabled = true;
            btn.textContent = 'Mengirim...';

            fetch('proses_pesan_ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ qty: cart })
            })
            .then(async res => {
                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error('Server error: ' + text.substring(0, 200));
                }
                return data;
            })
            .then(data => {
                btn.disabled = false;
                btn.textContent = 'Konfirmasi';
                if (data.ok) {
                    for (const id in cart) {
                        cart[id] = 0;
                        const el = document.getElementById('qty-' + id);
                        if (el) el.textContent = '0';
                    }
                    renderKeranjang();
                    tutupModal('modal-konfirmasi');
                    document.getElementById('modal-sukses').classList.add('show');
                } else {
                    alert(data.error || 'Gagal mengirim pesanan.');
                }
            })
            .catch((err) => {
                btn.disabled = false;
                btn.textContent = 'Konfirmasi';
                alert(err.message || 'Terjadi kesalahan. Coba lagi.');
                console.error(err);
            });
        }

        function tutupModalSukses() {
            tutupModal('modal-sukses');
            showSubTab('riwayat');
        }

        const badgeClass = { DIPROSES: 'badge-diproses', SELESAI: 'badge-selesai', BATAL: 'badge-batal' };

        function muatRiwayat() {
            fetch('riwayat_data.php')
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('riwayat-container');
                    if (!data.ok || data.pesanan.length === 0) {
                        container.innerHTML = '<div class="empty-state">Belum ada pesanan hari ini.</div>';
                        return;
                    }
                    container.innerHTML = data.pesanan.map(p => {
                        const jam = new Date(p.tgl_pesan).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                        const items = p.items.map(it =>
                            `<div class="riwayat-line"><span>${it.nama_menu} x${it.quantity}</span><span>Rp ${formatRupiah(it.subtotal)}</span></div>`
                        ).join('');
                        return `
                            <div class="card riwayat-card">
                                <div class="riwayat-head">
                                    <div>
                                        <strong>#${p.id}</strong><br>
                                        <span style="color:var(--espresso-light); font-size:0.82rem;">${jam} · Meja ${p.nomor_meja}</span>
                                    </div>
                                    <span class="badge ${badgeClass[p.status_pesan] || ''}">${p.status_pesan}</span>
                                </div>
                                ${items}
                                <div class="total-row" style="font-size:1rem; margin-top:8px; padding-top:8px;">
                                    <span>Total</span><span>Rp ${formatRupiah(p.grand_total)}</span>
                                </div>
                            </div>
                        `;
                    }).join('');
                });
        }

        setInterval(() => {
            if (document.getElementById('subtab-riwayat').style.display !== 'none') {
                muatRiwayat();
            }
        }, 10000);
    </script>
</body>
</html>