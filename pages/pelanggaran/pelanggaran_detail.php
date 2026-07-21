<?php
/** @var mysqli $conn */

// Mencegah error ob_clean() saat cetak PDF / Excel
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';
$source = isset($_GET['source']) ? $_GET['source'] : 'semua';

// ==========================================
// 1. PROSES EXPORT EXCEL DETAIL (KHUSUS PENGELOMPOKAN)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'export_excel_detail') {
    if (ob_get_length()) ob_clean();

    if ($source == 'pengelompokan') {
        $q_excel = mysqli_query($conn, "SELECT p.tanggal, s.nis, s.nama AS nama_siswa, k.nama_kelas, j.nama_pelanggaran, j.poin, p.keterangan, u.nama_lengkap AS petugas 
                                         FROM pelanggaran p 
                                         JOIN siswa s ON p.id_siswa = s.id 
                                         JOIN jenis_pelanggaran j ON p.id_jenis = j.id 
                                         LEFT JOIN kelas k ON s.id_kelas = k.id 
                                         LEFT JOIN users u ON p.id_user = u.id
                                         WHERE p.id_siswa = '$id' 
                                         ORDER BY p.tanggal DESC");

        $filename = "Detail_Pelanggaran_Siswa_" . date('Ymd_His') . ".xls";
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo "<table border='1'>";
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
        echo "</table>";
    }
    exit;
}

// ==========================================
// 2. PROSES CETAK PDF DETAIL (KHUSUS PENGELOMPOKAN)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'cetak_pdf_detail') {
    if (ob_get_length()) ob_clean();

    if ($source == 'pengelompokan') {
        $q_siswa = mysqli_query($conn, "SELECT s.*, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.id_kelas = k.id WHERE s.id = '$id'");
        $d_siswa = mysqli_fetch_assoc($q_siswa);
        
        $q_cetak = mysqli_query($conn, "SELECT p.tanggal, j.nama_pelanggaran, j.poin, p.keterangan, u.nama_lengkap AS petugas 
                                        FROM pelanggaran p 
                                        JOIN jenis_pelanggaran j ON p.id_jenis = j.id 
                                        LEFT JOIN users u ON p.id_user = u.id
                                        WHERE p.id_siswa = '$id' 
                                        ORDER BY p.tanggal DESC");
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Cetak PDF - Rekap Pelanggaran Siswa</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body { background-color: #525659; font-family: 'Times New Roman', Times, serif; font-size: 11pt; margin: 0; padding: 20px 0; }
            .paper { background: #fff; width: 210mm; min-height: 297mm; margin: 0 auto; padding: 20mm 15mm; box-shadow: 0 0 10px rgba(0,0,0,0.5); box-sizing: border-box; }
            .garis-kop { border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 20px; }
            .table-pdf { width: 100%; border-collapse: collapse; margin-top: 15px; }
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
                        <td width="15%"><strong>NIS / NISN</strong></td><td width="2%">:</td><td width="33%"><?= htmlspecialchars($d_siswa['nis']) ?></td>
                        <td width="15%"><strong>Kelas</strong></td><td width="2%">:</td><td width="33%"><?= htmlspecialchars($d_siswa['nama_kelas'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td><strong>Nama Siswa</strong></td><td>:</td><td><strong><?= htmlspecialchars($d_siswa['nama']) ?></strong></td>
                        <td><strong>Tanggal Cetak</strong></td><td>:</td><td><?= date('d/m/Y') ?></td>
                    </tr>
                </table>
            <?php endif; ?>

            <table class="table-pdf">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="12%">Tanggal</th>
                        <th>Petugas</th>
                        <th>Jenis Pelanggaran</th>
                        <th>Bentuk & Keterangan Pelanggaran</th>
                        <th width="8%">Poin</th>
                        <th>Sanksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($q_cetak && mysqli_num_rows($q_cetak) > 0): $no = 1; while($r = mysqli_fetch_assoc($q_cetak)): ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td class="text-center"><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                            <td><?= htmlspecialchars($r['petugas'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['nama_pelanggaran']) ?></td>
                            <td><?= htmlspecialchars($r['keterangan'] ?: '-') ?></td>
                            <td class="text-center" style="color: red; font-weight: bold;">+<?= $r['poin'] ?></td>
                            <td class="text-center">-</td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="7" class="text-center py-3">Tidak ada data pelanggaran.</td></tr>
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
    }
    exit;
}

// ==========================================
// 3. AMBIL DATA SESUAI MODE
// ==========================================
if ($source == 'pengelompokan') {
    // Data Siswa
    $q_siswa = mysqli_query($conn, "SELECT s.*, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.id_kelas = k.id WHERE s.id = '$id'");
    $siswa = mysqli_fetch_assoc($q_siswa);

    // List Pelanggaran Siswa
    $q_list = mysqli_query($conn, "SELECT p.id AS id_kasus, p.tanggal, p.keterangan, j.nama_pelanggaran, j.poin, u.nama_lengkap AS petugas
                                   FROM pelanggaran p
                                   JOIN jenis_pelanggaran j ON p.id_jenis = j.id
                                   LEFT JOIN users u ON p.id_user = u.id
                                   WHERE p.id_siswa = '$id'
                                   ORDER BY p.tanggal DESC, p.id DESC");
    
    // Ringkasan Total Kasus & Total Poin
    $q_sum = mysqli_query($conn, "SELECT COUNT(p.id) AS total_kasus, IFNULL(SUM(j.poin), 0) AS total_poin 
                                  FROM pelanggaran p 
                                  JOIN jenis_pelanggaran j ON p.id_jenis = j.id 
                                  WHERE p.id_siswa = '$id'");
    $sum = mysqli_fetch_assoc($q_sum);
    $total_kasus = $sum['total_kasus'] ?? 0;
    $total_poin  = $sum['total_poin'] ?? 0;

} else {
    // Mode Semua (1 Kasus)
    $q_detail = mysqli_query($conn, "SELECT p.id AS id_kasus, p.tanggal, p.keterangan, s.nis, s.nama AS nama_siswa, 
                                            s.jenis_kelamin, k.nama_kelas, j.nama_pelanggaran, j.poin, u.nama_lengkap AS petugas
                                     FROM pelanggaran p
                                     JOIN siswa s ON p.id_siswa = s.id
                                     JOIN jenis_pelanggaran j ON p.id_jenis = j.id
                                     LEFT JOIN kelas k ON s.id_kelas = k.id
                                     LEFT JOIN users u ON p.id_user = u.id
                                     WHERE p.id = '$id'");
    $detail_kasus = mysqli_fetch_assoc($q_detail);
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
                <!-- TOMBOL CETAK PDF DAN SIMPAN EXCEL SEJAJAR (TAMPIL SEKALI KLIK) -->
                <?php if ($source == 'pengelompokan'): ?>
                    <a href="index.php?page=pelanggaran_detail&id=<?= $id ?>&source=<?= $source ?>&action=cetak_pdf_detail" target="_blank" class="btn btn-danger btn-sm px-3 shadow-sm">
                        <i class="fas fa-file-pdf me-1"></i> Cetak PDF
                    </a>
                    <a href="index.php?page=pelanggaran_detail&id=<?= $id ?>&source=<?= $source ?>&action=export_excel_detail" class="btn btn-success btn-sm px-3 shadow-sm">
                        <i class="fas fa-file-excel me-1"></i> Simpan Excel
                    </a>
                <?php endif; ?>

                <a href="index.php?page=pelanggaran&view=<?= $source ?>" class="btn btn-light btn-sm border px-3 shadow-sm">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <!-- TAMPILAN MODE PENGELOMPOKAN -->
    <?php if ($source == 'pengelompokan'): ?>
        <div class="row g-3">
            
            <!-- KOLOM KIRI: INFO AKADEMIK -->
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

            <!-- KOLOM KANAN: STATISTIK & TABEL DETAIL LENGKAP -->
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
                        <i class="fas fa-history me-1"></i> Log Riwayat Pelanggaran Terbuku
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="5%" class="text-center">No</th>
                                        <th width="14%">Tanggal</th>
                                        <th width="15%">Petugas</th>
                                        <th width="20%">Jenis Pelanggaran</th>
                                        <th>Bentuk & Keterangan Pelanggaran</th>
                                        <th width="10%" class="text-center">Poin</th>
                                        <th width="12%">Sanksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if (isset($q_list) && mysqli_num_rows($q_list) > 0): 
                                        $no_l = 1;
                                        while($l_row = mysqli_fetch_assoc($q_list)):
                                    ?>
                                            <tr>
                                                <td class="text-center"><?= $no_l++ ?></td>
                                                <td><?= date('d/m/Y', strtotime($l_row['tanggal'])) ?></td>
                                                <td><small class="text-secondary fw-semibold"><?= htmlspecialchars($l_row['petugas'] ?? '-') ?></small></td>
                                                <td class="fw-bold text-dark"><?= htmlspecialchars($l_row['nama_pelanggaran']) ?></td>
                                                <td><small class="text-muted d-block"><?= htmlspecialchars($l_row['keterangan'] ?: '-') ?></small></td>
                                                <td class="text-center">
                                                    <span class="badge bg-danger rounded-pill px-2 py-1">+<?= $l_row['poin'] ?></span>
                                                </td>
                                                <td><small class="text-muted">-</small></td>
                                            </tr>
                                    <?php 
                                        endwhile; 
                                    else:
                                        echo '<tr><td colspan="7" class="text-center text-muted py-3">Tidak ada log riwayat.</td></tr>';
                                    endif;
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    <!-- TAMPILAN MODE SEMUA (SATU KASUS) -->
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-body p-4">
                <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary">Informasi Detail Pelanggaran</h6>
                <?php if (isset($detail_kasus) && $detail_kasus): ?>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <small class="text-muted d-block">Tanggal Pelanggaran</small>
                            <span class="fw-bold text-dark"><?= date('d/m/Y', strtotime($detail_kasus['tanggal'])) ?></span>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">NIS / NISN</small>
                            <span class="font-monospace text-secondary"><?= htmlspecialchars($detail_kasus['nis']) ?></span>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Nama Siswa</small>
                            <span class="fw-bold text-primary"><?= htmlspecialchars($detail_kasus['nama_siswa']) ?></span>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Kelas</small>
                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($detail_kasus['nama_kelas'] ?? '-') ?></span>
                        </div>
                    </div>

                    <div class="row g-3 border-top pt-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Bentuk Pelanggaran</small>
                            <span class="fw-bold text-danger"><?= htmlspecialchars($detail_kasus['nama_pelanggaran']) ?></span>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted d-block">Poin Pelanggaran</small>
                            <span class="badge bg-danger rounded-pill px-3 py-2 fs-6">+<?= $detail_kasus['poin'] ?></span>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Petugas Input</small>
                            <span class="text-dark"><?= htmlspecialchars($detail_kasus['petugas'] ?? '-') ?></span>
                        </div>
                        <div class="col-12 mt-3">
                            <small class="text-muted d-block">Keterangan Tambahan</small>
                            <div class="p-3 bg-light rounded border text-secondary mt-1">
                                <?= htmlspecialchars($detail_kasus['keterangan'] ?: 'Tidak ada keterangan tambahan.') ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">Data detail tidak ditemukan.</div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div>