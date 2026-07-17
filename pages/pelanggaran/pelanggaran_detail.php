<?php
/** @var mysqli $conn */

// Pastikan ID tersedia di URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='alert alert-danger m-3'>ID Pelanggaran tidak ditemukan.</div>";
    exit;
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// 1. QUERY UTAMA: Mengambil seluruh data inti pelanggaran siswa
$query = mysqli_query($conn, "SELECT p.*, s.id AS id_siswa_relasi, s.nis, s.nama AS nama_siswa, 
                                     k.nama_kelas, k.tahun_ajaran, k.wali_kelas,
                                     j.nama_pelanggaran, j.poin, 
                                     u.nama_lengkap AS nama_petugas
                              FROM pelanggaran p
                              JOIN siswa s ON p.id_siswa = s.id
                              JOIN jenis_pelanggaran j ON p.id_jenis = j.id
                              LEFT JOIN users u ON p.id_user = u.id
                              LEFT JOIN kelas k ON s.id_kelas = k.id
                              WHERE p.id = '$id'");

$data = mysqli_fetch_array($query);

if (!$data) {
    echo "<div class='alert alert-warning m-3'>Data pelanggaran tidak ditemukan di database.</div>";
    exit;
}

// 2. QUERY HITUNG TOTAL AKUMULASI POIN SISWA
$id_siswa = intval($data['id_siswa_relasi']);
$query_poin = mysqli_query($conn, "SELECT SUM(jp.poin) AS total_poin 
                                   FROM pelanggaran pl
                                   JOIN jenis_pelanggaran jp ON pl.id_jenis = jp.id
                                   WHERE pl.id_siswa = '$id_siswa'");
$data_poin = mysqli_fetch_array($query_poin);
$total_poin_siswa = intval($data_poin['total_poin'] ?? 0);

// 3. QUERY MENCARI SANKSI SECARA AMAN (Auto-detect nama kolom)
$nama_sanksi = "Peringatan Lisan / Pembinaan";
$query_sanksi = mysqli_query($conn, "SELECT * FROM sanksi");

if ($query_sanksi && mysqli_num_rows($query_sanksi) > 0) {
    while ($s = mysqli_fetch_assoc($query_sanksi)) {
        $p_min = isset($s['poin_min']) ? $s['poin_min'] : (isset($s['min_poin']) ? $s['min_poin'] : (isset($s['poin']) ? $s['poin'] : 0));
        $p_max = isset($s['poin_max']) ? $s['poin_max'] : (isset($s['max_poin']) ? $s['max_poin'] : (isset($s['poin']) ? $s['poin'] : 1000));
        
        if ($total_poin_siswa >= $p_min && $total_poin_siswa <= $p_max) {
            $nama_sanksi = $s['nama_sanksi'] ?? ($s['sanksi'] ?? $nama_sanksi);
            break;
        }
    }
}

// Konversi Tanggal ke format Indonesia (Contoh: 17 Juli 2026)
$bulan_indo = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
$split_tgl  = explode('-', $data['tanggal']);
$tanggal_id = $split_tgl[2] . ' ' . $bulan_indo[(int)$split_tgl[1]] . ' ' . $split_tgl[0];
?>

<div class="d-flex justify-content-between align-items-center mb-4 screen-only">
    <div>
        <h4 class="mb-0 fw-bold text-dark">Rincian Pelanggaran Siswa</h4>
        <small class="text-muted">Detail rekam jejak kedisiplinan dan akumulasi sanksi resmi</small>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print();" class="btn btn-sm btn-primary shadow-sm px-3">
            <i class="fas fa-print me-1"></i> Cetak Dokumen
        </button>
        <a href="index.php?page=pelanggaran" class="btn btn-sm btn-light border shadow-sm px-3">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3 overflow-hidden">
    <div class="card-header bg-dark py-3 px-4 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold mb-0 fs-6"><i class="fas fa-file-invoice me-2 text-warning"></i>KARTU REKAM KEDISIPLINAN SISWA</span>
        <span class="badge bg-light text-dark font-monospace px-2 py-1 small">ID: #<?= $data['id'] ?></span>
    </div>
    
    <div class="card-body p-4 bg-white">
        <div class="row align-items-center border-bottom pb-3 mb-4 g-3">
            <div class="col-sm-8 text-center text-sm-start">
                <h4 class="fw-bold text-dark text-uppercase mb-1"><?= htmlspecialchars($data['nama_siswa']) ?></h4>
                <p class="text-muted mb-0">Nomor Induk Siswa: <strong class="font-monospace text-secondary"><?= htmlspecialchars($data['nis']) ?></strong></p>
            </div>
            <div class="col-sm-4 text-center text-sm-end">
                <div class="p-2 rounded bg-light border d-inline-block text-center shadow-sm px-3">
                    <small class="text-uppercase text-secondary d-block fw-bold" style="font-size: 0.7rem; letter-spacing: 1px;">Total Akumulasi</small>
                    <span class="fs-4 fw-bold text-danger"><?= $total_poin_siswa ?> <small class="fs-6 text-muted">Poin</small></span>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6 border-end-md">
                <div class="pe-md-3">
                    <h6 class="text-primary fw-bold mb-3 d-flex align-items-center">
                        <span class="p-1 rounded bg-light-primary text-primary me-2 d-inline-flex align-items-center justify-content-center" style="width:28px; height:28px;">
                            <i class="fas fa-graduation-cap small"></i>
                        </span>
                        Informasi Akademik Siswa
                    </h6>
                    <table class="table table-borderless table-sm align-middle mb-0">
                        <tr>
                            <th width="35%" class="text-secondary fw-semibold py-2">Nama Kelas</th>
                            <td class="text-dark py-2">: <span class="badge bg-light text-dark border fw-bold px-2"><?= htmlspecialchars($data['nama_kelas'] ?? '-') ?></span></td>
                        </tr>
                        <tr>
                            <th class="text-secondary fw-semibold py-2">Wali Kelas</th>
                            <td class="text-dark py-2">: <?= htmlspecialchars($data['wali_kelas'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <th class="text-secondary fw-semibold py-2">Tahun Ajaran</th>
                            <td class="text-dark py-2">: <span class="fw-semibold"><?= htmlspecialchars($data['tahun_ajaran'] ?? '-') ?></span></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="col-md-6">
                <div class="ps-md-2">
                    <h6 class="text-danger fw-bold mb-3 d-flex align-items-center">
                        <span class="p-1 rounded bg-light-danger text-danger me-2 d-inline-flex align-items-center justify-content-center" style="width:28px; height:28px;">
                            <i class="fas fa-exclamation-circle small"></i>
                        </span>
                        Rincian Kasus Pelanggaran
                    </h6>
                    <table class="table table-borderless table-sm align-middle mb-0">
                        <tr>
                            <th width="45%" class="text-secondary fw-semibold py-2">Tanggal Kejadian</th>
                            <td class="text-dark fw-bold py-2">: <?= $tanggal_id ?></td>
                        </tr>
                        <tr>
                            <th class="text-secondary fw-semibold py-2">Bentuk Pelanggaran</th>
                            <td class="py-2">: <span class="text-danger fw-bold"><?= htmlspecialchars($data['nama_pelanggaran']) ?></span></td>
                        </tr>
                        <tr>
                            <th class="text-secondary fw-semibold py-2">Bobot Poin Kasus</th>
                            <td class="py-2">: <span class="badge bg-danger px-2">+ <?= $data['poin'] ?> Poin</span></td>
                        </tr>
                        <tr>
                            <th class="text-secondary fw-semibold py-2">Petugas Pencatat</th>
                            <td class="text-dark py-2">: <?= htmlspecialchars($data['nama_petugas'] ?? 'Administrator') ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="row mt-3 pt-3 border-top g-3">
            <div class="col-12">
                <div class="p-3 rounded mb-3 shadow-sm bg-light-warning border-start border-warning border-3">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <span class="fw-bold text-dark d-block mb-1 mb-md-0"><i class="fas fa-gavel text-warning me-2"></i>Bentuk Sanksi :</span>
                        </div>
                        <div class="col-md-9">
                            <span class="text-dark fw-bold fs-6"><?= htmlspecialchars($nama_sanksi) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-12">
                <label class="fw-bold text-secondary small mb-2"><i class="fas fa-align-left me-1"></i>Keterangan Tambahan / Kronologi Kejadian:</label>
                <div class="p-3 bg-light rounded text-dark border" style="min-height: 80px; font-size: 0.92rem; line-height: 1.6; white-space: pre-line;">
                    <?= !empty($data['keterangan']) ? htmlspecialchars($data['keterangan']) : '<em>Tidak ada catatan kronologi atau keterangan tambahan untuk kasus ini.</em>' ?>
                </div>
            </div>
        </div>

        <div class="row mt-5 pt-3 print-only d-none text-center">
            <div class="col-4">
                <p class="mb-5 small text-secondary">Orang Tua / Wali Murid,</p>
                <p class="fw-bold border-bottom d-inline-block px-4 mb-0" style="min-width: 160px;">&nbsp;</p>
            </div>
            <div class="col-4">
                <p class="mb-5 small text-secondary">Wali Kelas,</p>
                <p class="fw-bold text-dark mb-0 text-decoration-underline"><?= htmlspecialchars($data['wali_kelas'] ?? '........................') ?></p>
            </div>
            <div class="col-4">
                <p class="mb-5 small text-secondary">Pencatat Pelanggaran,</p>
                <p class="fw-bold text-dark mb-0 text-decoration-underline"><?= htmlspecialchars($data['nama_petugas'] ?? '........................') ?></p>
            </div>
        </div>

    </div>
</div>

<style>
    .bg-light-primary { background-color: #eef3ff; }
    .bg-light-danger { background-color: #ffeef0; }
    .bg-light-warning { background-color: #fffbf0; border-color: #ffc107 !important; }

    @media (min-width: 768px) {
        .border-end-md { border-end: 1px solid #dee2e6 !important; }
    }

    @media print {
        body { background-color: #fff !important; padding: 0 !important; font-size: 12px; }
        .screen-only, .btn { display: none !important; }
        .print-only { display: flex !important; }
        .card { border: 1px solid #000 !important; box-shadow: none !important; border-radius: 0 !important; }
        .card-header { background-color: #f8f9fa !important; color: #000 !important; border-bottom: 1px solid #000 !important; }
        .card-header span { color: #000 !important; }
        .bg-light, .bg-light-warning { background-color: #fff !important; border: 1px solid #dee2e6 !important; }
        .border-end-md { border-right: 1px solid #dee2e6 !important; }
    }
</style>