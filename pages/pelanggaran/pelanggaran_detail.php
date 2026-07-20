<?php
/** @var mysqli $conn */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mengambil ID dan Source (Sumber Menu asal)
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$source = isset($_GET['source']) ? $_GET['source'] : 'semua';

// Menentukan URL Kembali berdasarkan asal menu
$back_url = "index.php?page=pelanggaran&view=semua";
if ($source === 'pengelompokan') {
    $back_url = "index.php?page=pelanggaran&view=pengelompokan";
}

// 1. JIKA SUMBER DARI MENU 'PENGELOMPOKAN' (ID adalah ID Siswa)
if ($source === 'pengelompokan') {
    $sql_siswa = "SELECT s.*, k.nama_kelas 
                  FROM siswa s 
                  LEFT JOIN kelas k ON s.id_kelas = k.id 
                  WHERE s.id = '$id'";
    $q_siswa = mysqli_query($conn, $sql_siswa);
    $siswa = mysqli_fetch_assoc($q_siswa);

    if (!$siswa) {
        echo "<div class='alert alert-danger'>Data siswa tidak ditemukan.</div>";
        exit;
    }
    
    // Ambil semua riwayat pelanggaran milik siswa ini
    $sql_kasus = "SELECT p.id AS id_kasus, p.tanggal, p.keterangan, j.nama_pelanggaran, j.poin, u.nama_lengkap AS petugas
                  FROM pelanggaran p
                  JOIN jenis_pelanggaran j ON p.id_jenis = j.id
                  LEFT JOIN users u ON p.id_user = u.id
                  WHERE p.id_siswa = '$id'
                  ORDER BY p.tanggal DESC";
    $q_kasus = mysqli_query($conn, $sql_kasus);
} 
// 2. JIKA SUMBER DARI MENU 'SEMUA' (ID adalah ID Kasus Pelanggaran)
else {
    $sql_detail = "SELECT p.id AS id_kasus, p.tanggal, p.keterangan, s.id AS id_siswa, s.nis, s.nama AS nama_siswa, 
                          s.jenis_kelamin, k.nama_kelas, j.nama_pelanggaran, j.poin, u.nama_lengkap AS petugas
                   FROM pelanggaran p
                   JOIN siswa s ON p.id_siswa = s.id
                   JOIN jenis_pelanggaran j ON p.id_jenis = j.id
                   LEFT JOIN kelas k ON s.id_kelas = k.id
                   LEFT JOIN users u ON p.id_user = u.id
                   WHERE p.id = '$id'";
    $q_detail = mysqli_query($conn, $sql_detail);
    $detail = mysqli_fetch_assoc($q_detail);

    if (!$detail) {
        echo "<div class='alert alert-danger'>Data detail pelanggaran tidak ditemukan.</div>";
        exit;
    }

    // Menyamakan struktur data siswa agar bagian Informasi Akademik tetap tampil
    $siswa = [
        'id' => $detail['id_siswa'],
        'nis' => $detail['nis'],
        'nama' => $detail['nama_siswa'],
        'nama_kelas' => $detail['nama_kelas'],
        'jenis_kelamin' => $detail['jenis_kelamin']
    ];

    // Dari menu 'semua', fokus menampilkan 1 kasus terpilih ini (seperti tampilan awal Anda)
    $sql_kasus = "SELECT p.id AS id_kasus, p.tanggal, p.keterangan, j.nama_pelanggaran, j.poin, u.nama_lengkap AS petugas
                  FROM pelanggaran p
                  JOIN jenis_pelanggaran j ON p.id_jenis = j.id
                  LEFT JOIN users u ON p.id_user = u.id
                  WHERE p.id = '$id'";
    $q_kasus = mysqli_query($conn, $sql_kasus);
}

// Menghitung akumulasi data kasus ke dalam array
$list_kasus = [];
$total_poin = 0;
while ($row = mysqli_fetch_assoc($q_kasus)) {
    $list_kasus[] = $row;
    $total_poin += $row['poin'];
}
$total_kasus = count($list_kasus);
?>

<div class="container-fluid py-3 section-no-print">
    <!-- Header Menu & Tombol Aksi -->
    <div class="card shadow-sm border-0 rounded-3 mb-4 bg-white">
        <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="fw-bold mb-1 text-dark"><i class="fas fa-info-circle text-info me-2"></i>Detail Rekap Pelanggaran</h5>
                <small class="text-muted">Informasi riwayat dan akumulasi poin pelanggaran siswa.</small>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-secondary btn-sm px-3 shadow-2xs d-inline-flex align-items-center" onclick="cetakHalaman()">
                    <i class="fas fa-print me-1"></i> Cetak PDF
                </button>
                <a href="<?= $back_url ?>" class="btn btn-light btn-sm px-3 border shadow-2xs">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <!-- TAMPILAN JIKA DIAKSES DARI MENU PENGELOMPOKAN (Layout Full-Width dengan Tabel Tunggal di Bawah) -->
    <?php if ($source === 'pengelompokan'): ?>
        <div class="row g-4 mb-4">
            <!-- Kolom Biodata Siswa -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                    <div class="card-header bg-dark text-white fw-bold py-3">
                        <i class="fas fa-user-graduation me-2"></i>Informasi Akademik Siswa
                    </div>
                    <div class="card-body p-4">
                        <table class="table table-sm table-borderless mb-0 align-middle" style="font-size: 0.95rem;">
                            <tr>
                                <td width="30%" class="text-secondary pb-2">Nama Lengkap</td>
                                <td width="5%" class="text-muted pb-2">:</td>
                                <td class="fw-bold text-dark pb-2"><?= htmlspecialchars($siswa['nama']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-secondary pb-2">NIS / NISN</td>
                                <td class="text-muted pb-2">:</td>
                                <td class="font-monospace text-secondary pb-2"><?= htmlspecialchars($siswa['nis']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-secondary pb-2">Kelas</td>
                                <td class="text-muted pb-2">:</td>
                                <td class="pb-2"><span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($siswa['nama_kelas'] ?? '-') ?></span></td>
                            </tr>
                            <tr>
                                <td class="text-secondary">Jenis Kelamin</td>
                                <td class="text-muted">:</td>
                                <td><?= isset($siswa['jenis_kelamin']) && $siswa['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Kolom Ringkasan Akumulasi Poin -->
            <div class="col-md-6">
                <div class="row g-3 h-100 align-content-start">
                    <div class="col-12 col-sm-6">
                        <div class="card border-0 bg-primary text-white shadow-sm rounded-3 p-4">
                            <small class="opacity-75 d-block mb-1 font-monospace">TOTAL KASUS</small>
                            <h2 class="fw-bold mb-0"><?= $total_kasus ?> <span style="font-size: 1.1rem; font-weight: normal;">Kejadian</span></h2>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="card border-0 bg-danger text-white shadow-sm rounded-3 p-4">
                            <small class="opacity-75 d-block mb-1 font-monospace">AKUMULASI POIN</small>
                            <h2 class="fw-bold mb-0">+<?= $total_poin ?> <span style="font-size: 1.1rem; font-weight: normal;">Poin</span></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PERBAIKAN: TABEL UTUH REKAP PELANGGARAN BERDASARKAN KOLOM ANDA -->
        <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
            <div class="card-header bg-secondary text-white fw-bold py-3">
                <i class="fas fa-list me-2"></i>Daftar Riwayat Kasus & Sanksi Pelanggaran Siswa
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover mb-0 align-middle" style="font-size: 0.9rem;">
                        <thead class="table-light text-nowrap">
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th width="10%">Tanggal</th>
                                <th width="15%">Petugas</th>
                                <th width="20%">Jenis Pelanggaran</th>
                                <th width="8%" class="text-center">Poin</th>
                                <th>Bentuk & Keterangan Pelanggaran</th>
                                <th width="15%">Sanksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if($total_kasus > 0) {
                                $no_s = 1;
                                foreach($list_kasus as $kasus) {
                                    $tgl_c = date('d/m/Y', strtotime($kasus['tanggal']));
                                    
                                    // Logika Sanksi dinamis berdasarkan besaran poin kasus tersebut
                                    $poin_p = $kasus['poin'];
                                    $sanksi_teks = "Teguran Lisan";
                                    if ($poin_p >= 75) { $sanksi_teks = "Skorsing / Drop Out"; } 
                                    elseif ($poin_p >= 50) { $sanksi_teks = "Pemanggilan Orang Tua"; } 
                                    elseif ($poin_p >= 25) { $sanksi_teks = "Surat Peringatan (SP)"; } 
                                    elseif ($poin_p >= 10) { $sanksi_teks = "Teguran Tertulis"; }
                            ?>
                                    <tr>
                                        <td class="text-center text-muted"><?= $no_s++ ?></td>
                                        <td class="text-secondary text-nowrap"><?= $tgl_c ?></td>
                                        <td class="small text-secondary"><?= htmlspecialchars($kasus['petugas'] ?? 'Sistem') ?></td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($kasus['nama_pelanggaran']) ?></td>
                                        <td class="text-center fw-bold text-danger">+<?= $poin_p ?></td>
                                        <td><?= htmlspecialchars($kasus['keterangan'] ?: '-') ?></td>
                                        <td><span class="badge bg-warning text-dark fw-semibold"><?= $sanksi_teks ?></span></td>
                                    </tr>
                            <?php
                                }
                            } else {
                                echo '<tr><td colspan="7" class="text-center text-muted py-4"><em>Siswa tidak memiliki riwayat pelanggaran.</em></td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- TAMPILAN JIKA DIAKSES DARI MENU SEMUA (Kembali Ke Tampilan Awal: Kiri Biodata, Kanan Log Kasus Tunggal) -->
    <?php else: ?>
        <div class="row g-4">
            <!-- Kolom Kiri: Informasi Akademik -->
            <div class="col-md-5">
                <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
                    <div class="card-header bg-dark text-white fw-bold py-3">
                        <i class="fas fa-user-graduation me-2"></i>Informasi Akademik Siswa
                    </div>
                    <div class="card-body p-4">
                        <table class="table table-sm table-borderless mb-0 align-middle" style="font-size: 0.95rem;">
                            <tr>
                                <td width="35%" class="text-secondary pb-2">Nama Lengkap</td>
                                <td width="5%" class="text-muted pb-2">:</td>
                                <td class="fw-bold text-dark pb-2"><?= htmlspecialchars($siswa['nama']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-secondary pb-2">NIS / NISN</td>
                                <td class="text-muted pb-2">:</td>
                                <td class="font-monospace text-secondary pb-2"><?= htmlspecialchars($siswa['nis']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-secondary pb-2">Kelas</td>
                                <td class="text-muted pb-2">:</td>
                                <td class="pb-2"><span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($siswa['nama_kelas'] ?? '-') ?></span></td>
                            </tr>
                            <tr>
                                <td class="text-secondary">Jenis Kelamin</td>
                                <td class="text-muted">:</td>
                                <td><?= isset($siswa['jenis_kelamin']) && $siswa['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Detail Kasus Terpilih -->
            <div class="col-md-7">
                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div class="card border-0 bg-primary text-white shadow-sm rounded-3 p-3">
                            <small class="opacity-75 d-block mb-1 font-monospace">TOTAL KASUS</small>
                            <h3 class="fw-bold mb-0"><?= $total_kasus ?> <span style="font-size: 1rem; font-weight: normal;">Kejadian</span></h3>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card border-0 bg-danger text-white shadow-sm rounded-3 p-3">
                            <small class="opacity-75 d-block mb-1 font-monospace">AKUMULASI POIN</small>
                            <h3 class="fw-bold mb-0">+<?= $total_poin ?> <span style="font-size: 1rem; font-weight: normal;">Poin</span></h3>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-3 bg-white">
                    <div class="card-header bg-danger text-white fw-bold py-3">
                        <i class="fas fa-history me-2"></i>Detail Kasus Pelanggaran Terpilih
                    </div>
                    <div class="card-body p-3">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border mb-0" style="font-size: 0.9rem;">
                                <thead class="table-dark text-nowrap">
                                    <tr>
                                        <th width="5%" class="text-center">No</th>
                                        <th width="15%">Tanggal</th>
                                        <th>Bentuk & Keterangan Pelanggaran</th>
                                        <th width="12%" class="text-center">Poin</th>
                                        <th width="20%">Petugas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($total_kasus > 0) {
                                        $no_k = 1;
                                        foreach ($list_kasus as $kasus) {
                                            $tgl_c = date('d/m/Y', strtotime($kasus['tanggal']));
                                    ?>
                                            <tr>
                                                <td class="text-center text-muted"><?= $no_k++ ?></td>
                                                <td class="text-secondary"><?= $tgl_c ?></td>
                                                <td>
                                                    <span class="d-block fw-bold text-danger"><?= htmlspecialchars($kasus['nama_pelanggaran']) ?></span>
                                                    <small class="text-muted"><?= htmlspecialchars($kasus['keterangan'] ?: '-') ?></small>
                                                </td>
                                                <td class="text-center"><span class="badge bg-danger rounded-pill px-2 py-1">+<?= $kasus['poin'] ?></span></td>
                                                <td class="small text-secondary"><?= htmlspecialchars($kasus['petugas'] ?? 'Sistem') ?></td>
                                            </tr>
                                    <?php
                                        }
                                    } else {
                                        echo '<tr><td colspan="5" class="text-center py-4 text-muted"><em>Tidak ada catatan log pelanggaran.</em></td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ======================================================== -->
<!-- SECTION KHUSUS PRINT TAMPILAN LEMBAR SIAP CETAK          -->
<!-- ======================================================== -->
<div id="printArea" class="section-to-print d-none">
    <div style="text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px;">
        <h2 style="margin: 0; text-transform: uppercase;">SURAT DOKUMEN REKAP PELANGGARAN SISWA</h2>
        <p style="margin: 5px 0 0 0;">Laporan Akumulasi Poin Pelanggaran Kedisiplinan</p>
    </div>

    <table style="width: 100%; margin-bottom: 20px; font-size: 14px;" cellpadding="5">
        <tr>
            <td width="20%"><strong>Nama Siswa</strong></td><td width="2%">:</td><td><?= htmlspecialchars($siswa['nama']) ?></td>
            <td width="20%"><strong>Total Kasus</strong></td><td width="2%">:</td><td><?= $total_kasus ?> Kasus</td>
        </tr>
        <tr>
            <td><strong>NIS / NISN</strong></td><td>:</td><td><?= htmlspecialchars($siswa['nis']) ?></td>
            <td><strong>Akumulasi Poin</strong></td><td>:</td><td><strong><?= $total_poin ?> Poin</strong></td>
        </tr>
        <tr>
            <td><strong>Kelas</strong></td><td>:</td><td><?= htmlspecialchars($siswa['nama_kelas'] ?? '-') ?></td>
            <td><strong>Jenis Kelamin</strong></td><td>:</td><td><?= isset($siswa['jenis_kelamin']) && $siswa['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
        </tr>
    </table>

    <h4 style="margin-bottom: 8px; border-bottom: 1px solid #ddd; padding-bottom: 4px;">Detail Pelanggaran Yang Tercetak:</h4>
    <table style="width: 100%; border-collapse: collapse; font-size: 13px;" border="1" cellpadding="6">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th width="5%">No</th>
                <th width="12%">Tanggal</th>
                <th width="15%">Petugas</th>
                <th width="20%">Jenis Pelanggaran</th>
                <th width="8%">Poin</th>
                <th>Bentuk & Keterangan</th>
                <th width="15%">Sanksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($total_kasus > 0) {
                $no_p = 1;
                foreach ($list_kasus as $kasus) {
                    $poin_p = $kasus['poin'];
                    $sanksi_teks = "Teguran Lisan";
                    if ($poin_p >= 75) { $sanksi_teks = "Skorsing / Drop Out"; } 
                    elseif ($poin_p >= 50) { $sanksi_teks = "Pemanggilan Orang Tua"; } 
                    elseif ($poin_p >= 25) { $sanksi_teks = "Surat Peringatan (SP)"; } 
                    elseif ($poin_p >= 10) { $sanksi_teks = "Teguran Tertulis"; }
            ?>
                    <tr>
                        <td align="center"><?= $no_p++ ?></td>
                        <td><?= date('d/m/Y', strtotime($kasus['tanggal'])) ?></td>
                        <td><?= htmlspecialchars($kasus['petugas'] ?? 'Sistem') ?></td>
                        <td><strong><?= htmlspecialchars($kasus['nama_pelanggaran']) ?></strong></td>
                        <td align="center">+<?= $poin_p ?></td>
                        <td><?= htmlspecialchars($kasus['keterangan'] ?: '-') ?></td>
                        <td><?= $sanksi_teks ?></td>
                    </tr>
            <?php
                }
            } else {
                echo '<tr><td colspan="7" align="center">Tidak ada riwayat pelanggaran.</td></tr>';
            }
            ?>
        </tbody>
    </table>

    <table style="width: 100%; margin-top: 50px; font-size: 14px;">
        <tr>
            <td width="50%"></td>
            <td align="center">
                Kepala Sekolah / Petugas BK<br><br><br><br>
                ( ___________________________ )
            </td>
        </tr>
    </table>
</div>

<script>
function cetakHalaman() {
    window.print();
    setTimeout(function() {
        window.location.href = "<?= $back_url ?>";
    }, 500);
}
</script>

<style>
    .shadow-2xs { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
    @media print {
        body * {
            visibility: hidden;
        }
        #printArea, #printArea * {
            visibility: visible;
        }
        #printArea {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            display: block !important;
        }
        .section-no-print {
            display: none !important;
        }
    }
</style>