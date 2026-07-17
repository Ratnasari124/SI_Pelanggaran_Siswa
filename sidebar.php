<?php
$page_url = isset($_GET['page']) ? $_GET['page'] : 'beranda';
$menu_aktif = explode('_', $page_url)[0];
?>

<style>
    /* Styling Dasar Sidebar */
    .sidebar {
        min-height: 100vh;
        background-color: #2c3e50;
        position: sticky;
        top: 0;
        padding-top: 20px;
    }

    /* Di layar HP (Mobile), kita buat sidebar overlay (menumpuk di atas konten) */
    @media (max-width: 767.98px) {
        .sidebar {
            position: fixed;
            width: 250px;
            z-index: 1000;
            box-shadow: 2px 0 5px rgba(0,0,0,0.2);
        }
    }
</style>

<!-- Tambahkan script kecil agar sidebar tertutup otomatis saat menu diklik (di HP) -->
<script>
    document.querySelectorAll('.sidebar .nav-link').forEach(item => {
        item.addEventListener('click', () => {
            const sidebar = document.querySelector('#sidebarMenu');
            if (window.innerWidth < 768) {
                const bsCollapse = new bootstrap.Collapse(sidebar);
                bsCollapse.hide();
            }
        });
    });
</script>

<h4 class="text-white text-center py-3 border-bottom border-secondary">Buku Pelanggaran</h4>

<nav class="nav flex-column mt-2">
    <!-- DASHBOARD -->
    <a class="nav-link <?= ($menu_aktif == 'beranda') ? 'aktif' : ''; ?>" href="index.php?page=beranda">
        <i class="fas fa-home me-2"></i> Dashboard
    </a>

    <!-- DATA MASTER -->
    <div class="menu-group-title">Data Master</div>
    <a class="nav-link <?= ($menu_aktif == 'kelas') ? 'aktif' : ''; ?>" href="index.php?page=kelas">
        <i class="fas fa-school me-2"></i> Data Kelas
    </a>
    <a class="nav-link <?= ($menu_aktif == 'jenis') ? 'aktif' : ''; ?>" href="index.php?page=jenis">
        <i class="fas fa-list me-2"></i> Jenis Pelanggaran
    </a>
    <a class="nav-link <?= ($menu_aktif == 'sanksi') ? 'aktif' : ''; ?>" href="index.php?page=sanksi">
        <i class="fas fa-gavel me-2"></i> Sanksi
    </a>

    <!-- DATA OPERASIONAL -->
    <div class="menu-group-title">Data Transaksi</div>
    <a class="nav-link <?= ($menu_aktif == 'siswa') ? 'aktif' : ''; ?>" href="index.php?page=siswa">
        <i class="fas fa-users me-2"></i> Data Siswa
    </a>
    <a class="nav-link <?= ($menu_aktif == 'pelanggaran') ? 'aktif' : ''; ?>" href="index.php?page=pelanggaran">
        <i class="fas fa-exclamation-triangle me-2"></i> Catat Pelanggaran
    </a>

    <!-- SISTEM -->
    <div class="menu-group-title">Sistem</div>
    <a class="nav-link <?= ($menu_aktif == 'user') ? 'aktif' : ''; ?>" href="index.php?page=user">
        <i class="fas fa-user-cog me-2"></i> Manajemen User
    </a>
    <a class="nav-link text-danger" href="logout.php">
        <i class="fas fa-sign-out-alt me-2"></i> Keluar
    </a>
</nav>