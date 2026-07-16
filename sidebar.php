<?php
// Menangkap parameter page dari URL. Jika kosong, default ke 'beranda'
$page_url = isset($_GET['page']) ? $_GET['page'] : 'beranda';

// Mengambil kata pertama sebelum tanda underscore (_)
// Contoh: 'siswa_tambah' menjadi 'siswa'
$menu_aktif = explode('_', $page_url)[0];
?>

<!-- Tambahan CSS khusus untuk menu yang sedang aktif -->
<style>
    .sidebar .nav-link.aktif {
        background-color: #475f77; 
        border-left: 4px solid #288bcd;
        color: #f7f7f7;
        font-weight: bold;
    }
</style>

<h4 class="text-white text-center py-3 border-bottom border-secondary">Buku Pelanggaran</h4>
<nav class="nav flex-column mt-3">
    <!-- Logika if shorhand (Ternary) untuk menambahkan class 'aktif' -->
    <a class="nav-link <?= ($menu_aktif == 'beranda') ? 'aktif' : ''; ?>" href="index.php?page=beranda">
        <i class="fas fa-home me-2"></i> Dashboard
    </a>
    
    <a class="nav-link <?= ($menu_aktif == 'kelas') ? 'aktif' : ''; ?>" href="index.php?page=kelas">
        <i class="fas fa-school me-2"></i> Data Kelas
    </a>
    
    <a class="nav-link <?= ($menu_aktif == 'siswa') ? 'aktif' : ''; ?>" href="index.php?page=siswa">
        <i class="fas fa-users me-2"></i> Data Siswa
    </a>
    
    <a class="nav-link <?= ($menu_aktif == 'jenis') ? 'aktif' : ''; ?>" href="index.php?page=jenis">
        <i class="fas fa-list me-2"></i> Jenis Pelanggaran
    </a>
    <a class="nav-link <?= ($menu_aktif == 'sanksi') ? 'aktif' : ''; ?>" href="index.php?page=sanksi">
        <i class="fas fa-gavel me-2"></i> Sanksi Pelanggaran
    </a>
    
    <a class="nav-link <?= ($menu_aktif == 'pelanggaran') ? 'akt
    if' : ''; ?>" href="index.php?page=pelanggaran">
        <i class="fas fa-exclamation-triangle me-2"></i> Catat Pelanggaran
    </a>
</nav>