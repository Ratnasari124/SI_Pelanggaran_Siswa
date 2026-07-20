<?php
// Aktifkan pelaporan error PHP untuk mempermudah pelacakan jika ada kendala
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ==========================================
// 1. HUBUNGKAN KE DATABASE (ANTI-TERSETAT)
// ==========================================
// Menggunakan dirname(__DIR__, 2) untuk naik 2 tingkat secara absolut dari folder /pages/pelanggaran/
$path_koneksi = dirname(__DIR__, 2) . '/koneksi.php';

if (file_exists($path_koneksi)) {
    include $path_koneksi;
} else {
    // Jalur cadangan jika file koneksi berada selevel dengan index.php di mode routing
    include '../../koneksi.php';
}

/** 
 * Memberitahu VS Code secara absolut bahwa variabel database adalah objek MySQLi yang valid.
 * @var mysqli $conn 
 * @var mysqli $koneksi
 */

// Sinkronisasi variabel koneksi agar tidak undefined
if (isset($conn) && !isset($koneksi)) {
    $koneksi = $conn;
} elseif (isset($db) && !isset($koneksi)) {
    $koneksi = $db;
}

// Validasi akhir untuk memastikan koneksi benar-benar siap digunakan
if (!isset($koneksi) || !$koneksi instanceof mysqli) {
    die("Error: Variabel koneksi database tidak ditemukan. Periksa nama variabel di file koneksi.php Anda.");
}

// ==========================================
// 2. TANGKAP PARAMETER DARI URL
// ==========================================
$id_siswa = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['id_siswa']) ? (int)$_GET['id_siswa'] : 0);
$source = isset($_GET['source']) ? $_GET['source'] : 'menu'; 

if ($id_siswa == 0) {
    echo "<script>alert('Siswa tidak ditemukan!'); window.history.back();</script>";
    exit;
}

// ==========================================
// 3. AMBIL DATA AKADEMIK SISWA (Sesuai skrip utama s.id & k.id)
// ==========================================
$query_siswa = "SELECT s.id, s.nis, s.nama AS nama_siswa, k.nama_kelas 
                FROM siswa s 
                LEFT JOIN kelas k ON s.id_kelas = k.id 
                WHERE s.id = ? LIMIT 1";
$stmt_siswa = $koneksi->prepare($query_siswa);
$stmt_siswa->bind_param("i", $id_siswa);
$stmt_siswa->execute();
$result_siswa = $stmt_siswa->get_result();
$siswa = $result_siswa->fetch_assoc();

if (!$siswa) {
    die("Data siswa tidak ditemukan di database. Pastikan ID Siswa valid.");
}

// ==========================================
// 4. HITUNG TOTAL KASUS DAN TOTAL POIN (Query JOIN Valid)
// ==========================================
$query_total = "SELECT COUNT(p.id) as total_kasus, IFNULL(SUM(j.poin), 0) as total_poin 
                FROM pelanggaran p 
                INNER JOIN jenis_pelanggaran j ON p.id_jenis = j.id 
                WHERE p.id_siswa = ?";
$stmt_total = $koneksi->prepare($query_total);
$stmt_total->bind_param("i", $id_siswa);
$stmt_total->execute();
$stats = $stmt_total->get_result()->fetch_assoc();

$total_kasus = isset($stats['total_kasus']) ? (int)$stats['total_kasus'] : 0;
$total_poin = isset($stats['total_poin']) ? (int)$stats['total_poin'] : 0;

// ==========================================
// 5. AMBIL DAFTAR RIWAYAT PELANGGARAN SISWA
// ==========================================
$query_kasus = "SELECT p.id AS id_kasus, p.tanggal, p.keterangan, j.nama_pelanggaran, j.poin
                FROM pelanggaran p 
                INNER JOIN jenis_pelanggaran j ON p.id_jenis = j.id 
                WHERE p.id_siswa = ? 
                ORDER BY p.tanggal DESC, p.id DESC";
$stmt_kasus = $koneksi->prepare($query_kasus);
$stmt_kasus->bind_param("i", $id_siswa);
$stmt_kasus->execute();
$list_kasus = $stmt_kasus->get_result();
?>

<!-- ================= TAMPILAN HTML DETAIL ================= -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 font-weight-bold text-dark"><i class="fas fa-user-shield me-2 text-primary"></i>Detail Rekap Pelanggaran</h4>
        <?php if ($source == 'pengelompokan'): ?>
            <a href="index.php?page=pelanggaran&view=pengelompokan" class="btn btn-secondary btn-sm shadow-sm"><i class="fa fa-arrow-left me-1"></i> Kembali</a>
        <?php else: ?>
            <a href="index.php?page=pelanggaran&view=semua" class="btn btn-secondary btn-sm shadow-sm"><i class="fa fa-arrow-left me-1"></i> Kembali</a>
        <?php endif; ?>
    </div>

    <!-- Informasi Profil Akademik -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3 mb-md-0">
            <div class="card shadow-sm border-0 rounded-3 p-3 bg-white border-start border-primary border-3">
                <h6 class="text-muted text-uppercase font-weight-bold mb-3" style="font-size: 0.8rem;">Informasi Akademik Siswa</h6>
                <table class="table table-borderless table-sm mb-0 small">
                    <tr><td width="35%"><strong>Nama Lengkap</strong></td><td>: <?= htmlspecialchars($siswa['nama_siswa'] ?? ''); ?></td></tr>
                    <tr><td><strong>NIS / NISN</strong></td><td>: <?= htmlspecialchars($siswa['nis'] ?? '-'); ?></td></tr>
                    <tr><td><strong>Kelas</strong></td><td>: <?= htmlspecialchars($siswa['nama_kelas'] ?? '-'); ?></td></tr>
                </table>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
            <div class="card bg-info text-white shadow-sm border-0 rounded-3 p-3 text-center h-100 d-flex flex-column justify-content-center">
                <h6 class="text-uppercase mb-1 opacity-75" style="font-size: 0.75rem;">Total Kasus</h6>
                <h3 class="font-weight-bold mb-0"><?= $total_kasus; ?> Kejadian</h3>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card bg-danger text-white shadow-sm border-0 rounded-3 p-3 text-center h-100 d-flex flex-column justify-content-center">
                <h6 class="text-uppercase mb-1 opacity-75" style="font-size: 0.75rem;">Akumulasi Poin</h6>
                <h3 class="font-weight-bold mb-0">+ <?= $total_poin; ?> Poin</h3>
            </div>
        </div>
    </div>

    <!-- Tabel Riwayat Kasus -->
    <div class="card shadow-sm border-0 rounded-3 bg-white">
        <div class="card-header bg-dark text-white font-weight-bold p-3">
            <i class="fa fa-history me-1 text-warning"></i> Daftar Riwayat Catatan Pelanggaran
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0" style="font-size: 0.9rem;">
                    <thead>
                        <tr class="table-secondary text-center text-nowrap">
                            <th width="5%">No</th>
                            <th width="15%">Tanggal</th>
                            <th>Bentuk Pelanggaran & Keterangan</th>
                            <th width="10%">Poin</th>
                            <th width="15%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($list_kasus->num_rows > 0):
                            $no = 1;
                            while ($row = $list_kasus->fetch_assoc()):
                        ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++; ?></td>
                            <td class="text-center text-nowrap"><?= date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                            <td>
                                <strong class="text-dark"><?= htmlspecialchars($row['nama_pelanggaran']); ?></strong>
                                <?php if(!empty($row['keterangan'])): ?>
                                    <br><small class="text-muted">Ket: <?= htmlspecialchars($row['keterangan']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><span class="badge bg-danger rounded-pill px-2 py-1">+<?= $row['poin']; ?></span></td>
                            <td class="text-center text-nowrap">
                                <a href="pages/pelanggaran/pelanggaran_hapus.php?id_kasus=<?= $row['id_kasus']; ?>&asal=detail&id_kelompok=<?= $id_siswa; ?>&source=<?= $source; ?>"
                                   onclick="return confirm('Apakah Anda yakin ingin menghapus catatan pelanggaran ini?');" 
                                   class="btn btn-danger btn-sm px-2 py-1 d-inline-flex align-items-center gap-1 shadow-2xs">
                                   <i class="fa fa-trash small"></i> Hapus
                                </a>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="fas fa-folder-open fa-2x mb-2 opacity-50"></i><br>
                                <em>Tidak ada riwayat catatan pelanggaran untuk siswa ini.</em>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Tutup statement
$stmt_siswa->close();
$stmt_total->close();
$stmt_kasus->close();
?>