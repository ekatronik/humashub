<?php
// admin/common/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';

// Tentukan prefix path berdasarkan lokasi file yang memanggil sidebar
// Jika file berada di subfolder (misal: modul-kliping/...), maka prefix adalah '../'
// Jika file berada di root admin, maka prefix kosong ''
$prefix = (basename(dirname($_SERVER['PHP_SELF'])) == 'admin') ? '' : '../';

// Retrieve settings
$app_name = get_setting('app_name', 'Humas Hub');
$primary_color = get_setting('theme_primary_color', '#0984e3');
?>
<style>
    :root {
        --primary: <?php echo htmlspecialchars($primary_color); ?>;
    }
</style>
<!-- Mobile Header -->
<div class="mobile-header">
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <div style="font-weight: 800; font-size: 14px; letter-spacing: 1px;"><?php echo htmlspecialchars(strtoupper($app_name)); ?></div>
</div>

<div class="sidebar" id="sidebar">
    <div class="brand">
        <i class="fas fa-hubspot" style="color: var(--secondary);"></i>
        <span><?php echo htmlspecialchars(strtoupper($app_name)); ?></span>
        <button class="menu-toggle" onclick="toggleSidebar()" style="margin-left: auto; display: none;" id="close-sidebar">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <a href="<?php echo $prefix; ?>index.php" class="nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
        <i class="fas fa-th-large"></i>
        <span>Dashboard Utama</span>
    </a>

    <!-- Modul Kliping -->
    <?php if (in_array($role, ['Super Admin', 'Pranata Humas', 'Operator Kliping'])): ?>
    <div class="sidebar-label">MODUL KLIPING</div>
    <div class="nav-group">
        <a href="javascript:void(0)" class="nav-item <?php echo (strpos($current_page, 'kliping') !== false) ? 'active' : ''; ?>" onclick="toggleSubmenu('kliping-sub')">
            <i class="fas fa-newspaper"></i>
            <span>Arsip Kliping</span>
            <i class="fas fa-chevron-down" style="margin-left: auto; font-size: 12px;"></i>
        </a>
        <div id="kliping-sub" class="submenu" style="<?php echo (strpos($current_page, 'kliping') !== false) ? 'display:block;' : 'display:none;'; ?>">
            <a href="<?php echo $prefix; ?>modul-kliping/kliping.php" class="<?php echo $current_page == 'kliping.php' ? 'active' : ''; ?>">Dashboard</a>
            <a href="<?php echo $prefix; ?>modul-kliping/kliping_daftar.php" class="<?php echo $current_page == 'kliping_daftar.php' ? 'active' : ''; ?>">Daftar Kliping</a>
            <a href="<?php echo $prefix; ?>modul-kliping/kliping_laporan.php" class="<?php echo $current_page == 'kliping_laporan.php' ? 'active' : ''; ?>">Laporan</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modul Berita Online -->
    <?php if (in_array($role, ['Super Admin', 'Pranata Humas', 'Operator Berita Online'])): ?>
    <div class="sidebar-label">MODUL ONLINE</div>
    <div class="nav-group">
        <a href="javascript:void(0)" class="nav-item <?php echo (strpos($current_page, 'news') !== false) ? 'active' : ''; ?>" onclick="toggleSubmenu('news-sub')">
            <i class="fas fa-link"></i>
            <span>Berita Online</span>
            <i class="fas fa-chevron-down" style="margin-left: auto; font-size: 12px;"></i>
        </a>
        <div id="news-sub" class="submenu" style="<?php echo (strpos($current_page, 'news') !== false) ? 'display:block;' : 'display:none;'; ?>">
            <a href="<?php echo $prefix; ?>modul-newsonline/news.php" class="<?php echo $current_page == 'news.php' ? 'active' : ''; ?>">Dashboard</a>
            <a href="<?php echo $prefix; ?>modul-newsonline/news_daftar.php" class="<?php echo $current_page == 'news_daftar.php' ? 'active' : ''; ?>">Daftar Berita</a>
            <a href="<?php echo $prefix; ?>modul-newsonline/news_tambah.php" class="<?php echo $current_page == 'news_tambah.php' ? 'active' : ''; ?>">Tambah Berita</a>
            <a href="<?php echo $prefix; ?>modul-newsonline/news_laporan.php" class="<?php echo $current_page == 'news_laporan.php' ? 'active' : ''; ?>">Laporan</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modul Foto & Video -->
    <?php if (in_array($role, ['Super Admin', 'Pranata Humas', 'Operator Foto/Video'])): ?>
    <div class="sidebar-label">MODUL MEDIA</div>
    <div class="nav-group">
        <a href="javascript:void(0)" class="nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], 'modul-fotovideo') !== false) ? 'active' : ''; ?>" onclick="toggleSubmenu('foto-sub')">
            <i class="fas fa-camera"></i>
            <span>Foto & Video</span>
            <i class="fas fa-chevron-down" style="margin-left: auto; font-size: 12px;"></i>
        </a>
        <div id="foto-sub" class="submenu" style="<?php echo (strpos($_SERVER['REQUEST_URI'], 'modul-fotovideo') !== false) ? 'display:block;' : 'display:none;'; ?>">
            <a href="<?php echo $prefix; ?>modul-fotovideo/foto_video.php" class="<?php echo $current_page == 'foto_video.php' ? 'active' : ''; ?>">Dashboard</a>
            <a href="<?php echo $prefix; ?>modul-fotovideo/daftar.php" class="<?php echo $current_page == 'daftar.php' ? 'active' : ''; ?>">Daftar Dokumentasi</a>
            <a href="<?php echo $prefix; ?>modul-fotovideo/laporan.php" class="<?php echo $current_page == 'laporan.php' ? 'active' : ''; ?>">Laporan</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Pengaturan Taksonomi -->
    <?php if (in_array($role, ['Super Admin', 'Pranata Humas'])): ?>
    <div class="sidebar-label">PENGATURAN</div>
    <div class="nav-group">
        <a href="javascript:void(0)" class="nav-item <?php echo ($current_page == 'kategori.php' || $current_page == 'media.php') ? 'active' : ''; ?>" onclick="toggleSubmenu('sett-sub')">
            <i class="fas fa-tags"></i>
            <span>Taksonomi</span>
            <i class="fas fa-chevron-down" style="margin-left: auto; font-size: 12px;"></i>
        </a>
        <div id="sett-sub" class="submenu" style="<?php echo ($current_page == 'kategori.php' || $current_page == 'media.php') ? 'display:block;' : 'display:none;'; ?>">
            <a href="<?php echo $prefix; ?>modul-setelan/kategori.php" class="<?php echo $current_page == 'kategori.php' ? 'active' : ''; ?>">Kelola Kategori</a>
            <a href="<?php echo $prefix; ?>modul-setelan/media.php" class="<?php echo $current_page == 'media.php' ? 'active' : ''; ?>">Nama Media</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Pengaturan & Log (Pimpinan Only) -->
    <?php if (in_array($role, ['Super Admin', 'Pranata Humas'])): ?>
    <div class="sidebar-label">ADMINISTRASI</div>
    <a href="<?php echo $prefix; ?>modul-admin/users.php" class="nav-item <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
        <i class="fas fa-user-shield"></i>
        <span>Manajemen User</span>
    </a>
    <a href="<?php echo $prefix; ?>modul-admin/logs.php" class="nav-item <?php echo $current_page == 'logs.php' ? 'active' : ''; ?>">
        <i class="fas fa-history"></i>
        <span>Log Aktivitas</span>
    </a>
    <?php endif; ?>

    <?php if ($role == 'Super Admin'): ?>
    <a href="<?php echo $prefix; ?>modul-setelan/settings.php" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
        <i class="fas fa-cog"></i>
        <span>Setelan Sistem</span>
    </a>
    <?php endif; ?>

    <div style="margin-top: auto; padding-top: 20px;">
        <a href="<?php echo $prefix; ?>logout.php" class="nav-item" style="color: #ff4d4d; background: rgba(255,77,77,0.1);">
            <i class="fas fa-sign-out-alt"></i>
            <span>Keluar</span>
        </a>
    </div>
</div>

<script>
function toggleSubmenu(id) {
    const sub = document.getElementById(id);
    sub.style.display = sub.style.display === 'none' ? 'block' : 'none';
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('active');
}
</script>

<style>
@media (max-width: 992px) {
    #close-sidebar { display: block !important; }
}
</style>
