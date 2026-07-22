<?php
/** @var mysqli $conn */

// Mencegah error ob_clean() saat cetak PDF / Excel
if (ob_get_level() == 0) {
    ob_start();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. TANGKAP PARAMETER
$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, trim($_GET['id'])) : '';
$source = isset($_GET['source']) ? trim($_GET['source']) : (isset($_GET['from_view']) ? trim($_GET['from_view']) : 'pengelompokan');

// Simpan/sinkronkan menu asal ke session
$_SESSION['last_view_pelanggaran'] = $source;

// Validasi jika ID kosong
if (empty($id)) {
    echo "<script>
            alert('ID Tidak ditemukan!');
            window.location.href = 'index.php?page=pelanggaran&view=" . urlencode($source) . "';
          </script>";
    exit;
}

// ==========================================
// 1. PROSES EXPORT EXCEL DETAIL
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'export_excel_detail') {
    if (ob_get_length()) ob_clean();

    $filename = "Detail_Pelanggaran_" . date('Ymd_His') . ".xls";
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "<table border='1'>";
    
    if ($source == 'pengelompokan') {
        // EXCEL MODE PENGELOMPOKAN (KOMPLEKS): Sertakan NIS, Nama, Kelas
        $q_excel = mysqli_query($conn, "SELECT p.tanggal, s.nis, s.nama AS nama_siswa, k.nama_kelas, j.nama_pelanggaran, j.poin, p.keterangan, u.nama_lengkap AS petugas 
                                         FROM pelanggaran p 
                                         JOIN jenis_pelanggaran j ON p.id_jenis = j.id 
                                         JOIN siswa s ON p.id_siswa = s.id 
                                         LEFT JOIN kelas k ON s.id_kelas = k.id 
                                         LEFT JOIN users u ON p.id_user = u.id
                                         WHERE p.id_siswa = '$id' 
                                         ORDER BY p.tanggal DESC");

        echo "<tr style='background-color:#f2f2f2; font-weight:bold;'>
                <th>No</th><th>Tanggal</th><th>NIS</th><th>Nama Siswa</th><th>Kelas</th><th>Petugas</th><th>Jenis Pelanggaran</th><th>Bentuk & Keterangan</th><th>Poin</th><th>Sanksi</th>
              </tr>";

        if ($q_excel && mysqli_num_rows($q_excel) > 0) {
            $no = 1;
            while ($r = mysqli_fetch_assoc($q_excel)) {
                echo "<tr>
                        <td align='center'>" . $no++ . "</td>
                        <td align='center'>" . date('d/m/Y', strtotime($r['tanggal'])) . "</td>
                        <td>" . htmlspecialchars($r['nis'] ?? '-') . "</td>
                        <td>" . htmlspecialchars($r['nama_siswa'] ?? '-') . "</td>
                        <td>" . htmlspecialchars($r['nama_kelas'] ?? '-') . "</td>
                        <td>" . htmlspecialchars($r['petugas'] ?? '-') . "</td>
                        <td>" . htmlspecialchars($r['nama_pelanggaran']) . "</td>
                        <td>" . htmlspecialchars($r['keterangan'] ?: '-') . "</td>
                        <td align='center'>+" . $r['poin'] . "</td>
                        <td>-</td>
                      </tr>";
            }
        }
    } else {
        // EXCEL MODE SEMUA (RINGKAS): Hanya detail transaksi tunggal
        $q_excel = mysqli_query($conn, "SELECT p.tanggal, j.nama_pelanggaran, j.poin, p.keterangan, u.nama_lengkap AS petugas 
                                         FROM pelanggaran p 
                                         JOIN jenis_pelanggaran j ON p.id_jenis = j.id 
                                         LEFT JOIN users u ON p.id_user = u.id
                                         WHERE p.id = '$id'");

        echo "<tr style='background-color:#f2f2f2; font-weight:bold;'>
                <th>No</th><th>Tanggal</th><th>Petugas</th><th>Jenis Pelanggaran</th><th>Bentuk & Keterangan Pelanggaran</th><th>Poin</th><th>Sanksi</th>
              </tr>";

        if ($q_excel && mysqli_num_rows($q_excel) > 0) {
            $no = 1;
            while ($r = mysqli_fetch_assoc($q_excel)) {
                echo "<tr>
                        <td align='center'>" . $no++ . "</td>
                        <td align='center'>" . date('d/m/Y', strtotime($r['tanggal'])) . "</td>
                        <td>" . htmlspecialchars($r['petugas'] ?? '-') . "</td>
                        <td>" . htmlspecialchars($r['nama_pelanggaran']) . "</td>
                        <td>" . htmlspecialchars($r['keterangan'] ?: '-') . "</td>
                        <td align='center'>+" . $r['poin'] . "</td>
                        <td>-</td>
                      </tr>";
            }
        }
    }

    echo "</table>";
    exit;
}

// ==========================================
// 2. PROSES CETAK PDF DETAIL
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'cetak_pdf_detail') {
    if (ob_get_length()) ob_clean();

    if ($source == 'pengelompokan') {
        $id_siswa_pdf = $id;
        $d_siswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT s.*, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.id_kelas = k.id WHERE s.id = '$id_siswa_pdf'"));
        $q_cetak = mysqli_query($conn, "SELECT p.tanggal, s.nis, s.nama AS nama_siswa, k.nama_kelas, j.nama_pelanggaran, j.poin, p.keterangan, u.nama_lengkap AS petugas 
                                        FROM pelanggaran p 
                                        JOIN jenis_pelanggaran j ON p.id_jenis = j.id 
                                        JOIN siswa s ON p.id_siswa = s.id
                                        LEFT JOIN kelas k ON s.id_kelas = k.id
                                        LEFT JOIN users u ON p.id_user = u.id
                                        WHERE p.id_siswa = '$id_siswa_pdf' 
                                        ORDER BY p.tanggal DESC");
    } else {
        $q_cetak = mysqli_query($conn, "SELECT p.tanggal, j.nama_pelanggaran, j.poin, p.keterangan, u.nama_lengkap AS petugas 
                                        FROM pelanggaran p 
                                        JOIN jenis_pelanggaran j ON p.id_jenis = j.id 
                                        LEFT JOIN users u ON p.id_user = u.id
                                        WHERE p.id = '$id'");
        $d_siswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT s.*, k.nama_kelas FROM pelanggaran p JOIN siswa s ON p.id_siswa = s.id LEFT JOIN kelas k ON s.id_kelas = k.id WHERE p.id = '$id'"));
    }
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Cetak PDF - Detail Pelanggaran Siswa</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body { background-color: #525659; font-family: 'Times New Roman', Times, serif; font-size: 11pt; margin: 0; padding: 20px 0; }
            .paper { background: #fff; width: 210mm; min-height: 297mm; margin: 0 auto; padding: 20mm 15mm; box-shadow: 0 0 10px rgba(0,0,0,0.5); box-sizing: border-box; }
            .garis-kop { border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 20px; }
            .table-pdf { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 10pt; }
            .table-pdf th, .table-pdf td { border: 1px solid #000; padding: 6px 8px; vertical-align: middle; }
            .table-pdf th { background-color: #f2f2f2 !important; text-align: center; font-weight: bold; }
            @media print {
                body { background: none !important; padding: 0 !important; }
                .paper { width: 100% !important; box-shadow: none !important; padding: 0 !important; margin: 0 !important; }
                .no-print { display: none !important; }
                @page { size: A4 portrait; margin: 1.5cm; }
            }
        </style>
    </head>
    <body>

        <div class="no-print text-center mb-3">
            <button onclick="window.print();" class="btn btn-primary btn-sm px-3 shadow"><i class="fas fa-file-pdf me-1"></i> Cetak / Simpan PDF</button>
            <button onclick="window.close();" class="btn btn-secondary btn-sm px-3 shadow me-1"><i class="fas fa-times me-1"></i> Tutup</button>
        </div>

        <div class="paper">
            <div class="garis-kop text-center">
                <h3 class="fw-bold mb-1" style="font-size: 16pt;">LAPORAN DETAIL PELANGGARAN SISWA</h3>
                <h5 class="mb-0 text-uppercase" style="font-size: 12pt;">SISTEM INFORMASI BIMBINGAN KONSELING (BK)</h5>
            </div>

            <?php if (isset($d_siswa)): ?>
                <table class="table table-borderless mb-3" style="font-size: 11pt;">
                    <tr>
                        <td width="15%"><strong>NIS / NISN</strong></td><td width="2%">:</td><td width="33%"><?= htmlspecialchars($d_siswa['nis'] ?? '-') ?></td>
                        <td width="15%"><strong>Kelas</strong></td><td width="2%">:</td><td width="33%"><?= htmlspecialchars($d_siswa['nama_kelas'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td><strong>Nama Siswa</strong></td><td>:</td><td><strong><?= htmlspecialchars($d_siswa['nama'] ?? '-') ?></strong></td>
                        <td><strong>Tanggal Cetak</strong></td><td>:</td><td><?= date('d/m/Y') ?></td>
                    </tr>
                </table>
            <?php endif; ?>

            <table class="table-pdf">
                <thead>
                    <tr>
                        <th width="4%">No</th>
                        <th width="12%">Tanggal</th>
                        <?php if ($source == 'pengelompokan'): ?>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                        <?php endif; ?>
                        <th>Petugas</th>
                        <th>Jenis Pelanggaran</th>
                        <th>Bentuk & Keterangan</th>
                        <th width="7%">Poin</th>
                        <th>Sanksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($q_cetak && mysqli_num_rows($q_cetak) > 0): $no = 1; while($r = mysqli_fetch_assoc($q_cetak)): ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td class="text-center"><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                            <?php if ($source == 'pengelompokan'): ?>
                                <td><?= htmlspecialchars($r['nis'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($r['nama_siswa'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($r['nama_kelas'] ?? '-') ?></td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars($r['petugas'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['nama_pelanggaran']) ?></td>
                            <td><?= htmlspecialchars($r['keterangan'] ?: '-') ?></td>
                            <td class="text-center" style="color: red; font-weight: bold;">+<?= $r['poin'] ?></td>
                            <td class="text-center">-</td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="<?= ($source == 'pengelompokan') ? '10' : '7' ?>" class="text-center py-3">Tidak ada data pelanggaran.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="row mt-5" style="page-break-inside: avoid;">
                <div class="col-7"></div>
                <div class="col-5 text-center">
                    <p class="mb-1">Mengetahui,</p>
                    <p class="mb-5">Guru Bimbingan Konseling (BK)</p>
                    <br><br>
                    <p class="fw-bold text-decoration-underline mb-0">( _______________________ )</p>
                    <small>NIP. ........................................</small>
                </div>
            </div>
        </div>

        <script>
            window.addEventListener('DOMContentLoaded', () => { setTimeout(() => { window.print(); }, 500); });
        </script>
    </body>
    </html>
    <?php
    exit;
}

// ==========================================
// 3. AMBIL DATA UNTUK TAMPILAN HALAMAN DETAIL
// ==========================================
if ($source == 'pengelompokan') {
    // ---- MODE PENGELOMPOKAN (Berdasarkan ID Siswa - KOMPLEKS) ----
    $id_siswa = $id;

    // Data Siswa
    $q_siswa = mysqli_query($conn, "SELECT s.*, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.id_kelas = k.id WHERE s.id = '$id_siswa'");
    $siswa = mysqli_fetch_assoc($q_siswa);

    // List Pelanggaran Siswa beserta data Siswa
    $q_list = mysqli_query($conn, "SELECT p.id AS id_kasus, p.tanggal, p.keterangan, j.nama_pelanggaran, j.poin, u.nama_lengkap AS petugas,
                                          s.nis, s.nama AS nama_siswa, k.nama_kelas
                                   FROM pelanggaran p
                                   JOIN jenis_pelanggaran j ON p.id_jenis = j.id
                                   JOIN siswa s ON p.id_siswa = s.id
                                   LEFT JOIN kelas k ON s.id_kelas = k.id
                                   LEFT JOIN users u ON p.id_user = u.id
                                   WHERE p.id_siswa = '$id_siswa'
                                   ORDER BY p.tanggal DESC, p.id DESC");

    $data_tabel = [];
    if ($q_list) {
        while ($row = mysqli_fetch_assoc($q_list)) {
            $data_tabel[] = $row;
        }
    }

    // Ringkasan
    $q_sum = mysqli_query($conn, "SELECT COUNT(p.id) AS total_kasus, IFNULL(SUM(j.poin), 0) AS total_poin 
                                  FROM pelanggaran p 
                                  JOIN jenis_pelanggaran j ON p.id_jenis = j.id 
                                  WHERE p.id_siswa = '$id_siswa'");
    $sum = mysqli_fetch_assoc($q_sum);
    $total_kasus = $sum['total_kasus'] ?? 0;
    $total_poin  = $sum['total_poin'] ?? 0;

} else {
    // ---- MODE SEMUA (Berdasarkan ID Pelanggaran - RINGKAS) ----
    $q_kasus = mysqli_query($conn, "SELECT p.id AS id_kasus, p.tanggal, p.keterangan, j.nama_pelanggaran, j.poin, u.nama_lengkap AS petugas, 
                                          s.id AS id_siswa, s.nis, s.nama AS nama_siswa, s.jenis_kelamin, k.nama_kelas
                                   FROM pelanggaran p
                                   JOIN siswa s ON p.id_siswa = s.id
                                   JOIN jenis_pelanggaran j ON p.id_jenis = j.id
                                   LEFT JOIN kelas k ON s.id_kelas = k.id
                                   LEFT JOIN users u ON p.id_user = u.id
                                   WHERE p.id = '$id'");
    
    $d_kasus_single = mysqli_fetch_assoc($q_kasus);

    if ($d_kasus_single) {
        $siswa = [
            'nama' => $d_kasus_single['nama_siswa'],
            'nis' => $d_kasus_single['nis'],
            'nama_kelas' => $d_kasus_single['nama_kelas'],
            'jenis_kelamin' => $d_kasus_single['jenis_kelamin'] ?? '-'
        ];

        $total_kasus = 1;
        $total_poin  = $d_kasus_single['poin'];
        
        // Data tunggal kasus untuk tabel
        $data_tabel = [$d_kasus_single];
    } else {
        $siswa = null;
        $total_kasus = 0;
        $total_poin  = 0;
        $data_tabel  = [];
    }
}
?>

<div class="container-fluid py-3">

    <!-- HEADER DAN TOMBOL AKSI ATAS -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="fas fa-info-circle text-info me-2"></i>Detail Rekap Pelanggaran
                </h5>
                <small class="text-muted">Informasi riwayat dan akumulasi poin pelanggaran siswa.</small>
            </div>
            
            <div class="d-flex gap-2 align-items-center">
                <a href="index.php?page=pelanggaran_detail&id=<?= urlencode($id) ?>&source=<?= urlencode($source) ?>&action=cetak_pdf_detail" target="_blank" class="btn btn-danger btn-sm px-3 shadow-sm">
                    <i class="fas fa-file-pdf me-1"></i> Cetak PDF
                </a>
                <a href="index.php?page=pelanggaran_detail&id=<?= urlencode($id) ?>&source=<?= urlencode($source) ?>&action=export_excel_detail" class="btn btn-success btn-sm px-3 shadow-sm">
                    <i class="fas fa-file-excel me-1"></i> Simpan Excel
                </a>

                <a href="index.php?page=pelanggaran&view=<?= urlencode($source) ?>" class="btn btn-light btn-sm border px-3 shadow-sm">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <?php if ($siswa): ?>
        <div class="row g-3">
            
            <!-- KOLOM KIRI: INFORMASI AKADEMIK SISWA -->
            <div class="col-lg-4 col-12">
                <div class="card border-0 shadow-sm rounded-3 bg-white">
                    <div class="card-header bg-dark text-white fw-bold py-2 px-3">
                        Informasi Akademik Siswa
                    </div>
                    <div class="card-body p-3">
                        <table class="table table-borderless table-sm mb-0 align-middle">
                            <tr>
                                <td width="38%" class="text-secondary">Nama Lengkap</td>
                                <td width="5%">:</td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($siswa['nama'] ?? '-') ?></td>
                            </tr>
                            <tr>
                                <td class="text-secondary">NIS / NISN</td>
                                <td>:</td>
                                <td class="text-secondary font-monospace"><?= htmlspecialchars($siswa['nis'] ?? '-') ?></td>
                            </tr>
                            <tr>
                                <td class="text-secondary">Kelas</td>
                                <td>:</td>
                                <td><span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($siswa['nama_kelas'] ?? '-') ?></span></td>
                            </tr>
                            <tr>
                                <td class="text-secondary">Jenis Kelamin</td>
                                <td>:</td>
                                <td class="text-dark"><?= htmlspecialchars($siswa['jenis_kelamin'] ?? '-') ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN: STATISTIK & TABEL LOG RIWAYAT -->
            <div class="col-lg-8 col-12">
                <!-- STATISTIK POIN & KASUS -->
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="card border-0 shadow-sm bg-primary text-white rounded-3 p-3">
                            <small class="text-uppercase fw-semibold opacity-75">TOTAL KASUS</small>
                            <h3 class="fw-bold mb-0 mt-1"><?= $total_kasus ?> <span class="fs-6 fw-normal">Kejadian</span></h3>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card border-0 shadow-sm bg-danger text-white rounded-3 p-3">
                            <small class="text-uppercase fw-semibold opacity-75">AKUMULASI POIN</small>
                            <h3 class="fw-bold mb-0 mt-1">+<?= $total_poin ?> <span class="fs-6 fw-normal">Poin</span></h3>
                        </div>
                    </div>
                </div>

                <!-- CARD TABEL LOG RIWAYAT -->
                <div class="card border-0 shadow-sm rounded-3 bg-white">
                    <div class="card-header bg-danger text-white fw-bold py-2 px-3">
                        <i class="fas fa-history me-1"></i> Data Record Pelanggaran Terbuku
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                                <thead class="table-dark">
                                    <tr>
                                        <?php if ($source == 'pengelompokan'): ?>
                                            <!-- HEADER TABEL UNTUK MODE PENGELOMPOKAN (KOMPLEKS) -->
                                            <th width="4%" class="text-center">No</th>
                                            <th width="12%">Tanggal</th>
                                            <th width="12%">NIS</th>
                                            <th width="16%">Nama Siswa</th>
                                            <th width="10%">Kelas</th>
                                            <th width="12%">Petugas</th>
                                            <th>Jenis Pelanggaran</th>
                                            <th>Bentuk & Keterangan Pelanggaran</th>
                                            <th width="8%" class="text-center">Poin</th>
                                            <th width="8%">Sanksi</th>
                                        <?php else: ?>
                                            <!-- HEADER TABEL UNTUK MODE SEMUA (RINGKAS) -->
                                            <th width="5%" class="text-center">No</th>
                                            <th width="14%">Tanggal</th>
                                            <th width="15%">Petugas</th>
                                            <th width="22%">Jenis Pelanggaran</th>
                                            <th>Bentuk & Keterangan Pelanggaran</th>
                                            <th width="10%" class="text-center">Poin</th>
                                            <th width="10%">Sanksi</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($data_tabel)): ?>
                                        <?php $no_l = 1; foreach ($data_tabel as $l_row): ?>
                                            <tr>
                                                <td class="text-center"><?= $no_l++ ?></td>
                                                <td><?= date('d/m/Y', strtotime($l_row['tanggal'])) ?></td>

                                                <?php if ($source == 'pengelompokan'): ?>
                                                    <!-- ISI KOLOM KHUSUS MODE PENGELOMPOKAN -->
                                                    <td><span class="font-monospace text-secondary"><?= htmlspecialchars($l_row['nis'] ?? '-') ?></span></td>
                                                    <td class="fw-bold text-dark"><?= htmlspecialchars($l_row['nama_siswa'] ?? '-') ?></td>
                                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($l_row['nama_kelas'] ?? '-') ?></span></td>
                                                <?php endif; ?>

                                                <td><small class="text-secondary fw-semibold"><?= htmlspecialchars($l_row['petugas'] ?? '-') ?></small></td>
                                                <td class="fw-bold text-dark"><?= htmlspecialchars($l_row['nama_pelanggaran']) ?></td>
                                                <td><small class="text-muted d-block"><?= htmlspecialchars($l_row['keterangan'] ?: '-') ?></small></td>
                                                <td class="text-center">
                                                    <span class="badge bg-danger rounded-pill px-2 py-1">+<?= $l_row['poin'] ?></span>
                                                </td>
                                                <td><small class="text-muted">-</small></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="<?= ($source == 'pengelompokan') ? '10' : '7' ?>" class="text-center text-muted py-3">
                                                Tidak ada data riwayat.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-3 bg-white p-4 text-center">
            <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
            <h5 class="fw-bold text-dark">Data Tidak Ditemukan</h5>
            <p class="text-muted mb-0">Record pelanggaran atau data siswa tidak ditemukan dalam database.</p>
        </div>
    <?php endif; ?>

</div>