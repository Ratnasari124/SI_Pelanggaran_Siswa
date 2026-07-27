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
    
    if (isset($_SESSION['id_user'])) {
        $id_user = intval($_SESSION['id_user']);
    } elseif (isset($_SESSION['user_id'])) {
        $id_user = intval($_SESSION['user_id']);
    } else {
        $id_user = is_numeric($_POST['petugas']) ? intval($_POST['petugas']) : 1; 
    }

    if (!empty($kegiatan) && $poin_kurang > 0 && !empty($tanggal)) {
        mysqli_begin_transaction($conn);

        try {
            $q_insert = "INSERT INTO poin_pengurang (id_siswa, kegiatan, jumlah_pengurang, tanggal, id_user) 
                         VALUES ('$id_siswa', '$kegiatan', '$poin_kurang', '$tanggal', '$id_user')";
            mysqli_query($conn, $q_insert);

            $q_update = "UPDATE pelanggaran p 
                         JOIN jenis_pelanggaran j ON p.id_jenis = j.id 
                         SET j.poin = GREATEST(0, j.poin - $poin_kurang) 
                         WHERE p.id = '$id_pelanggaran'";
            mysqli_query($conn, $q_update);

            mysqli_commit($conn);

            echo "<script>
                    alert('Berhasil! Poin siswa telah berkurang sebesar $poin_kurang poin.'); 
                    window.location='index.php?page=pelanggaran_detail&id=$id_siswa&from_view=$from_view';
                  </script>";
            exit;

        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo "<script>alert('Gagal mengurangi poin: " . addslashes($e->getMessage()) . "');</script>";
        }
    } else {
        echo "<script>alert('Harap isi semua data pengurangan poin dengan benar!');</script>";
    }
}

// =========================================================================
// KONDISI 1: DETAIL DARI MENU "PENGELOMPOKAN SISWA"
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

    // =========================================================================
    // HANYA MENGUBAH PENENTUAN SANKSI BERDASARKAN TABEL `sanksi` INI
    // =========================================================================
    $q_sanksi = mysqli_query($conn, "SELECT nama_sanksi 
                                     FROM sanksi 
                                     WHERE min_poin <= $total_poin AND max_poin >= $total_poin 
                                     ORDER BY min_poin DESC 
                                     LIMIT 1");

    if ($q_sanksi && mysqli_num_rows($q_sanksi) > 0) {
        $data_sanksi = mysqli_fetch_assoc($q_sanksi);
        $sanksi = $data_sanksi['nama_sanksi'];
    } else {
        $sanksi = "Belum Ada Sanksi (Siswa Berkepribadian Baik)";
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
    /* Elemen khusus cetak disembunyikan di layar komputer biasa */
    .area-cetak-kop, .area-ttd-cetak {
        display: none;
    }

    /* Pengaturan Tampilan Khusus Cetak/PDF */
    @media print {
        /* Sembunyikan elemen navigasi & tombol saja */
        .no-print, .btn, .modal, nav, header, sidebar, .main-header, .main-sidebar, .col-aksi {
            display: none !important;
        }

        /* Pastikan elemen utama tetap tampil */
        body, html, .container-fluid, .row, .col-md-5, .col-md-7, .card, .card-body, .table-responsive, table {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            float: none !important;
            width: 100% !important;
        }

        body {
            background-color: #ffffff !important;
            color: #000000 !important;
            font-family: Arial, sans-serif !important;
            font-size: 11pt !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Kop Laporan */
        .area-cetak-kop {
            display: block !important;
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .area-cetak-kop h3 {
            margin: 0;
            font-weight: bold;
            font-size: 14pt;
        }

        .area-cetak-kop p {
            margin: 2px 0 0 0;
            font-size: 10pt;
        }

        /* Tampilan Card & Box di Kertas */
        .card {
            border: 1px solid #000 !important;
            box-shadow: none !important;
            margin-bottom: 15px !important;
            background: #fff !important;
        }

        .card-header {
            background-color: #f2f2f2 !important;
            color: #000 !important;
            border-bottom: 1px solid #000 !important;
            font-weight: bold;
        }

        /* Tampilan Tabel di Kertas */
        .table {
            width: 100% !important;
            border-collapse: collapse !important;
            margin-bottom: 15px !important;
        }

        .table th, .table td {
            border: 1px solid #000 !important;
            padding: 6px 8px !important;
            color: #000 !important;
        }

        .table-dark {
            background-color: #eee !important;
            color: #000 !important;
        }

        /* Sembunyikan kolom aksi saja dalam tabel */
        th.col-aksi, td.col-aksi {
            display: none !important;
        }

        /* Area Tanda Tangan */
        .area-ttd-cetak {
            display: flex !important;
            justify-content: space-between;
            margin-top: 30px;
            page-break-inside: avoid;
        }

        .box-ttd {
            text-align: center;
            width: 40%;
        }

        @page {
            size: A4 portrait;
            margin: 1.5cm;
        }
    }
</style>

<div class="container-fluid px-4 py-3" id="area-cetak-utama">

    <div class="area-cetak-kop">
        <h3>LEMBAR REKAPITULASI CATATAN PELANGGARAN SISWA</h3>
        <p>Laporan Resmi Bimbingan Konseling (BK) & Kedisiplinan Siswa</p>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <div>
            <h4 class="fw-bold mb-0 text-dark"><i class="fas fa-info-circle text-info me-2"></i>Detail Rekap Pelanggaran</h4>
            <p class="text-muted small mb-0">Informasi riwayat dan akumulasi poin pelanggaran siswa.</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-danger btn-sm px-3">
                <i class="fas fa-print me-1"></i> Cetak / Simpan PDF
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
                    <div class="card shadow-sm border-0 text-white h-100" style="background-color: #3313a7;">
                        <div class="card-body py-3">
                            <small class="text-uppercase fw-semibold">TOTAL KASUS</small>
                            <div class="fs-3 fw-bold mt-1"><?= $siswa['total_kasus'] ?> <span class="fs-6 fw-normal">Kejadian</span></div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card shadow-sm border-0 text-white h-100" style="background-color: #a70415;">
                        <div class="card-body py-3">
                            <small class="text-uppercase fw-semibold">AKUMULASI POIN</small>
                            <div class="fs-3 fw-bold mt-1">+<?= $siswa['total_poin'] ?> <span class="fs-6 fw-normal">Poin</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 text-dark" style="background-color: #624ccf; border: 1px solid #1d1d9c !important;">
                <div class="card-body py-2 px-3">
                    <small class="text-uppercase fw-bold text-dark d-block" style="font-size: 0.7rem;">
                        <i class="fas fa-gavel me-1"></i> Rekomendasi Sanksi Siswa:
                    </small>
                    <div class="fw-bold text-dark fs-6 mt-1">
                        <?= htmlspecialchars($sanksi) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header text-white fw-bold py-2" style="background-color: #970816;">
            <i class="fas fa-history me-1"></i> Data Record Pelanggaran Terbuku
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="12%">Tanggal</th>
                            <th width="12%">NIS</th>
                            <th width="20%">Nama Siswa</th>
                            <th width="10%">Kelas</th>
                            <th width="12%">Petugas</th>
                            <th>Jenis Pelanggaran</th>
                            <th width="8%" class="text-center">Poin</th>
                            <th width="12%" class="text-center col-aksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($q_detail && mysqli_num_rows($q_detail) > 0): $no = 1; while($row = mysqli_fetch_assoc($q_detail)): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                <td><?= htmlspecialchars($siswa['nis'] ?? '-') ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($siswa['nama_siswa'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($siswa['nama_kelas'] ?? '-') ?></td>
                                <td><small><?= htmlspecialchars($row['petugas'] ?? 'guru umum') ?></small></td>
                                <td class="fw-semibold"><?= htmlspecialchars($row['nama_pelanggaran']) ?></td>
                                <td class="text-center">+<?= $row['poin'] ?></td>
                                <td class="text-center col-aksi">
                                    <div class="btn-group btn-group-sm">
                                        <a href="index.php?page=pelanggaran_edit&id=<?= $row['id'] ?>" class="btn btn-warning btn-sm text-dark">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalKurangPoin<?= $row['id'] ?>">
                                            <i class="fas fa-minus-circle"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
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

    <div class="area-ttd-cetak">
        <div class="box-ttd">
            <p>Mengetahui,<br>Wali Kelas</p>
            <br><br><br>
            <p><strong>( ____________________ )</strong></p>
        </div>
        <div class="box-ttd">
            <p>Tanggal Cetak: <?= date('d/m/Y') ?><br>Guru Bimbingan Konseling</p>
            <br><br><br>
            <p><strong>( ____________________ )</strong></p>
        </div>
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
    ?>

<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">Detail Kejadian Pelanggaran</h4>
        <a href="index.php?page=pelanggaran&view=semua" class="btn btn-secondary btn-sm">Kembali</a>
    </div>
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <table class="table table-bordered mb-0">
                <tr><th>Tanggal</th><td><?= date('d/m/Y', strtotime($d_semua['tanggal'])) ?></td></tr>
                <tr><th>NIS</th><td><?= htmlspecialchars($d_semua['nis'] ?? '-') ?></td></tr>
                <tr><th>Nama Siswa</th><td class="fw-bold"><?= htmlspecialchars($d_semua['nama_siswa'] ?? '-') ?></td></tr>
                <tr><th>Kelas</th><td><?= htmlspecialchars($d_semua['nama_kelas'] ?? '-') ?></td></tr>
                <tr><th>Jenis Pelanggaran</th><td class="text-danger fw-semibold"><?= htmlspecialchars($d_semua['nama_pelanggaran'] ?? '-') ?></td></tr>
                <tr><th>Poin</th><td>+<?= $d_semua['poin'] ?> Poin</td></tr>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>