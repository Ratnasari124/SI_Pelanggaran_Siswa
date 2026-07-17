<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$role = $_SESSION['role'] ?? '';
$page_url = isset($_GET['page']) ? $_GET['page'] : 'beranda';
$menu_aktif = explode('_', $page_url)[0];
?>

<style>
    /* 1. Sidebar lebih ramping */
    .sidebar {
        min-height: 100vh;
        background: #1e293b;
        width: 200px; /* Lebar total dikurangi */
        padding-top: 1rem;
        transition: all 0.3s ease;
    }

    /* 2. Brand area diperkecil */
    .brand-area {
        padding: 0 1rem 1rem 1rem;
        text-align: center;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        margin-bottom: 0.5rem;
    }
    .brand-name {
        font-weight: 600;
        color: #f8fafc;
        font-size: 0.85rem; /* Ukuran teks diperkecil */
        display: block;
    }

    /* 3. Group title lebih padat */
    .menu-group-title {
        font-size: 0.55rem;
        font-weight: 800;
        text-transform: uppercase;
        color: #475569;
        padding: 1rem 1rem 0.25rem 1.25rem;
        letter-spacing: 0.5px;
    }

    /* 4. Nav link lebih compact */
    .sidebar .nav-link { 
        color: #94a3b8; 
        transition: all 0.2s ease; 
        padding: 0.5rem 1rem; /* Padding dikurangi */
        margin: 0.1rem 0.75rem;
        border-radius: 6px;
        font-size: 0.78rem; /* Font diperkecil agar tidak terlihat besar */
        display: flex;
        align-items: center;
    }
    
    .sidebar .nav-link i { 
        width: 16px; /* Ikon diperkecil */
        margin-right: 10px; 
        font-size: 0.85rem; 
    }

    /* Efek hover & aktif */
    .sidebar .nav-link:hover { background: rgba(255,255,255,0.03); color: #ffffff; }
    .sidebar .nav-link.aktif {
        background: #3b82f6; 
        color: #ffffff;
    }
    .sidebar-nav {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 80px); /* 80px adalah estimasi tinggi brand-area */
    }

    /* Ini kunci agar konten dorong logout ke bawah */
    .menu-spacer {
        flex-grow: 1;
    }

    /* Penyesuaian margin agar logout tidak menempel di pojok */
    .logout-box {
        padding-bottom: 20px;
    }
</style>

<div class="brand-area">
    <i class="fas fa-shield-alt text-primary fa-2x mb-2"></i>
    <span class="brand-name">Buku Pelanggaran</span>
</div>

<nav class="nav flex-column sidebar-nav">
    <div>
        <!-- DASHBOARD -->
        <a class="nav-link <?= ($menu_aktif == 'beranda') ? 'aktif' : ''; ?>" href="index.php?page=beranda">
            <i class="fas fa-th-large"></i> Dashboard
        </a>

        <!-- DATA MASTER -->
        <?php if ($role == 'admin'): ?>
            <div class="menu-group-title">Data Master</div>
            <a class="nav-link <?= ($menu_aktif == 'kelas') ? 'aktif' : ''; ?>" href="index.php?page=kelas">
                <i class="fas fa-graduation-cap"></i> Data Kelas
            </a>
            <a class="nav-link <?= ($menu_aktif == 'jenis') ? 'aktif' : ''; ?>" href="index.php?page=jenis">
                <i class="fas fa-stream"></i> Jenis Pelanggaran
            </a>
            <a class="nav-link <?= ($menu_aktif == 'sanksi') ? 'aktif' : ''; ?>" href="index.php?page=sanksi">
                <i class="fas fa-gavel"></i> Sanksi
            </a>
        <?php endif; ?>

        <!-- OPERASIONAL -->
        <div class="menu-group-title">Data Operasional</div>
        <a class="nav-link <?= ($menu_aktif == 'siswa') ? 'aktif' : ''; ?>" href="index.php?page=siswa">
            <i class="fas fa-user-graduate"></i> Data Siswa
        </a>
        <a class="nav-link <?= ($menu_aktif == 'pelanggaran') ? 'aktif' : ''; ?>" href="index.php?page=pelanggaran">
            <i class="fas fa-clipboard-list"></i> Catat Pelanggaran
        </a>

        <!-- SISTEM -->
        <?php if ($role == 'admin'): ?>
            <div class="menu-group-title">Administrasi</div>
            <a class="nav-link <?= ($menu_aktif == 'user') ? 'aktif' : ''; ?>" href="index.php?page=user">
                <i class="fas fa-user-shield"></i> Manajemen User
            </a>
        <?php endif; ?>
    </div>

    <!-- PENGGUSUR (Spacer) agar logout terdorong ke bawah -->
    <div class="menu-spacer"></div>

    <!-- Tombol Logout -->
    <div class="logout-box">
        <a class="nav-link text-danger border-top pt-3" href="logout.php">
            <i class="fas fa-sign-out-alt"></i> Keluar
        </a>
    </div>
</nav>

<script>
    document.querySelectorAll('.sidebar .nav-link').forEach(item => {
        item.addEventListener('click', () => {
            if (window.innerWidth < 768) {
                const sidebar = document.querySelector('#sidebarMenu');
                const bsCollapse = bootstrap.Collapse.getInstance(sidebar) || new bootstrap.Collapse(sidebar);
                bsCollapse.hide();
            }
        });
    });
</script>