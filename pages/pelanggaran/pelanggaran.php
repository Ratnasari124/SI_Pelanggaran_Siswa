<?php
/** @var mysqli $conn */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// 1. PROSES AJAX UNTUK LIVE SEARCH AUTOCOMPLETE
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'search_siswa') {
    $keyword = mysqli_real_escape_string($conn, $_GET['keyword']);
    $q = mysqli_query($conn, "SELECT s.id, s.nis, s.nama, k.nama_kelas 
                              FROM siswa s 
                              LEFT JOIN kelas k ON s.id_kelas = k.id 
                              WHERE s.nama LIKE '%$keyword%' OR s.nis LIKE '%$keyword%' 
                              LIMIT 10");
    $result = [];
    while($r = mysqli_fetch_assoc($q)) {
        $result[] = $r;
    }
    echo json_encode($result);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'search_jenis') {
    $keyword = mysqli_real_escape_string($conn, $_GET['keyword']);
    $q = mysqli_query($conn, "SELECT id, nama_pelanggaran, poin FROM jenis_pelanggaran WHERE nama_pelanggaran LIKE '%$keyword%' LIMIT 10");
    $result = [];
    while($r = mysqli_fetch_assoc($q)) {
        $result[] = $r;
    }
    echo json_encode($result);
    exit;
}

// ==========================================
// 2. PROSES AKSI POST FORM (TAMBAH / EDIT)
// ==========================================
$alert = '';
if (isset($_POST['simpan_pelanggaran'])) {
    $id_siswa = mysqli_real_escape_string($conn, $_POST['id_siswa']);
    $id_jenis = mysqli_real_escape_string($conn, $_POST['id_jenis']);
    $id_user  = mysqli_real_escape_string($conn, $_POST['id_user']);
    $tanggal  = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);

    if(!empty($id_siswa) && !empty($id_jenis)) {
        $ins = mysqli_query($conn, "INSERT INTO pelanggaran (id_siswa, id_jenis, id_user, tanggal, keterangan) 
                                    VALUES ('$id_siswa', '$id_jenis', '$id_user', '$tanggal', '$keterangan')");
        if ($ins) {
            $alert = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Berhasil!</strong> Catatan pelanggaran baru berhasil disimpan.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                      </div>';
        } else {
            $alert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Gagal!</strong> Terjadi kesalahan database saat menyimpan data.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                      </div>';
        }
    }
}

$opt_petugas = mysqli_query($conn, "SELECT id, nama_lengkap FROM users ORDER BY nama_lengkap ASC");

$view_type = isset($_GET['view']) ? $_GET['view'] : '';
$search_keyword = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$tgl_awal = isset($_GET['tgl_awal']) ? mysqli_real_escape_string($conn, $_GET['tgl_awal']) : '';
$tgl_akhir = isset($_GET['tgl_akhir']) ? mysqli_real_escape_string($conn, $_GET['tgl_akhir']) : '';
?>

<div class="container-fluid py-3">
    <?= $alert ?>

    <div class="card shadow-sm border-0 rounded-3 mb-4 card-filter-header">
        <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="fw-bold mb-1 text-dark"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Menu Log Pelanggaran</h5>
                <small class="text-muted">Pilih mode pengelompokan data untuk memulai pengelolaan.</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="fw-bold text-secondary text-nowrap mb-0 small">Mode Tampilan :</label>
                <select id="selectViewMode" class="form-select form-select-sm shadow-sm fw-bold border-primary text-primary" style="width: 200px;" onchange="switchMode(this.value)">
                    <option value="" <?= $view_type == '' ? 'selected' : '' ?>>-- Pilih Mode --</option>
                    <option value="pengelompokan" <?= $view_type == 'pengelompokan' ? 'selected' : '' ?>>Pengelompokan Siswa</option>
                    <option value="semua" <?= $view_type == 'semua' ? 'selected' : '' ?>>Semua Pelanggaran</option>
                </select>
            </div>
        </div>
    </div>

    <?php if ($view_type == ''): ?>
        <div class="text-center py-5 bg-white rounded shadow-sm border">
            <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" alt="Pilih Mode" style="max-width: 120px;" class="mb-3 opacity-75">
            <h5 class="fw-bold text-secondary">Silahkan Pilih Tipe Pengelompokan</h5>
            <p class="text-muted small">Pilih opsi <strong>Pengelompokan Siswa</strong> atau <strong>Semua Pelanggaran</strong> pada dropdown di atas untuk memuat data.</p>
        </div>
    <?php endif; ?>

    <!-- ================= VIEW PENGELOMPOKAN SISWA ================= -->
    <?php if ($view_type == 'pengelompokan'): ?>
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                    <form method="GET" action="index.php" class="d-flex gap-2 flex-grow-1" style="max-width: 400px;">
                        <input type="hidden" name="page" value="pelanggaran">
                        <input type="hidden" name="view" value="pengelompokan">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-secondary"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Ketik NAMA DEPAN siswa..." value="<?= htmlspecialchars($search_keyword) ?>">
                            <button type="submit" class="btn btn-primary btn-sm px-3">Cari</button>
                        </div>
                    </form>
                    <a href="index.php?page=pelanggaran_tambah" class="btn btn-danger btn-sm px-3 shadow-sm d-inline-flex align-items-center justify-content-center">
                        <i class="fas fa-plus me-1"></i> Tambah
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle border" style="font-size: 0.9rem;">
                        <thead class="table-dark text-nowrap">
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th>NIS / NISN</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th class="text-center">Total Kasus</th>
                                <th class="text-center">Akumulasi Poin</th>
                                <th width="15%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $where_clause = "";
                            if (!empty($search_keyword)) {
                                $where_clause = "WHERE s.nama LIKE '$search_keyword%'";
                            }
                            
                            $sql_grup = "SELECT s.id AS id_siswa, s.nis, s.nama AS nama_siswa, k.nama_kelas,
                                                COUNT(p.id) AS total_kasus,
                                                IFNULL(SUM(j.poin), 0) AS total_poin
                                         FROM siswa s
                                         LEFT JOIN kelas k ON s.id_kelas = k.id
                                         JOIN pelanggaran p ON p.id_siswa = s.id
                                         JOIN jenis_pelanggaran j ON p.id_jenis = j.id
                                         $where_clause
                                         GROUP BY s.id
                                         ORDER BY s.nama ASC";
                            $q_grup = mysqli_query($conn, $sql_grup);
                            
                            if (mysqli_num_rows($q_grup) > 0) {
                                $no = 1;
                                while($row = mysqli_fetch_assoc($q_grup)) {
                            ?>
                                    <tr>
                                        <td class="text-center align-middle"><?= $no++; ?></td>
                                        <td class="align-middle font-monospace text-secondary"><?= htmlspecialchars($row['nis']) ?></td>
                                        <td class="align-middle fw-bold"><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                        <td class="text-center align-middle">
                                            <span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></span>
                                        </td>
                                        <td class="text-center align-middle fw-bold text-primary"><?= $row['total_kasus'] ?> Kasus</td>
                                        <td class="text-center align-middle">
                                            <span class="badge bg-danger rounded-pill px-3 py-2 fs-6">+ <?= $row['total_poin'] ?></span>
                                        </td>
                                        <td class="text-center align-middle text-nowrap">
                                            <div class="d-inline-flex align-items-center justify-content-center gap-1">
                                                <a href="index.php?page=pelanggaran_detail&id=<?= $row['id_siswa'] ?>&source=pengelompokan" 
                                                   class="btn btn-info btn-sm text-white px-2 py-1 d-inline-flex align-items-center gap-1 shadow-2xs" 
                                                   title="Lihat Detail Rekap Siswa">
                                                    <i class="fas fa-eye small"></i>
                                                    <span>Detail Rekap</span>
                                                </a>
                                                
                                                <a href="index.php?page=pelanggaran_edit&id=<?= $row['id_siswa'] ?>&source=pengelompokan" 
                                                   class="btn btn-warning btn-sm text-dark fw-semibold px-2 py-1 d-inline-flex align-items-center gap-1 shadow-2xs" 
                                                   title="Edit Catatan Terakhir">
                                                    <i class="fas fa-edit small"></i>
                                                    <span>Edit</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                            <?php
                                }
                            } else {
                                echo '<tr><td colspan="7" class="text-center py-4 text-muted"><em>Tidak ditemukan siswa yang terdaftar melakukan pelanggaran.</em></td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ================= VIEW SEMUA PELANGGARAN ================= -->
    <?php if ($view_type == 'semua'): ?>
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-body p-4">
                <form method="GET" action="index.php" class="row g-2 align-items-end mb-4">
                    <input type="hidden" name="page" value="pelanggaran">
                    <input type="hidden" name="view" value="semua">
                    
                    <div class="col-md-3 col-sm-6">
                        <label class="small text-secondary fw-semibold mb-1">Cari Nama / NIS</label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Ketik kata kunci..." value="<?= htmlspecialchars($search_keyword) ?>">
                    </div>
                    <div class="col-md-2 col-sm-3">
                        <label class="small text-secondary fw-semibold mb-1">Tanggal Awal</label>
                        <input type="date" name="tgl_awal" class="form-control form-control-sm" value="<?= $tgl_awal ?>">
                    </div>
                    <div class="col-md-2 col-sm-3">
                        <label class="small text-secondary fw-semibold mb-1">Tanggal Akhir</label>
                        <input type="date" name="tgl_akhir" id="filter_tgl_akhir" class="form-control form-control-sm" value="<?= $tgl_akhir ?>">
                    </div>
                    
                    <div class="col-md-5 col-12 text-md-end mt-3 mt-md-0 d-flex gap-2 justify-content-start justify-content-md-end align-items-center">
                        <button type="submit" class="btn btn-primary btn-sm px-3 shadow-2xs"><i class="fas fa-filter me-1"></i> Filter</button>
                        <a href="index.php?page=pelanggaran_tambah" class="btn btn-danger btn-sm px-3 shadow-sm d-inline-flex align-items-center justify-content-center">
                            <i class="fas fa-plus me-1"></i> Tambah
                        </a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle border mb-0" style="font-size: 0.9rem;">
                        <thead class="table-dark text-nowrap">
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th width="12%">Tanggal</th>
                                <th width="15%">NIS / NISN</th>
                                <th width="20%">Nama Siswa</th>
                                <th width="10%">Kelas</th>
                                <th>Bentuk Pelanggaran</th>
                                <th width="8%" class="text-center">Poin</th>
                                <th width="15%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $conditions = [];
                            if (!empty($search_keyword)) {
                                $conditions[] = "(s.nama LIKE '%$search_keyword%' OR s.nis LIKE '%$search_keyword%')";
                            }
                            if (!empty($tgl_awal) && !empty($tgl_akhir)) {
                                $conditions[] = "(p.tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir')";
                            }
                            
                            $where_all = "";
                            if(count($conditions) > 0) {
                                $where_all = "WHERE " . implode(" AND ", $conditions);
                            }

                            $sql_all = "SELECT p.id AS id_kasus, p.tanggal, p.keterangan, s.nis, s.nama AS nama_siswa, 
                                               k.nama_kelas, j.nama_pelanggaran, j.poin
                                        FROM pelanggaran p
                                        JOIN siswa s ON p.id_siswa = s.id
                                        JOIN jenis_pelanggaran j ON p.id_jenis = j.id
                                        LEFT JOIN kelas k ON s.id_kelas = k.id
                                        $where_all
                                        ORDER BY p.tanggal DESC, p.id DESC";
                            $q_all = mysqli_query($conn, $sql_all);

                            if(mysqli_num_rows($q_all) > 0) {
                                $no_all = 1;
                                while($row = mysqli_fetch_assoc($q_all)) {
                                    $tgl_formatted = date('d/m/Y', strtotime($row['tanggal']));
                                    $id_kasus_fix = $row['id_kasus'];
                            ?>
                                    <tr>
                                        <td class="text-muted text-center"><?= $no_all++ ?></td>
                                        <td><?= $tgl_formatted ?></td>
                                        <td class="font-monospace text-secondary"><?= htmlspecialchars($row['nis']) ?></td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                        <td><span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></span></td>
                                        <td class="text-danger fw-normal"><?= htmlspecialchars($row['nama_pelanggaran']) ?></td>
                                        <td class="text-center"><span class="badge bg-danger rounded-pill px-2 py-1">+<?= $row['poin'] ?></span></td>
                                        
                                        <td class="text-center align-middle text-nowrap">
                                            <div class="d-inline-flex align-items-center justify-content-center gap-1">
                                                <a href="index.php?page=pelanggaran_detail&id=<?= $row['id_kasus'] ?>&source=semua" 
                                                   class="btn btn-info btn-sm text-white px-2 py-1 d-inline-flex align-items-center gap-1 shadow-2xs" 
                                                   title="Lihat Detail Kasus">
                                                    <i class="fas fa-eye small"></i>
                                                    <span>Detail</span>
                                                </a>

                                                <a href="index.php?page=pelanggaran_edit&id=<?= $id_kasus_fix ?>&source=semua" 
                                                   class="btn btn-warning btn-sm text-dark fw-semibold px-2 py-1 d-inline-flex align-items-center gap-1 shadow-2xs" 
                                                   title="Edit Catatan Pelanggaran">
                                                    <i class="fas fa-edit small"></i>
                                                    <span>Edit</span>
                                                </a>

                                                <!-- TOMBOL HAPUS MENU SEMUA (DI SEBELAH KANAN EDIT) -->
                                                <a href="pages/pelanggaran/pelanggaran_hapus.php?id_kasus=<?= $id_kasus_fix ?>&asal=menu_semua" 
                                                   onclick="return confirm('Apakah Anda yakin ingin menghapus catatan pelanggaran ini?');" 
                                                   class="btn btn-danger btn-sm px-2 py-1 d-inline-flex align-items-center gap-1 shadow-2xs"
                                                   title="Hapus Catatan Pelanggaran">
                                                    <i class="fas fa-trash small"></i>
                                                    <span>Hapus</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                            <?php
                                }
                            } else {
                                echo '<tr><td colspan="8" class="text-center py-4 text-muted"><em>Tidak ada log pelanggaran ditemukan.</em></td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function switchMode(val) {
    window.location.href = 'index.php?page=pelanggaran&view=' + val;
}
</script>

<style>
    .card-filter-header { background: #fdfdfd; border: 1px solid #e3e6f0 !important; }
    .shadow-2xs { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
</style>