<?php
/** @var mysqli $conn */
// Pastikan session sudah ada
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

// Ambil data user dari database agar nama selalu update
$id_user = $_SESSION['id'];
$q_user = mysqli_query($conn, "SELECT username FROM users WHERE id = '$id_user'");
$user = mysqli_fetch_assoc($q_user);
$nama_tampil = $user['username'] ?? 'User';

// 1. Statistik Utama
$tgl_hari_ini = date('Y-m-d');
$bulan_ini = date('Y-m');

$data_stats = [
    'hari_ini' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pelanggaran WHERE DATE(tanggal) = '$tgl_hari_ini'"))['total'],
    'bulan_ini' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pelanggaran WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'"))['total'],
    'total_siswa' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM siswa"))['total'],
    'total_poin' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(j.poin) as total FROM pelanggaran p JOIN jenis_pelanggaran j ON p.id_jenis = j.id WHERE DATE_FORMAT(p.tanggal, '%Y-%m') = '$bulan_ini'"))['total'] ?? 0
];

// 2. Data Kelas Hotspot
$q_kelas = mysqli_query($conn, "SELECT k.nama_kelas, COUNT(p.id) as total FROM pelanggaran p JOIN siswa s ON p.id_siswa = s.id JOIN kelas k ON s.id_kelas = k.id GROUP BY k.id ORDER BY total DESC LIMIT 1");
$kelas_terbanyak = mysqli_fetch_assoc($q_kelas);

// 3. Data Pelanggaran Terbanyak (Jenis)
$q_top_jenis = mysqli_query($conn, "SELECT j.nama_pelanggaran, COUNT(p.id) as total FROM pelanggaran p JOIN jenis_pelanggaran j ON p.id_jenis = j.id GROUP BY j.id ORDER BY total DESC LIMIT 3");
?>

<div class="container-fluid px-0">
   <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <!-- ucwords akan membuat huruf depan jadi kapital otomatis -->
            <h2 class="fw-extrabold text-slate-900 mb-1">
                Hello, <?= ucwords(htmlspecialchars($nama_tampil)) ?> 👋
            </h2>
        </div>
        <div class="text-end">
            <span class="badge bg-dark rounded-pill px-3 py-2 shadow-sm">
                <i class="far fa-calendar-alt me-1"></i> <?= date('l, d F Y') ?>
            </span>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-3">
        <?php 
        $cards = [
            ['Pelanggaran Hari Ini', $data_stats['hari_ini'], 'fas fa-bolt', 'primary'],
            ['Pelanggaran Bulan Ini', $data_stats['bulan_ini'], 'fas fa-calendar-alt', 'success'],
            ['Total Poin Bulan Ini', $data_stats['total_poin'], 'fas fa-exclamation-triangle', 'warning'],
            ['Total Siswa', $data_stats['total_siswa'], 'fas fa-user-graduate', 'info']
        ];
        foreach($cards as $c): ?>
        <div class="col-md-3">
            <div class="card p-3 border-0 shadow-sm" style="border-radius:20px">
                <div class="d-flex align-items-center">
                    <div class="bg-<?= $c[3] ?>-subtle p-3 rounded-4 me-3 text-<?= $c[3] ?>">
                        <i class="<?= $c[2] ?> fa-lg"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block"><?= $c[0] ?></small>
                        <h4 class="fw-bold mb-0"><?= $c[1] ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Bottom Row -->
    <div class="row mt-4">
        <!-- Aktivitas Terakhir -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0 p-4 h-100" style="border-radius:20px">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0"><i class="fas fa-history text-primary me-2"></i> Aktivitas Terakhir</h5>
                    <a href="index.php?page=pelanggaran" class="btn btn-dark rounded-pill px-4 shadow-sm">Lihat Semua</a>
                </div>
                <table class="table table-hover align-middle">
                    <thead class="text-muted small">
                        <tr><th>SISWA</th><th>PELANGGARAN</th><th>POIN</th></tr>
                    </thead>
                    <tbody>
                        <?php 
                        $q_recent = mysqli_query($conn, "SELECT s.nama, j.nama_pelanggaran, j.poin FROM pelanggaran p JOIN siswa s ON p.id_siswa = s.id JOIN jenis_pelanggaran j ON p.id_jenis = j.id ORDER BY p.tanggal DESC LIMIT 5");
                        while($r = mysqli_fetch_assoc($q_recent)): ?>
                        <tr>
                            <td class="fw-bold"><?= $r['nama'] ?></td>
                            <td><?= $r['nama_pelanggaran'] ?></td>
                            <td><span class="badge bg-danger-subtle text-danger rounded-pill">+<?= $r['poin'] ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Insight Samping -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 p-4 h-100" style="border-radius:20px; background: #0f172a; color: white;">
                <h5 class="fw-bold mb-4 text-white">Insight Kesiswaan</h5>
                <p class="text-secondary small">Kelas dengan pelanggaran terbanyak:</p>
                <h3 class="text-info fw-bold"><?= $kelas_terbanyak['nama_kelas'] ?? '-' ?></h3>
                <small class="text-secondary">Dengan total <?= $kelas_terbanyak['total'] ?? 0 ?> kasus.</small>
                
                <hr class="my-4 border-secondary">
                
                <p class="text-secondary small">Top Pelanggaran:</p>
                <?php while($top = mysqli_fetch_assoc($q_top_jenis)): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="small"><?= $top['nama_pelanggaran'] ?></span>
                        <span class="fw-bold"><?= $top['total'] ?></span>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>