<?php
// 1. Sertakan file koneksi
if (file_exists('koneksi.php')) {
    include_once 'koneksi.php';
} elseif (file_exists('config.php')) {
    include_once 'config.php';
}

// 2. Normalisasi Nama Variabel Koneksi
if (!isset($conn) && isset($koneksi)) {
    $conn = $koneksi;
}

// Ambil ID Target dan asal View dari URL
$id_target = isset($_GET['id']) ? intval($_GET['id']) : 0;
$from_view = isset($_GET['from_view']) ? $_GET['from_view'] : 'pengelompokan';

// =========================================================================
// LOGIKA SIMPAN PENGURANGAN POIN (PROSES DARI MODAL)
// =========================================================================
if (isset($_POST['simpan_pengurangan'])) {
    $id_pelanggaran = intval($_POST['id_pelanggaran']);
    $id_siswa       = intval($_POST['id_siswa']);
    $kegiatan      = mysqli_real_escape_string($conn, $_POST['kegiatan_pembinaan']);
    $poin_kurang   = intval($_POST['jumlah_poin_kurang']);
    $tanggal       = mysqli_real_escape_string($conn, $_POST['tanggal']);
    
    // Ambil ID User dari Session LOGIN (jika ada), jika tidak gunakan ID dari form atau default 1
    if (isset($_SESSION['id_user'])) {
        $id_user = intval($_SESSION['id_user']);
    } elseif (isset($_SESSION['user_id'])) {
        $id_user = intval($_SESSION['user_id']);
    } else {
        // Jika form mengirim string (seperti 'bk'), konversi nilai input menjadi ID berupa angka
        $id_user = is_numeric($_POST['petugas']) ? intval($_POST['petugas']) : 1; 
    }

    if (!empty($kegiatan) && $poin_kurang > 0 && !empty($tanggal)) {
        
        // Mulai Transaksi Database
        mysqli_begin_transaction($conn);

        try {

          // 1. Simpan Catatan / Riwayat Pengurangan Poin (Disesuaikan dengan struktur tabel poin_pengurang)
            $q_insert = "INSERT INTO poin_pengurang (id_siswa, kegiatan, jumlah_pengurang, tanggal, id_user) 
                         VALUES ('$id_siswa', '$kegiatan', '$poin_kurang', '$tanggal', '$id_user')";
            
            mysqli_query($conn, $q_insert);

            // 2. Potong Nilai Poin pada Jenis Pelanggaran
            $q_update = "UPDATE pelanggaran p 
                         JOIN jenis_pelanggaran j ON p.id_jenis = j.id 
                         SET j.poin = GREATEST(0, j.poin - $poin_kurang) 
                         WHERE p.id = '$id_pelanggaran'";
            mysqli_query($conn, $q_update);

            // Commit transaksi
            mysqli_commit($conn);

            echo "<script>
                    alert('Berhasil! Poin siswa telah berkurang sebesar $poin_kurang poin.'); 
                    window.location='index.php?page=pelanggaran_detail&id=$id_siswa&from_view=$from_view';
                  </script>";
            exit;

        } catch (Exception $e) {
            // Rollback jika query gagal
            mysqli_rollback($conn);
            echo "<script>alert('Gagal mengurangi poin: " . addslashes($e->getMessage()) . "');</script>";
        }
    } else {
        echo "<script>alert('Harap isi semua data pengurangan poin dengan benar!');</script>";
    }
}

// =========================================================================
// KONDISI 1: DETAIL DARI MENU "PENGELOMPOKAN SISWA"
// $id_target di sini adalah ID SISWA (s.id)
// =========================================================================
if ($from_view == 'pengelompokan'):

    // 1. Ambil Data Siswa & Akumulasi Poin
    $q_siswa = mysqli_query($conn, "SELECT s.id, s.nis, s.nama AS nama_siswa, s.no_hp, k.nama_kelas,
                                           COUNT(p.id) AS total_kasus, 
                                           IFNULL(SUM(j.poin), 0) AS total_poin
                                    FROM siswa s
                                    LEFT JOIN kelas k ON s.id_kelas = k.id
                                    LEFT JOIN pelanggaran p ON p.id_siswa = s.id
                                    LEFT JOIN jenis_pelanggaran j ON p.id_jenis = j.id
                                    WHERE s.id = '$id_target'
                                    GROUP BY s.id");

    $siswa = mysqli_fetch_assoc($q_siswa);

    if (!$siswa) {
        echo "<script>alert('Data siswa tidak ditemukan!'); window.location='index.php?page=pelanggaran&view=pengelompokan';</script>";
        exit;
    }

    $total_poin = (int) $siswa['total_poin'];

    // Penentuan Sanksi Berdasarkan Poin Kumulatif
    $sanksi = "Belum Ada Sanksi (Siswa Berkepribadian Baik)";
    $badge_sanksi = "bg-success";

    if ($total_poin >= 100) {
        $sanksi = "Dikembalikan kepada Orang Tua / Wali (Skorsing Permanen / Dikeluarkan)";
        $badge_sanksi = "bg-danger";
    } elseif ($total_poin >= 75) {
        $sanksi = "Skorsing Sekolah selama 3 Hari + Pemanggilan Orang Tua ke-3";
        $badge_sanksi = "bg-danger";
    } elseif ($total_poin >= 50) {
        $sanksi = "Surat Peringatan II (SP 2) + Pemanggilan Orang Tua ke-2";
        $badge_sanksi = "bg-warning text-dark";
    } elseif ($total_poin >= 25) {
        $sanksi = "Surat Peringatan I (SP 1) + Pemanggilan Orang Tua ke-1";
        $badge_sanksi = "bg-warning text-dark";
    } elseif ($total_poin >= 10) {
        $sanksi = "Peringatan Lisan & Pembinaan oleh Guru BK / Wali Kelas";
        $badge_sanksi = "bg-info text-white";
    }

    // 2. Ambil Semua Rincian Kasus Siswa
    $q_detail = mysqli_query($conn, "SELECT p.id, p.tanggal, p.keterangan, j.nama_pelanggaran, j.poin, u.nama_lengkap AS petugas
                                     FROM pelanggaran p
                                     JOIN jenis_pelanggaran j ON p.id_jenis = j.id
                                     LEFT JOIN users u ON p.id_user = u.id
                                     WHERE p.id_siswa = '$id_target'
                                     ORDER BY p.tanggal DESC, p.id DESC");
?>

<style>
    @media print {
        /* Sembunyikan elemen navigasi & tombol yang tidak perlu */
        .no-print, .btn, .modal, nav, header, sidebar, .main-header, .main-sidebar, .card-header {
            display: none !important;
        }
        /* Sembunyikan kolom Aksi di tabel saat dicetak */
        .col-aksi, td:last-child, th:last-child {
            display: none !important;
        }
        body {
            background: #fff !important;
            color: #000 !important;
            font-size: 11pt;
            margin: 0;
            padding: 0;
        }
        .container-fluid {
            padding: 0 !important;
        }
        .card {
            border: 1px solid #333 !important;
            box-shadow: none !important;
            margin-bottom: 15px !important;
        }
        .area-cetak-header {
            display: block !important;
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .table {
            border-collapse: collapse !important;
            width: 100% !important;
        }
        .table th, .table td {
            border: 1px solid #333 !important;
            padding: 5px 8px !important;
        }
        .area-ttd {
            display: block !important;
            margin-top: 30px;
            float: right;
            width: 200px;
            text-align: center;
        }
        @page {
            size: A4 portrait;
            margin: 15mm;
        }
    }
    /* Sembunyikan Header Cetak dan TTD pada Tampilan Normal Browser */
    .area-cetak-header, .area-ttd {
        display: none;
    }
</style>

<div class="container-fluid px-4 py-3">

    <div class="area-cetak-header">
        <h3 style="margin:0; text-transform:uppercase;">REKAPITULASI PELANGGARAN SISWA</h3>
        <p style="margin:2px 0 0 0; font-size:10pt; color:#444;">Laporan Bimbingan Konseling (BK) & Kesiswaan</p>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <div>
            <h4 class="fw-bold mb-0 text-dark"><i class="fas fa-info-circle text-info me-2"></i>Detail Rekap Pelanggaran</h4>
            <p class="text-muted small mb-0">Informasi riwayat dan akumulasi poin pelanggaran siswa.</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-danger btn-sm px-3">
                <i class="fas fa-file-pdf me-1"></i> Cetak PDF
            </button>
            
            <a href="index.php?page=pelanggaran&view=<?= isset($from_view) ? $from_view : 'pengelompokan' ?>" class="btn btn-light btn-sm border px-3">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-dark text-white fw-bold py-2">
                    Informasi Akademik Siswa
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td width="35%" class="text-muted">Nama Lengkap</td>
                            <td width="5%">:</td>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($siswa['nama_siswa'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">NIS / NISN</td>
                            <td>:</td>
                            <td><?= htmlspecialchars($siswa['nis'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Kelas</td>
                            <td>:</td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($siswa['nama_kelas'] ?? '-') ?></span></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-7 d-flex flex-column justify-content-between gap-2">
            <div class="row g-2">
                <div class="col-6">
                    <div class="card shadow-sm border-0 text-white h-100" style="background-color: #1f0777;">
                        <div class="card-body py-3">
                            <small class="text-uppercase fw-semibold" style="letter-spacing: 0.5px;">TOTAL KASUS</small>
                            <div class="fs-3 fw-bold mt-1"><?= $siswa['total_kasus'] ?> <span class="fs-6 fw-normal">Kejadian</span></div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card shadow-sm border-0 text-white h-100" style="background-color: #7c020e;">
                        <div class="card-body py-3">
                            <small class="text-uppercase fw-semibold" style="letter-spacing: 0.5px;">AKUMULASI POIN</small>
                            <div class="fs-3 fw-bold mt-1">+<?= $siswa['total_poin'] ?> <span class="fs-6 fw-normal">Poin</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 text-white" style="background-color: #ffc107ab; color: #212529 !important;">
                <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-uppercase fw-bold text-dark d-block" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                <i class="fas fa-gavel me-1"></i> Rekomendasi Sanksi Siswa:
                            </small>
                            <div class="fw-bold text-dark fs-6 mt-1">
                                <?= $sanksi ?>
                            </div>
                        </div>
                        <span class="badge <?= $badge_sanksi ?> px-2 py-1" style="font-size: 0.75rem;">
                            <?= $total_poin ?> Pts
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header text-white fw-bold py-2" style="background-color: #970816;">
            <i class="fas fa-history me-1"></i> Data Record Pelanggaran Terbuku
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="table-dark">
                        <tr>
                            <th width="4%" class="text-center">No</th>
                            <th width="11%">Tanggal</th>
                            <th width="12%">NIS</th>
                            <th width="18%">Nama Siswa</th>
                            <th width="8%">Kelas</th>
                            <th width="12%">Petugas</th>
                            <th>Jenis Pelanggaran</th>
                            <th width="7%" class="text-center">Poin</th>
                            <th width="14%" class="text-center col-aksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($q_detail && mysqli_num_rows($q_detail) > 0): $no = 1; while($row = mysqli_fetch_assoc($q_detail)): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                <td class="text-secondary"><?= htmlspecialchars($siswa['nis'] ?? '-') ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($siswa['nama_siswa'] ?? '-') ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($siswa['nama_kelas'] ?? '-') ?></span></td>
                                <td><small class="text-secondary"><?= htmlspecialchars($row['petugas'] ?? 'guru umum') ?></small></td>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_pelanggaran']) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-danger rounded-pill px-2 py-1">+<?= $row['poin'] ?></span>
                                </td>
                                <td class="text-center col-aksi">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="index.php?page=pelanggaran_edit&id=<?= $row['id'] ?>" class="btn btn-warning btn-sm text-dark" title="Edit Pelanggaran">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalKurangPoin<?= $row['id'] ?>" title="Pengurangan Poin">
                                            <i class="fas fa-minus-circle"></i> - Poin
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <div class="modal fade" id="modalKurangPoin<?= $row['id'] ?>" tabindex="-1" aria-labelledby="modalLabel<?= $row['id'] ?>" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header bg-success text-white py-2">
                                            <h6 class="modal-title fw-bold" id="modalLabel<?= $row['id'] ?>">
                                                <i class="fas fa-minus-circle me-1"></i> Form Pengurangan Poin Siswa
                                            </h6>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="" method="POST">
                                            <div class="modal-body text-start" style="font-size: 0.875rem;">
                                                <input type="hidden" name="id_pelanggaran" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="id_siswa" value="<?= $siswa['id'] ?>">

                                                <div class="bg-light p-2 rounded border mb-3">
                                                    <small class="d-block text-muted"><strong>Siswa:</strong> <?= htmlspecialchars($siswa['nama_siswa']) ?> (<?= htmlspecialchars($siswa['nis']) ?>)</small>
                                                    <small class="d-block text-muted"><strong>Pelanggaran:</strong> <?= htmlspecialchars($row['nama_pelanggaran']) ?> (+<?= $row['poin'] ?> Poin)</small>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Kegiatan Pembinaan / Positif <span class="text-danger">*</span></label>
                                                    <select name="kegiatan_pembinaan" class="form-select form-select-sm" required>
                                                        <option value="">-- Pilih Kegiatan Pembinaan --</option>
                                                        <option value="Membersihkan Lingkungan Sekolah / Perpustakaan">Membersihkan Lingkungan Sekolah / Perpustakaan</option>
                                                        <option value="Mengikuti Pembinaan Khusus Guru BK">Mengikuti Pembinaan Khusus Guru BK</option>
                                                        <option value="Setoran Hafalan / Kegiatan Keagamaan">Setoran Hafalan / Kegiatan Keagamaan</option>
                                                        <option value="Prestasi Akademik / Non-Akademik">Prestasi Akademik / Non-Akademik</option>
                                                        <option value="Tugas Tambahan Pembiasaan Disiplin">Tugas Tambahan Pembiasaan Disiplin</option>
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Jumlah Pengurangan Poin <span class="text-danger">*</span></label>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text bg-light text-danger fw-bold">-</span>
                                                        <input type="number" name="jumlah_poin_kurang" class="form-control" placeholder="Masukkan angka (contoh: 10)" min="1" max="<?= $row['poin'] ?>" required>
                                                        <span class="input-group-text bg-light">Poin</span>
                                                    </div>
                                                </div>

                                                <div class="row g-2 mb-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Tanggal <span class="text-danger">*</span></label>
                                                        <input type="date" name="tanggal" class="form-control form-select-sm" value="<?= date('Y-m-d') ?>" required>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Petugas / Pencatat <span class="text-danger">*</span></label>
                                                        <input type="text" name="petugas" class="form-control form-select-sm" placeholder="Nama Guru / BK" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer py-2">
                                                <button type="button" class="btn btn-light btn-sm border" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="simpan_pengurangan" class="btn btn-success btn-sm">
                                                    <i class="fas fa-save me-1"></i> Simpan Pengurangan
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">Belum ada record data pelanggaran terbuku.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="area-ttd">
        <p>Guru BK / Kesiswaan</p>
        <br><br><br>
        <p><strong>( ________________________ )</strong></p>
    </div>

</div>

<?php else: ?>
    <?php
    $q_semua_detail = mysqli_query($conn, "SELECT p.id, p.tanggal, p.keterangan, s.nis, s.nama AS nama_siswa, 
                                                   k.nama_kelas, j.nama_pelanggaran, j.poin, u.nama_lengkap AS petugas
                                           FROM pelanggaran p
                                           JOIN siswa s ON p.id_siswa = s.id
                                           JOIN jenis_pelanggaran j ON p.id_jenis = j.id
                                           LEFT JOIN kelas k ON s.id_kelas = k.id
                                           LEFT JOIN users u ON p.id_user = u.id
                                           WHERE p.id = '$id_target'");

    $d_semua = mysqli_fetch_assoc($q_semua_detail);

    if (!$d_semua) {
        echo "<script>alert('Data pelanggaran tidak ditemukan!'); window.location='index.php?page=pelanggaran&view=semua';</script>";
        exit;
    }
    ?>

<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-0">Detail Kejadian Pelanggaran</h4>
            <p class="text-muted small mb-0">Rincian data transaksi kasus pelanggaran siswa</p>
        </div>
        <a href="index.php?page=pelanggaran&view=semua" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <table class="table table-bordered align-middle mb-0">
                <tr>
                    <th width="25%" class="bg-light">Tanggal Kejadian</th>
                    <td><?= date('d/m/Y', strtotime($d_semua['tanggal'])) ?></td>
                </tr>
                <tr>
                    <th class="bg-light">NIS / NISN</th>
                    <td><?= htmlspecialchars($d_semua['nis'] ?? '-') ?></td>
                </tr>
                <tr>
                    <th class="bg-light">Nama Siswa</th>
                    <td class="fw-bold"><?= htmlspecialchars($d_semua['nama_siswa'] ?? '-') ?></td>
                </tr>
                <tr>
                    <th class="bg-light">Kelas</th>
                    <td><?= htmlspecialchars($d_semua['nama_kelas'] ?? '-') ?></td>
                </tr>
                <tr>
                    <th class="bg-light">Jenis Pelanggaran</th>
                    <td class="text-danger fw-semibold"><?= htmlspecialchars($d_semua['nama_pelanggaran'] ?? '-') ?></td>
                </tr>
                <tr>
                    <th class="bg-light">Tambahan Poin</th>
                    <td><span class="badge bg-danger">+<?= $d_semua['poin'] ?> Poin</span></td>
                </tr>
                <tr>
                    <th class="bg-light">Keterangan</th>
                    <td><?= htmlspecialchars($d_semua['keterangan'] ?: '-') ?></td>
                </tr>
                <tr>
                    <th class="bg-light">Petugas Mencatat</th>
                    <td><?= htmlspecialchars($d_semua['petugas'] ?? '-') ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>