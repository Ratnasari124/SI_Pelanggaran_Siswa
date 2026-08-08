<?php
/** @var mysqli $conn */

// 1. Ambil nilai filter (Pencarian & Filter Poin) menggunakan GET agar cocok dengan Paginasi
$cari = isset($_GET['cari']) ? trim($_GET['cari']) : '';
$filter_poin = isset($_GET['filter_poin']) ? trim($_GET['filter_poin']) : '';

// 2. Ambil daftar pengelompokan angka poin secara unik untuk dropdown
$query_angka_poin = mysqli_query($conn, "SELECT DISTINCT poin FROM jenis_pelanggaran ORDER BY poin ASC");

// 3. Menyusun kondisi query SQL (Sanitasi Input)
$kondisi = "";

if ($cari != '') {
    $cari_db = mysqli_real_escape_string($conn, $cari);
    $kondisi .= " AND nama_pelanggaran LIKE '%$cari_db%'";
}

if ($filter_poin != '') {
    $poin_pilihan = intval($filter_poin);
    $kondisi .= " AND poin = '$poin_pilihan'";
}

// 4. PENGATURAN PAGINASI (7 Data per Halaman)
$limit = 10;
$halaman = isset($_GET['halaman']) ? max(1, (int)$_GET['halaman']) : 1;
$halaman_awal = ($halaman - 1) * $limit;

// Hitung total data untuk paginasi
$query_total = mysqli_query($conn, "SELECT COUNT(*) as total FROM jenis_pelanggaran WHERE 1=1 $kondisi");
$data_total = mysqli_fetch_assoc($query_total);
$total_data = $data_total['total'];
$total_halaman = ceil($total_data / $limit);

// 5. Query utama dengan LIMIT & OFFSET
$sql = "SELECT * FROM jenis_pelanggaran WHERE 1=1 $kondisi ORDER BY poin DESC, nama_pelanggaran ASC LIMIT $halaman_awal, $limit";
$query = mysqli_query($conn, $sql);

// Menentukan nomor urut awal
$no = $halaman_awal + 1;
?>

<style>
/* CSS Optimasi Tampilan Adaptif / Responsif Perangkat */

/* Penyesuaian khusus untuk Layar Smartphone (Layar < 576px) */
@media (max-width: 575.98px) {
    .page-title {
        font-size: 1.35rem;
    }

    /* Mengubah Tabel Menjadi Card View di HP */
    .table-responsive-card table, 
    .table-responsive-card thead, 
    .table-responsive-card tbody, 
    .table-responsive-card th, 
    .table-responsive-card td, 
    .table-responsive-card tr { 
        display: block; 
    }

    /* Sembunyikan Header Tabel di HP */
    .table-responsive-card thead tr { 
        position: absolute;
        top: -9999px;
        left: -9999px;
    }

    .table-responsive-card tr {
        border: 1px solid #e3e6f0;
        border-radius: 0.5rem;
        margin-bottom: 0.75rem;
        background-color: #fff;
        padding: 0.5rem 0.75rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.05);
    }

    .table-responsive-card td { 
        border: none !important;
        position: relative;
        padding-left: 45% !important;
        text-align: right !important;
        min-height: 2.5rem;
        display: flex;
        align-items: center;
        justify-content: flex-end;
    }

    /* Menampilkan Label Kolom di Sebelah Kiri pada HP */
    .table-responsive-card td:before { 
        position: absolute;
        top: 50%;
        left: 0.75rem;
        transform: translateY(-50%);
        width: 40%; 
        padding-right: 10px; 
        white-space: nowrap;
        text-align: left;
        font-weight: bold;
        color: #4e73df;
        content: attr(data-label);
    }

    /* Tombol Aksi HP (Touch-Friendly) */
    .btn-action-container .btn {
        padding: 0.4rem 0.8rem;
        font-size: 0.875rem;
    }
}
</style>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-2 mb-3">
    <h2 class="h3 mb-0 page-title text-center text-sm-start fw-bold">Data Jenis Pelanggaran</h2>
    <a href="index.php?page=jenis_tambah" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Tambah Pelanggaran
    </a>
</div>

<div class="card shadow-sm mb-4 border-0">
    <div class="card-body bg-light rounded p-3 p-md-4">
        <form method="GET" action="index.php" class="row g-2 g-md-3">
            <input type="hidden" name="page" value="jenis">
            
            <div class="col-12 col-md-5">
                <label class="form-label d-md-none fw-semibold small text-muted">Cari Pelanggaran</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="cari" class="form-control" placeholder="Cari nama pelanggaran..." value="<?= htmlspecialchars($cari); ?>">
                </div>
            </div>
            
            <div class="col-12 col-md-4">
                <label class="form-label d-md-none fw-semibold small text-muted">Filter Poin</label>
                <select name="filter_poin" class="form-select">
                    <option value="">-- Semua Poin --</option>
                    <?php 
                    if ($query_angka_poin) {
                        while ($row_poin = mysqli_fetch_assoc($query_angka_poin)) {
                            $angka = $row_poin['poin'];
                            $selected = ($filter_poin !== '' && intval($filter_poin) === intval($angka)) ? 'selected' : '';
                            echo "<option value='".$angka."' ".$selected.">".$angka." Poin</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-secondary flex-fill">
                    <i class="fas fa-filter me-1"></i> <span>Tampilkan</span>
                </button>
                <a href="index.php?page=jenis" class="btn btn-outline-secondary flex-fill text-center d-flex align-items-center justify-content-center">
                    <i class="fas fa-sync-alt me-1"></i> <span>Reset</span>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-body p-2 p-md-0">
        <div class="table-responsive-card">
            <table class="table table-hover align-middle mb-0 bg-white">
                <thead class="table-dark text-nowrap">
                    <tr>
                        <th width="5%" class="text-center">No</th>
                        <th>Nama Pelanggaran</th>
                        <th width="20%" class="text-center">Bobot (Poin)</th>
                        <th width="15%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($query) == 0) {
                        echo "<tr><td colspan='4' class='text-center text-danger fw-bold py-4'>Data tidak ditemukan!</td></tr>";
                    } else {
                        while ($data = mysqli_fetch_array($query)) {
                            $poin_aktif = intval($data['poin']);
                    ?>
                    <tr>
                        <td data-label="No" class="text-md-center fw-bold"><?= $no++; ?></td>
                        <td data-label="Nama Pelanggaran" class="fw-semibold text-dark"><?= htmlspecialchars($data['nama_pelanggaran']); ?></td>
                        <td data-label="Bobot (Poin)" class="text-md-center">
                            <span class="badge bg-primary fs-6 px-3 py-2">
                                <?= $poin_aktif; ?> Poin
                            </span>
                        </td>
                        <td data-label="Aksi" class="text-md-center">
                            <div class="btn-action-container d-flex justify-content-end justify-content-md-center gap-1">
                                <a href="index.php?page=jenis_edit&id=<?= $data['id']; ?>" class="btn btn-sm btn-warning text-dark" title="Edit">
                                    <i class="fas fa-edit"></i> <span class="d-md-none ms-1">Edit</span>
                                </a>
                                <a href="index.php?page=jenis_hapus&id=<?= $data['id']; ?>" class="btn btn-sm btn-danger btn-hapus" title="Hapus">
                                    <i class="fas fa-trash"></i> <span class="d-md-none ms-1">Hapus</span>
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
<nav aria-label="Page navigation" class="mt-3">
    <ul class="pagination pagination-sm pagination-md-md flex-wrap justify-content-center justify-content-md-end mb-0">
        <li class="page-item <?= ($halaman <= 1) ? 'disabled' : ''; ?>">
            <a class="page-link" href="index.php?page=jenis&halaman=<?= $halaman - 1; ?>&cari=<?= urlencode($cari); ?>&filter_poin=<?= urlencode($filter_poin); ?>">
                <i class="fas fa-chevron-left d-md-none"></i><span class="d-none d-md-inline">Previous</span>
            </a>
        </li>

        <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
            <li class="page-item <?= ($halaman == $i) ? 'active' : ''; ?>">
                <a class="page-link" href="index.php?page=jenis&halaman=<?= $i; ?>&cari=<?= urlencode($cari); ?>&filter_poin=<?= urlencode($filter_poin); ?>"><?= $i; ?></a>
            </li>
        <?php endfor; ?>

        <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : ''; ?>">
            <a class="page-link" href="index.php?page=jenis&halaman=<?= $halaman + 1; ?>&cari=<?= urlencode($cari); ?>&filter_poin=<?= urlencode($filter_poin); ?>">
                <i class="fas fa-chevron-right d-md-none"></i><span class="d-none d-md-inline">Next</span>
            </a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tombolHapus = document.querySelectorAll('.btn-hapus');
    tombolHapus.forEach(tombol => {
        tombol.addEventListener('click', function(e) {
            e.preventDefault(); 
            const urlHapus = this.getAttribute('href'); 
            Swal.fire({
                title: 'Yakin hapus jenis pelanggaran ini?',
                text: "Data riwayat poin siswa yang berkaitan dengan jenis ini akan ikut terpengaruh!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
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