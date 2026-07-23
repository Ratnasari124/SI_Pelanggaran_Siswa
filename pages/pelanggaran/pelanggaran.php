<?php
/** @var mysqli $conn */

// Mencegah error ob_clean() saat cetak PDF / Excel
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =========================================================================
// 1. MEKANISME SESSION KUNCI VIEW
// =========================================================================
if (isset($_GET['view']) && in_array($_GET['view'], ['pengelompokan', 'semua'])) {
    $_SESSION['last_view_pelanggaran'] = $_GET['view'];
}

// Hanya ambil view dari parameter URL (jika kosong, tampilkan halaman awal bersih)
$view = $_GET['view'] ?? '';

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$id_kelas_filter = isset($_GET['kelas']) ? mysqli_real_escape_string($conn, $_GET['kelas']) : '';

// ==========================================
// 2. PROSES EXPORT EXCEL (SEMUA PELANGGARAN)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'export_excel_semua') {
    if (ob_get_length()) ob_clean();

    $where = "WHERE 1=1";
    if (!empty($search)) {
        $where .= " AND (s.nama LIKE '%$search%' OR s.nis LIKE '%$search%' OR j.nama_pelanggaran LIKE '%$search%')";
    }
    if (!empty($id_kelas_filter)) {
        $where .= " AND s.id_kelas = '$id_kelas_filter'";
    }

    $q_excel = mysqli_query($conn, "SELECT p.tanggal, s.nis, s.nama AS nama_siswa, k.nama_kelas, j.nama_pelanggaran, j.poin, p.keterangan, u.nama_lengkap AS petugas
                                     FROM pelanggaran p
                                     JOIN siswa s ON p.id_siswa = s.id
                                     JOIN jenis_pelanggaran j ON p.id_jenis = j.id
                                     LEFT JOIN kelas k ON s.id_kelas = k.id
                                     LEFT JOIN users u ON p.id_user = u.id
                                     $where
                                     ORDER BY p.tanggal DESC, p.id DESC");

    $filename = "Laporan_Semua_Pelanggaran_" . date('Ymd_His') . ".xls";
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "<table border='1'>";
    echo "<tr style='background-color:#f2f2f2; font-weight:bold;'>
            <th>No</th><th>Tanggal</th><th>NIS</th><th>Nama Siswa</th><th>Kelas</th><th>Petugas</th><th>Jenis Pelanggaran</th><th>Keterangan</th><th>Poin</th>
          </tr>";

    if ($q_excel && mysqli_num_rows($q_excel) > 0) {
        $no = 1;
        while ($r = mysqli_fetch_assoc($q_excel)) {
            echo "<tr>
                    <td align='center'>" . $no++ . "</td>
                    <td align='center'>" . date('d/m/Y', strtotime($r['tanggal'])) . "</td>
                    <td>'" . htmlspecialchars($r['nis']) . "</td>
                    <td>" . htmlspecialchars($r['nama_siswa']) . "</td>
                    <td align='center'>" . htmlspecialchars($r['nama_kelas'] ?? '-') . "</td>
                    <td>" . htmlspecialchars($r['petugas'] ?? '-') . "</td>
                    <td>" . htmlspecialchars($r['nama_pelanggaran']) . "</td>
                    <td>" . htmlspecialchars($r['keterangan'] ?: '-') . "</td>
                    <td align='center'>+" . $r['poin'] . "</td>
                  </tr>";
        }
    }
    echo "</table>";
    exit;
}

// ==========================================
// 3. PROSES CETAK PDF (SEMUA PELANGGARAN)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'cetak_pdf_semua') {
    if (ob_get_length()) ob_clean();

    $where = "WHERE 1=1";
    if (!empty($search)) {
        $where .= " AND (s.nama LIKE '%$search%' OR s.nis LIKE '%$search%' OR j.nama_pelanggaran LIKE '%$search%')";
    }
    if (!empty($id_kelas_filter)) {
        $where .= " AND s.id_kelas = '$id_kelas_filter'";
    }

    $q_cetak = mysqli_query($conn, "SELECT p.tanggal, s.nis, s.nama AS nama_siswa, k.nama_kelas, j.nama_pelanggaran, j.poin, p.keterangan, u.nama_lengkap AS petugas
                                    FROM pelanggaran p
                                    JOIN siswa s ON p.id_siswa = s.id
                                    JOIN jenis_pelanggaran j ON p.id_jenis = j.id
                                    LEFT JOIN kelas k ON s.id_kelas = k.id
                                    LEFT JOIN users u ON p.id_user = u.id
                                    $where
                                    ORDER BY p.tanggal DESC, p.id DESC");
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Cetak PDF - Laporan Semua Pelanggaran</title>
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
                <h3 class="fw-bold mb-1" style="font-size: 16pt;">LAPORAN SEMUA PELANGGARAN SISWA</h3>
                <h5 class="mb-0 text-uppercase" style="font-size: 12pt;">SISTEM INFORMASI BIMBINGAN KONSELING (BK)</h5>
            </div>

            <table class="table-pdf">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="12%">Tanggal</th>
                        <th>NIS</th>
                        <th>Nama Siswa</th>
                        <th width="10%">Kelas</th>
                        <th>Jenis Pelanggaran</th>
                        <th width="8%">Poin</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($q_cetak && mysqli_num_rows($q_cetak) > 0): $no = 1; while($r = mysqli_fetch_assoc($q_cetak)): ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td class="text-center"><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                            <td class="text-center"><?= htmlspecialchars($r['nis']) ?></td>
                            <td><?= htmlspecialchars($r['nama_siswa']) ?></td>
                            <td class="text-center"><?= htmlspecialchars($r['nama_kelas'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['nama_pelanggaran']) ?></td>
                            <td class="text-center" style="color: red; font-weight: bold;">+<?= $r['poin'] ?></td>
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
    exit;
}

// ==========================================
// 4. TAMPILAN HALAMAN UTAMA PELANGGARAN
// ==========================================
?>

<div class="container-fluid px-4 py-4">

    <!-- HEADER: BANNER DROPDOWN PILIHAN MODE -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                    <i class="fas fa-exclamation-triangle text-warning"></i> Menu Log Pelanggaran
                </h5>
                <p class="text-muted small mb-0">Pilih mode pengelompokan data untuk memulai pengelolaan.</p>
            </div>
            
            <!-- DROPDOWN MODE TAMPILAN -->
            <div class="d-flex align-items-center gap-2">
                <label class="form-label small mb-0 fw-semibold text-secondary">Mode Tampilan :</label>
                <select class="form-select form-select-sm fw-bold border-primary text-primary" style="width: auto;" onchange="location = this.value;">
                    <option value="index.php?page=pelanggaran&view=" <?= empty($view) ? 'selected' : '' ?>>-- Pilih Mode Tampilan --</option>
                    <option value="index.php?page=pelanggaran&view=pengelompokan" <?= $view == 'pengelompokan' ? 'selected' : '' ?>>Pengelompokan Siswa</option>
                    <option value="index.php?page=pelanggaran&view=semua" <?= $view == 'semua' ? 'selected' : '' ?>>Semua Pelanggaran</option>
                </select>
            </div>
        </div>
    </div>

    <!-- AREA KONTEN UTAMA -->
    <?php if (empty($view)): ?>
        <!-- TAMPILAN KOSONG / AWAL SAAT KLIK MENU CATAT PELANGGARAN -->
        <div class="card border-0 shadow-sm rounded-3 bg-white p-5 text-center my-2">
            <div class="py-5">
                <i class="fas fa-filter text-secondary mb-3" style="font-size: 3.5rem; opacity: 0.3;"></i>
                <h5 class="fw-bold text-dark">Silakan Pilih Mode Tampilan</h5>
                <p class="text-muted small mb-0 mx-auto" style="max-width: 450px;">
                    Pilih mode <strong>Pengelompokan Siswa</strong> atau <strong>Semua Pelanggaran</strong> pada dropdown di atas untuk menampilkan data rekapitulasi.
                </p>
            </div>
        </div>
    <?php else: ?>
        <!-- TAMPILAN DATA SETELAH MODE DIPILIH (PENGELOMPOKAN / SEMUA) -->
        <div class="card border-0 shadow-sm rounded-3 bg-white p-3">

            <!-- SEARCH BAR & TOMBOL TAMBAH -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <form method="GET" action="index.php" class="d-flex gap-2" style="max-width: 450px; width: 100%;">
                    <input type="hidden" name="page" value="pelanggaran">
                    <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
                    
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Ketik NAMA DEPAN siswa..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-primary px-3">Cari</button>
                        <?php if (!empty($search)): ?>
                            <a href="index.php?page=pelanggaran&view=<?= htmlspecialchars($view) ?>" class="btn btn-outline-secondary">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="d-flex gap-2">
                    <?php if ($view == 'semua'): ?>
                        <a href="index.php?page=pelanggaran&view=semua&action=cetak_pdf_semua&search=<?= urlencode($search) ?>" target="_blank" class="btn btn-danger btn-sm px-3">
                            <i class="fas fa-file-pdf me-1"></i> Cetak PDF
                        </a>
                        <a href="index.php?page=pelanggaran&view=semua&action=export_excel_semua&search=<?= urlencode($search) ?>" class="btn btn-success btn-sm px-3">
                            <i class="fas fa-file-excel me-1"></i> Simpan Excel
                        </a>
                    <?php endif; ?>

                    <a href="index.php?page=pelanggaran_tambah&from_view=<?= htmlspecialchars($view) ?>" class="btn btn-danger btn-sm px-3 fw-semibold">
                        <i class="fas fa-plus me-1"></i> Tambah
                    </a>
                </div>
            </div>

            <!-- TABEL MODE PENGELOMPOKAN SISWA -->
            <?php if ($view == 'pengelompokan'): ?>
                <?php
                if (!function_exists('formatNoWA')) {
                    function formatNoWA($nomor) {
                        $nomor = preg_replace('/[^0-9]/', '', $nomor);
                        if (substr($nomor, 0, 1) === '0') {
                            $nomor = '62' . substr($nomor, 1);
                        }
                        return $nomor;
                    }
                }

                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                $base_url = $protocol . $_SERVER['HTTP_HOST'] . str_replace(basename($_SERVER['SCRIPT_NAME']), "", $_SERVER['SCRIPT_NAME']);

                $where_kelompok = "WHERE 1=1";
                if (!empty($search)) {
                    $where_kelompok .= " AND (s.nama LIKE '%$search%' OR s.nis LIKE '%$search%')";
                }

                $q_kelompok = mysqli_query($conn, "SELECT 
                                                        s.id AS id_siswa, 
                                                        s.nis, 
                                                        s.nama AS nama_siswa, 
                                                        s.no_hp,
                                                        k.nama_kelas, 
                                                        COUNT(p.id) AS total_kasus, 
                                                        IFNULL(SUM(j.poin), 0) AS total_poin,
                                                        MAX(p.id) AS id_kasus_terakhir
                                                FROM siswa s
                                                JOIN pelanggaran p ON p.id_siswa = s.id
                                                JOIN jenis_pelanggaran j ON p.id_jenis = j.id
                                                LEFT JOIN kelas k ON s.id_kelas = k.id
                                                $where_kelompok
                                                GROUP BY s.id
                                                ORDER BY total_poin DESC");
                ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.875rem;">
                        <thead class="table-dark" style="background-color: #212529;">
                            <tr>
                                <th width="4%" class="text-center">No</th>
                                <th width="15%">NIS / NISN</th>
                                <th width="20%">Nama Siswa</th>
                                <th width="10%">Kelas</th>
                                <th width="12%" class="text-center">Total Kasus</th>
                                <th width="12%" class="text-center">Akumulasi Poin</th>
                                <th width="27%" class="text-center">Aksi & Cetak</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($q_kelompok && mysqli_num_rows($q_kelompok) > 0): $no = 1; while($row = mysqli_fetch_assoc($q_kelompok)): ?>
                                <?php 
                                    $no_wa = formatNoWA($row['no_hp'] ?? '');
                                    $has_wa = !empty($no_wa);
                                    
                                    $url_pdf_siswa = $base_url . "cetak_pelanggaran.php?id=" . $row['id_siswa'];

                                    $pesan = "Yth. Bapak/Ibu Orang Tua/Wali dari *{$row['nama_siswa']}*,\n\n";
                                    $pesan .= "Berikut kami sampaikan *Surat Rekapan Catatan Pelanggaran Siswa* resmi dari pihak sekolah.\n\n";
                                    $pesan .= "Silakan klik link di bawah ini untuk mengunduh / melihat dokumen resmi (PDF):\n";
                                    $pesan .= "📄 *Link PDF:* " . $url_pdf_siswa . "\n\n";
                                    $pesan .= "Mohon bantuan Bapak/Ibu untuk mendampingi dan memberikan pembinaan kepada putra/putrinya.\n\n";
                                    $pesan .= "Terima Kasih,\n";
                                    $pesan .= "*Tim Bimbingan Konseling (BK)*";

                                    $url_wa = "https://api.whatsapp.com/send?phone=" . $no_wa . "&text=" . urlencode($pesan);
                                ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td class="text-secondary"><?= htmlspecialchars($row['nis']) ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border px-2 py-1" style="font-size: 0.75rem;">
                                            <?= htmlspecialchars($row['nama_kelas'] ?? '-') ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-bold text-primary"><?= $row['total_kasus'] ?> Kasus</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger rounded-pill px-3 py-2" style="font-size: 0.8rem;">
                                            + <?= $row['total_poin'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1 flex-wrap justify-content-center">
                                            <!-- TOMBOL DETAIL SAJA -->
                                            <a href="index.php?page=pelanggaran_detail&id=<?= $row['id_siswa'] ?>&from_view=pengelompokan" class="btn btn-info btn-sm text-white px-2 py-1" style="font-size: 0.75rem; background-color: #0dcaf0; border: none;" title="Lihat Detail Rekap">
                                                <i class="fas fa-eye me-1"></i> Detail
                                            </a>

                                            <!-- DROPDOWN BUTTON CETAK / SEND WA -->
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-secondary btn-sm dropdown-toggle px-2 py-1" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 0.75rem;">
                                                    <i class="fas fa-print me-1"></i> Cetak / WA
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow" style="font-size: 0.85rem;">
                                                    <li>
                                                        <a class="dropdown-item" href="cetak_pelanggaran.php?id=<?= $row['id_siswa'] ?>" target="_blank">
                                                            <i class="fas fa-file-pdf text-danger me-2"></i> Cetak PDF Rekap
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <?php if ($has_wa): ?>
                                                            <a class="dropdown-item text-success" href="<?= $url_wa ?>" target="_blank">
                                                                <i class="fab fa-whatsapp me-2"></i> Kirim Link PDF via WA
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="dropdown-item text-muted disabled">
                                                                <i class="fab fa-whatsapp me-2"></i> No WA Tidak Ada
                                                            </span>
                                                        <?php endif; ?>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada rekap data pelanggaran siswa.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <!-- TABEL MODE SEMUA PELANGGARAN -->
            <?php else: ?>
                <?php
                $where_semua = "WHERE 1=1";
                if (!empty($search)) {
                    $where_semua .= " AND (s.nama LIKE '%$search%' OR s.nis LIKE '%$search%' OR j.nama_pelanggaran LIKE '%$search%')";
                }

                $q_semua = mysqli_query($conn, "SELECT p.id AS id_kasus, p.tanggal, p.keterangan, s.nis, s.nama AS nama_siswa, 
                                                       k.nama_kelas, j.nama_pelanggaran, j.poin, u.nama_lengkap AS petugas
                                                FROM pelanggaran p
                                                JOIN siswa s ON p.id_siswa = s.id
                                                JOIN jenis_pelanggaran j ON p.id_jenis = j.id
                                                LEFT JOIN kelas k ON s.id_kelas = k.id
                                                LEFT JOIN users u ON p.id_user = u.id
                                                $where_semua
                                                ORDER BY p.tanggal DESC, p.id DESC");
                ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.875rem;">
                        <thead class="table-dark">
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th width="12%">Tanggal</th>
                                <th>NIS</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Jenis Pelanggaran</th>
                                <th>Keterangan</th>
                                <th width="8%" class="text-center">Poin</th>
                                <th>Petugas</th>
                                <th width="10%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($q_semua && mysqli_num_rows($q_semua) > 0): $no = 1; while($row = mysqli_fetch_assoc($q_semua)): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                    <td class="text-secondary"><?= htmlspecialchars($row['nis']) ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></span></td>
                                    <td class="fw-semibold text-danger"><?= htmlspecialchars($row['nama_pelanggaran']) ?></td>
                                    <td><small class="text-muted"><?= htmlspecialchars($row['keterangan'] ?: '-') ?></small></td>
                                    <td class="text-center"><span class="badge bg-danger rounded-pill px-2 py-1">+<?= $row['poin'] ?></span></td>
                                    <td><small class="text-secondary"><?= htmlspecialchars($row['petugas'] ?? '-') ?></small></td>
                                    <!-- TOMBOL DETAIL SAJA -->
                                    <td class="text-center">
                                        <a href="index.php?page=pelanggaran_detail&id=<?= $row['id_kasus'] ?>&from_view=semua" class="btn btn-info btn-sm text-white px-3" title="Lihat Detail Kejadian">
                                            <i class="fas fa-eye me-1"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="10" class="text-center py-4 text-muted">Belum ada data pelanggaran.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>
    <?php endif; ?>

</div>