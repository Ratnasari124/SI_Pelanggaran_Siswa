<?php
// Aktifkan pelaporan error PHP untuk mempermudah pelacakan jika ada kendala
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ==========================================
// 1. HUBUNGKAN KE DATABASE (ANTI-TERSETAT)
// ==========================================
$path_koneksi = dirname(__DIR__, 2) . '/koneksi.php';

if (file_exists($path_koneksi)) {
    include $path_koneksi;
} else {
    include '../../koneksi.php';
}

/** 
 * @var mysqli $conn 
 * @var mysqli $koneksi
 */

if (isset($conn) && !isset($koneksi)) {
    $koneksi = $conn;
} elseif (isset($db) && !isset($koneksi)) {
    $koneksi = $db;
}

if (!isset($koneksi) || !$koneksi instanceof mysqli) {
    die("Error: Variabel koneksi database tidak ditemukan. Periksa nama variabel di file koneksi.php Anda.");
}

// ==========================================
// 2. TANGKAP PARAMETER URL SECARA MENYELURUH
// ==========================================
$id_target = 0;
if (isset($_GET['id'])) {
    $id_target = (int)$_GET['id'];
} elseif (isset($_GET['id_siswa'])) {
    $id_target = (int)$_GET['id_siswa'];
} elseif (isset($_GET['id_kelompok'])) {
    $id_target = (int)$_GET['id_kelompok'];
}

$source = isset($_GET['source']) ? htmlspecialchars($_GET['source']) : ''; 
$page_aktif = isset($_GET['page']) ? htmlspecialchars($_GET['page']) : '';
$action = isset($_GET['action']) ? htmlspecialchars($_GET['action']) : '';

if ($id_target == 0) {
    echo "<script>alert('Data target tidak ditemukan atau ID tidak valid!'); window.history.back();</script>";
    exit;
}

// ==========================================
// 3. DETEKSI OTOMATIS JENIS MENU (PENGELOMPOKAN VS SEMUA)
// ==========================================
// Perbaikan Deteksi: Kita pastikan dulu apakah ID ini milik Jenis Pelanggaran atau Siswa
$cek_jenis = mysqli_query($koneksi, "SELECT id, nama_pelanggaran FROM jenis_pelanggaran WHERE id = $id_target LIMIT 1");

if ($cek_jenis && mysqli_num_rows($cek_jenis) > 0 && ($source == 'pengelompokan' || $page_aktif == 'pengelompokan_detail' || !isset($_GET['id_siswa']))) {
    // JIKA ID COCOK DENGAN JENIS PELANGGARAN, OTOMATIS MODE PENGELOMPOKAN
    $is_mode_pengelompokan = true;
    $siswa = null;
} else {
    // JIKA TIDAK, MAKA MASUK MODE SEMUA / DETAIL PER SISWA
    $is_mode_pengelompokan = false;
    $cek_siswa = mysqli_query($koneksi, "SELECT s.id, s.nis, s.nama AS nama_siswa, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.id_kelas = k.id WHERE s.id = $id_target LIMIT 1");
    $siswa = ($cek_siswa) ? mysqli_fetch_assoc($cek_siswa) : null;
}

// Inisialisasi variabel data
$total_kasus = 0;
$total_poin = 0;
$data_kasus = [];
$judul_halaman = "Detail Catatan Pelanggaran";

// ==========================================
// 4. QUERY DATA TABEL DINAMIS
// ==========================================
if (!$is_mode_pengelompokan) {
    // Jalur A: Menu Semua / Per Siswa
    if ($siswa) {
        $judul_halaman = "Detail Pelanggaran Siswa: " . $siswa['nama_siswa'];
    }

    // Hitung Statistik Utama Siswa
    $query_total = "SELECT COUNT(p.id) as total_kasus, IFNULL(SUM(j.poin), 0) as total_poin 
                    FROM pelanggaran p 
                    INNER JOIN jenis_pelanggaran j ON p.id_jenis = j.id 
                    WHERE p.id_siswa = ?";
    $stmt_total = $koneksi->prepare($query_total);
    $stmt_total->bind_param("i", $id_target);
    $stmt_total->execute();
    $stats = $stmt_total->get_result()->fetch_assoc();
    $total_kasus = (int)$stats['total_kasus'];
    $total_poin = (int)$stats['total_poin'];
    $stmt_total->close();

    // Ambil Riwayat Kasus Siswa
    $query_kasus = "SELECT p.id AS id_kasus, p.tanggal, p.keterangan, j.nama_pelanggaran, j.poin, 
                           k.nama_kelas, s.nama, s.nis, p.id_siswa
                    FROM pelanggaran p 
                    INNER JOIN jenis_pelanggaran j ON p.id_jenis = j.id 
                    INNER JOIN siswa s ON p.id_siswa = s.id
                    LEFT JOIN kelas k ON s.id_kelas = k.id
                    WHERE p.id_siswa = ? 
                    ORDER BY p.tanggal DESC, p.id DESC";
    $stmt_kasus = $koneksi->prepare($query_kasus);
    $stmt_kasus->bind_param("i", $id_target);
    $stmt_kasus->execute();
    $list_kasus = $stmt_kasus->get_result();
    while ($row = $list_kasus->fetch_assoc()) { $data_kasus[] = $row; }
    $stmt_kasus->close();

} else {
    // Jalur B: Menu Pengelompokan Pelanggaran
    $d_judul = mysqli_fetch_assoc($cek_jenis);
    $judul_halaman = "Detail Kelompok: " . $d_judul['nama_pelanggaran'];

    // Ambil daftar semua siswa yang masuk ke dalam kelompok pelanggaran ini
    $query_kasus = "SELECT p.id AS id_kasus, p.tanggal, p.keterangan, jp.nama_pelanggaran, jp.poin, 
                           s.nama, s.nis, k.nama_kelas, p.id_siswa
                    FROM pelanggaran p
                    INNER JOIN siswa s ON p.id_siswa = s.id
                    LEFT JOIN kelas k ON s.id_kelas = k.id
                    INNER JOIN jenis_pelanggaran jp ON p.id_jenis = jp.id
                    WHERE p.id_jenis = ? 
                    ORDER BY p.tanggal DESC, p.id DESC";
    $stmt_kasus = $koneksi->prepare($query_kasus);
    $stmt_kasus->bind_param("i", $id_target);
    $stmt_kasus->execute();
    $list_kasus = $stmt_kasus->get_result();
    while ($row = $list_kasus->fetch_assoc()) { $data_kasus[] = $row; }
    $stmt_kasus->close();
    
    $total_kasus = count($data_kasus);
    foreach ($data_kasus as $dk) { $total_poin += $dk['poin']; }
}

// ========================================================
// 5. PROSES LIVE RENDER OUTPUT PDF (JIKA ACTION=CETAK)
// ========================================================
if ($action == 'cetak') {
    if (ob_get_length()) ob_clean(); 
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Cetak PDF Laporan - <?= htmlspecialchars($judul_halaman) ?></title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <style>
            body { font-family: 'Arial', sans-serif; font-size: 13px; color: #000; background: #fff; }
            .garis-kop { border-bottom: 3px double #000; padding-bottom: 5px; margin-bottom: 20px; }
            .table th { background-color: #f8f9fa !important; color: #000 !important; font-weight: bold; }
            @media print {
                .no-print { display: none !important; }
                body { padding: 0; margin: 0; }
                @page { size: A4 portrait; margin: 1.5cm; }
            }
        </style>
    </head>
    <body>
    <div class="container-fluid mt-3">
        <div class="no-print text-end mb-4">
            <button onclick="window.print();" class="btn btn-primary btn-sm me-1"><i class="fas fa-print"></i> Cetak Dokumen</button>
            <button onclick="window.close();" class="btn btn-secondary btn-sm">Tutup Tab</button>
        </div>

        <div class="garis-kop text-center">
            <h4 class="mb-1 fw-bold">LAPORAN DATA DETIL PELANGGARAN TATA TERTIB SISWA</h4>
            <p class="text-muted mb-2">Sistem Informasi Layanan Bimbingan Konseling (BK)</p>
        </div>

        <div class="card p-3 mb-3 bg-light border">
            <table class="table table-sm table-borderless mb-0" style="font-size: 13px;">
                <tr>
                    <td width="20%"><strong>Jenis Laporan</strong></td>
                    <td>: <?= htmlspecialchars($judul_halaman); ?></td>
                    <td class="text-end">Total Kejadian: <strong><?= $total_kasus ?> Kasus</strong></td>
                </tr>
                <tr>
                    <td><strong>Tanggal Cetak</strong></td>
                    <td>: <?= date('d/m/Y H:i'); ?> WIB</td>
                    <td class="text-end">Akumulasi Bobot: <strong class="text-danger"><?= $total_poin ?> Poin</strong></td>
                </tr>
                <?php if(!$is_mode_pengelompokan && $siswa): ?>
                <tr>
                    <td><strong>Siswa / Kelas</strong></td>
                    <td colspan="2">: <?= htmlspecialchars($siswa['nama_siswa']) ?> (NIS: <?= htmlspecialchars($siswa['nis']) ?>) / Kelas: <?= htmlspecialchars($siswa['nama_kelas']) ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

        <!-- Tabel Riwayat PDF -->
        <table class="table table-bordered align-middle">
            <thead>
                <tr class="text-center">
                    <th width="5%">No</th>
                    <th width="15%">Tanggal</th>
                    <?php if($is_mode_pengelompokan): ?>
                        <th>Nama Siswa (Kelas)</th>
                    <?php endif; ?>
                    <th>Bentuk / Jenis Pelanggaran</th>
                    <th width="10%">Poin</th>
                    <th>Keterangan Tambahan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($data_kasus) > 0): $no = 1; foreach ($data_kasus as $row): ?>
                    <tr>
                        <td class="text-center"><?= $no++; ?></td>
                        <td class="text-center"><?= date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                        <?php if($is_mode_pengelompokan): ?>
                            <td><strong><?= htmlspecialchars($row['nama']); ?></strong><br><small class="text-muted"><?= htmlspecialchars($row['nama_kelas']); ?> (NIS: <?= htmlspecialchars($row['nis']); ?>)</small></td>
                        <?php endif; ?>
                        <td><strong><?= htmlspecialchars($row['nama_pelanggaran']); ?></strong></td>
                        <td class="text-center text-danger fw-bold">+<?= $row['poin']; ?></td>
                        <td><?= htmlspecialchars($row['keterangan'] ?: '-'); ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="<?= $is_mode_pengelompokan ? '6' : '5' ?>" class="text-center text-muted py-3">Tidak ada data rekap catatan pelanggaran.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="row mt-5 pt-3">
            <div class="col-8"></div>
            <div class="col-4 text-center">
                <p class="mb-5">Mengetahui,<br>Guru Pembimbing / BK</p>
                <p class="fw-bold text-decoration-underline" style="margin-top: 60px;">( _______________________ )</p>
            </div>
        </div>
    </div>
    <script>
        window.addEventListener('DOMContentLoaded', () => { setTimeout(() => { window.print(); }, 400); });
    </script>
    </body>
    </html>
    <?php
    exit;
}
?>

<!-- ========================================================
// 6. TAMPILAN INTERFACE UTAMA DASHBOARD ADMIN (NORMAL MODE)
// ======================================================== -->
<div class="container-fluid py-4">
    <div class="card shadow-sm border-0 rounded-3 bg-white mb-4">
        <div class="card-header bg-dark text-white p-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="fas fa-list text-warning me-2"></i><?= htmlspecialchars($judul_halaman) ?></h5>
            <div class="d-flex gap-2">
                <a href="index.php?page=<?= $page_aktif ?>&id=<?= $id_target ?>&source=<?= $source ?>&action=cetak" target="_blank" class="btn btn-danger btn-sm shadow-2xs">
                    <i class="fas fa-file-pdf me-1"></i> Cetak PDF
                </a>
                <a href="javascript:void(0);" onclick="window.history.back();" class="btn btn-secondary btn-sm shadow-2xs">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6 mb-3 mb-md-0">
            <div class="card shadow-sm border-0 rounded-3 p-3 bg-white border-start border-primary border-3 h-100 d-flex flex-column justify-content-center">
                <h6 class="text-muted text-uppercase font-weight-bold mb-2" style="font-size: 0.8rem;">Informasi Subjek Rekapitulasi</h6>
                <table class="table table-borderless table-sm mb-0 small">
                    <?php if (!$is_mode_pengelompokan && $siswa): ?>
                        <tr><td width="30%"><strong>Nama Siswa</strong></td><td>: <?= htmlspecialchars($siswa['nama_siswa']); ?></td></tr>
                        <tr><td><strong>NIS / Kelas</strong></td><td>: <?= htmlspecialchars($siswa['nis'] ?? '-'); ?> / <?= htmlspecialchars($siswa['nama_kelas'] ?? '-'); ?></td></tr>
                    <?php else: ?>
                        <tr><td width="30%"><strong>Kelompok</strong></td><td>: <?= htmlspecialchars($judul_halaman); ?></td></tr>
                        <tr><td><strong>Format Data</strong></td><td>: Rekapitulasi Komponen Kolektif Pelanggaran</td></tr>
                    <?php endif; ?>
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

    <!-- TABEL DATA UTAMA -->
    <div class="card shadow-sm border-0 rounded-3 bg-white">
        <div class="card-header bg-dark text-white font-weight-bold p-3">
            <i class="fa fa-history me-1 text-warning"></i> Lembar Riwayat Catatan
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0" style="font-size: 0.9rem;">
                    <thead>
                        <tr class="table-secondary text-center text-nowrap">
                            <th width="5%">No</th>
                            <th width="15%">Tanggal</th>
                            <?php if($is_mode_pengelompokan): ?>
                                <!-- Kolom Khusus Menu Pengelompokan -->
                                <th>Biodata Siswa Pelanggar</th>
                                <th>Kelas</th>
                            <?php endif; ?>
                            <th>Jenis / Bentuk Pelanggaran</th>
                            <th width="10%">Poin</th>
                            <th>Keterangan Tambahan / Sanksi Lapangan</th>
                            <th width="10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($data_kasus) > 0): $no = 1; foreach ($data_kasus as $row): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++; ?></td>
                            <td class="text-center text-nowrap"><?= date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                            
                            <?php if($is_mode_pengelompokan): ?>
                                <!-- Isi Kolom Khusus Menu Pengelompokan -->
                                <td class="fw-semibold text-dark">
                                    <?= htmlspecialchars($row['nama']); ?><br>
                                    <small class="text-muted">NIS: <?= htmlspecialchars($row['nis']); ?></small>
                                </td>
                                <td class="text-center"><?= htmlspecialchars($row['nama_kelas']); ?></td>
                            <?php endif; ?>

                            <td><strong class="text-dark"><?= htmlspecialchars($row['nama_pelanggaran']); ?></strong></td>
                            <td class="text-center"><span class="badge bg-danger rounded-pill px-2 py-1">+<?= $row['poin']; ?></span></td>
                            <td><span class="text-secondary small"><?= htmlspecialchars($row['keterangan'] ?: '-'); ?></span></td>
                            
                            <td class="text-center text-nowrap">
                                <?php $id_kembali = $is_mode_pengelompokan ? $id_target : ($row['id_siswa'] ?? $id_target); ?>
                                <a href="pages/pelanggaran/pelanggaran_hapus.php?id_kasus=<?= $row['id_kasus']; ?>&asal=detail&id_kelompok=<?= $id_kembali; ?>&source=<?= $source; ?>"
                                   onclick="return confirm('Apakah Anda yakin ingin menghapus catatan pelanggaran ini?');" 
                                   class="btn btn-danger btn-sm px-2 py-1 shadow-2xs">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="<?= $is_mode_pengelompokan ? '7' : '5' ?>" class="text-center text-muted py-5">
                                <i class="fas fa-folder-open fa-2x mb-2 opacity-50"></i><br>
                                <em>Tidak ada data riwayat catatan pelanggaran yang ditemukan.</em>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>