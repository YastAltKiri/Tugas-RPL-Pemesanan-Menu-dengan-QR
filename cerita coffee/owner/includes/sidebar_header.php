<?php

?>
<div class="admin-layout">
    <aside class="sidebar">
        <div class="sidebar-brand">Cerita Coffee</div>
        <div class="sidebar-sub">Panel Admin</div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
            <a href="menu.php" class="<?= $activePage === 'menu' ? 'active' : '' ?>">Daftar Menu</a>
            <a href="meja.php" class="<?= $activePage === 'meja' ? 'active' : '' ?>">Meja & QR</a>
            <a href="pelanggan.php" class="<?= $activePage === 'pelanggan' ? 'active' : '' ?>">Pelanggan</a>
            <a href="order.php" class="<?= $activePage === 'order' ? 'active' : '' ?>">Order</a>
            <a href="karyawan.php" class="<?= $activePage === 'karyawan' ? 'active' : '' ?>">Akun Karyawan</a>
        </nav>
        <div class="sidebar-footer">
            <div class="name"><?= htmlspecialchars($_SESSION['username']) ?></div>
            <a href="/logout.php">Keluar</a>
        </div>
    </aside>
    <main class="admin-content">