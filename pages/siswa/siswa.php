<?php
/** @var mysqli $conn */

// 1. Pastikan Session Aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Menangkap dan Mengamankan Nilai Filter/Pencarian (Mencegah SQL Injection)
$cari          = isset($_GET['cari']) ? mysqli_real_escape_string($conn, trim($_GET['cari'])) : '';
$filter_kelas  = isset($_GET['filter_kelas']) ? mysqli_real_escape_string($conn, trim($_GET['filter_kelas'])) : '';
$filter_status = isset($_GET['filter_status']) ? mysqli_real_escape_string($conn, trim($_GET['filter_status'])) : 'Aktif';

// 3. Konfigurasi Pagination
$limit   = 10; // Jumlah data per halaman
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
if ($halaman < 1) $halaman = 1;
$offset  = ($halaman - 1) * $limit;

// 4. Menyiapkan Kondisi Query
$kondisi = "";
if ($cari != '') {
    $kondisi .= " AND (siswa.nama LIKE '%$cari%' OR siswa.nis LIKE '%$cari%')";
}
if ($filter_kelas != '') {
    $kondisi .= " AND siswa.id_kelas = '$filter_kelas'";
}
if ($filter_status != 'Semua') {
    $kondisi .= " AND siswa.status = '$filter_status'";
}

// 5. Query Hitung Total Data (Untuk Pagination)
$sql_total   = "SELECT COUNT(*) AS total 
                FROM siswa 
                LEFT JOIN kelas ON siswa.id_kelas = kelas.id 
                WHERE 1=1 $kondisi";
$query_total = mysqli_query($conn, $sql_total);
$data_total  = mysqli_fetch_assoc($query_total);
$total_data  = $data_total['total'] ?? 0;
$total_halaman = ceil($total_data / $limit);

// 6. Query Utama Ambil Data Siswa dengan LIMIT & OFFSET
$sql = "SELECT siswa.*, kelas.nama_kelas, kelas.tahun_ajaran 
        FROM siswa 
        LEFT JOIN kelas ON siswa.id_kelas = kelas.id 
        WHERE 1=1 $kondisi 
        ORDER BY siswa.id DESC 
        LIMIT $limit OFFSET $offset";

$query = mysqli_query($conn, $sql);

// 7. Notifikasi SweetAlert dari Session (Sesudah Tambah/Edit/Hapus)
$swal_script = '';
if (isset($_SESSION['status_pesan'])) {
    $tipe  = $_SESSION['status_pesan']['tipe'] ?? 'success';
    $judul = $_SESSION['status_pesan']['judul'] ?? 'Berhasil!';
    $teks  = $_SESSION['status_pesan']['teks'] ?? 'Data berhasil diproses.';
    
    $swal_script = "
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: '{$tipe}',
                title: '{$judul}',
                text: '{$teks}',
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true
            });
        }
    });
    </script>";
    unset($_SESSION['status_pesan']);
}
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?= $swal_script ?>

<style>
    .table-responsive-custom {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    @media (max-width: 575.98px) {
        .header-title-responsive {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 10px !important;
        }
        .header-title-responsive a {
            width: 100% !important;
            text-align: center;
        }
        .filter-btn-group .btn {
            width: 100% !important;
            margin-bottom: 5px;
        }
        .table-custom th, .table-custom td {
            font-size: 0.82rem !important;
            padding: 8px 6px !important;
            white-space: nowrap;
        }
    }
    @media (min-width: 576px) and (max-width: 991.98px) {
        .table-custom th, .table-custom td {
            font-size: 0.88rem !important;
        }
    }
</style>

<div class="container-fluid py-2 py-md-3">

    <div class="d-flex justify-content-between align-items-center mb-3 header-title-responsive">
        <h2 class="h4 fw-bold mb-0"><i class="fas fa-user-graduate text-primary me-2"></i>Data Siswa</h2>
        <a href="index.php?page=siswa_tambah" class="btn btn-primary shadow-sm"><i class="fas fa-plus me-1"></i> Tambah Siswa</a>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body bg-light rounded-3">
            <form method="GET" action="index.php" class="row g-2">
                <input type="hidden" name="page" value="siswa">
                
                <div class="col-12 col-md-4 col-lg-3">
                    <input type="text" name="cari" class="form-control" placeholder="Cari NIS atau Nama Siswa..." value="<?= htmlspecialchars($cari); ?>">
                </div>
                
                <div class="col-12 col-md-4 col-lg-3">
                    <select name="filter_kelas" class="form-select">
                        <option value="">Semua Kelas & Tahun Ajaran</option>
                        <?php
                        $q_kelas = mysqli_query($conn, "SELECT * FROM kelas ORDER BY tahun_ajaran DESC, nama_kelas ASC");
                        while($k = mysqli_fetch_array($q_kelas)){
                            $selected = ($filter_kelas == $k['id']) ? 'selected' : '';
                            echo "<option value='{$k['id']}' $selected>{$k['nama_kelas']} ({$k['tahun_ajaran']})</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="col-12 col-md-4 col-lg-2">
                    <select name="filter_status" class="form-select">
                        <option value="Semua" <?= ($filter_status == 'Semua') ? 'selected' : ''; ?>>Semua Status</option>
                        <option value="Aktif" <?= ($filter_status == 'Aktif') ? 'selected' : ''; ?>>Aktif</option>
                        <option value="Tidak Aktif" <?= ($filter_status == 'Tidak Aktif') ? 'selected' : ''; ?>>Tidak Aktif</option>
                    </select>
                </div>
                
                <div class="col-6 col-lg-2 d-grid filter-btn-group">
                    <button type="submit" class="btn btn-secondary"><i class="fas fa-search me-1"></i> Tampilkan</button>
                </div>
                <div class="col-6 col-lg-2 d-grid filter-btn-group">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='index.php?page=siswa&filter_status=Aktif'"><i class="fas fa-sync-alt me-1"></i> Reset</button>
                </div>
            </form>
        </div>
    </div>
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body p-0">
            <div class="table-responsive-custom">
                <table class="table table-bordered table-hover align-middle table-custom mb-0 bg-white">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="15%">NIS</th>
                            <th>Nama Siswa</th>
                            <th width="22%">Kelas (Tahun Ajaran)</th>
                            <th width="10%" class="text-center">Status</th>
                            <th width="13%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = $offset + 1;
                        if(mysqli_num_rows($query) == 0){
                            echo "<tr><td colspan='6' class='text-center text-danger fw-bold py-4'>Data siswa tidak ditemukan!</td></tr>";
                        } else {
                            while($data = mysqli_fetch_array($query)){
                        ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td><?= htmlspecialchars($data['nis']); ?></td>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($data['nama']); ?></td>
                            <td>
                                <?= htmlspecialchars($data['nama_kelas'] ?? '-'); ?> 
                                <?php if(!empty($data['tahun_ajaran'])): ?>
                                    <span class="badge bg-info text-dark ms-1"><?= htmlspecialchars($data['tahun_ajaran']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= ($data['status'] == 'Aktif') ? 'bg-success' : 'bg-danger' ?>">
                                    <?= htmlspecialchars($data['status']); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="index.php?page=siswa_edit&id=<?= $data['id']; ?>" class="btn btn-warning text-dark" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-info text-white btn-detail" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalDetail" 
                                            data-id="<?= $data['id']; ?>" 
                                            title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="index.php?page=siswa_hapus&id=<?= $data['id']; ?>" class="btn btn-danger btn-hapus" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            } 
                        } 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($total_halaman > 1): ?>
        <?php
        $url_params = "index.php?page=siswa&cari=" . urlencode($cari) . "&filter_kelas=" . urlencode($filter_kelas) . "&filter_status=" . urlencode($filter_status);
        ?>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 mb-4">
            <small class="text-muted">
                Menampilkan <?= min($offset + 1, $total_data); ?> sampai <?= min($offset + $limit, $total_data); ?> dari <?= $total_data; ?> data
            </small>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm m-0">
                    <li class="page-item <?= ($halaman <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?= $url_params; ?>&halaman=<?= $halaman - 1; ?>">&laquo;</a>
                    </li>

                    <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                        <li class="page-item <?= ($halaman == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="<?= $url_params; ?>&halaman=<?= $i; ?>"><?= $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?= $url_params; ?>&halaman=<?= $halaman + 1; ?>">&raquo;</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>

</div>

<div class="modal fade" id="modalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fs-6 fw-bold"><i class="fas fa-user-circle me-2"></i>Detail Siswa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="detailBody">
                <div class="text-center text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Memuat data...</div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Skrip untuk memuat modal detail via AJAX
    const btnDetails = document.querySelectorAll('.btn-detail');
    btnDetails.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const detailBody = document.getElementById('detailBody');
            detailBody.innerHTML = '<div class="text-center text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Memuat data...</div>';
            
            fetch('pages/siswa/siswa_detail.php?id=' + id)
                .then(response => response.text())
                .then(data => {
                    detailBody.innerHTML = data;
                })
                .catch(error => {
                    detailBody.innerHTML = '<div class="text-center text-danger">Gagal memuat detail data.</div>';
                });
        });
    });

    // 2. Skrip Konfirmasi Hapus SweetAlert2
    const tombolHapus = document.querySelectorAll('.btn-hapus');
    tombolHapus.forEach(tombol => {
        tombol.addEventListener('click', function(e) {
            e.preventDefault(); 
            const urlHapus = this.getAttribute('href'); 
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data siswa ini akan dihapus secara permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = urlHapus;
                }
            });
        });
    });
});
</script>