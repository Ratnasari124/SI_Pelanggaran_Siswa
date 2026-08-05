<?php
/** @var mysqli $conn */

// 1. Menangkap nilai dari URL jika form pencarian disubmit (sanitasi XSS & Input)
$cari = isset($_GET['cari']) ? trim($_GET['cari']) : '';
$filter_tahun = isset($_GET['filter_tahun']) ? trim($_GET['filter_tahun']) : '';

// 2. Merakit query SQL dinamis
$kondisi = "";
if ($cari != '') {
    $cari_db = mysqli_real_escape_string($conn, $cari);
    $kondisi .= " AND (nama_kelas LIKE '%$cari_db%' OR wali_kelas LIKE '%$cari_db%')";
}
if ($filter_tahun != '') {
    $filter_tahun_db = mysqli_real_escape_string($conn, $filter_tahun);
    $kondisi .= " AND tahun_ajaran = '$filter_tahun_db'";
}

// 3. Eksekusi query
$sql = "SELECT * FROM kelas WHERE 1=1 $kondisi ORDER BY tahun_ajaran DESC, nama_kelas ASC";
$query = mysqli_query($conn, $sql);
?>

<style>
/* CSS Tambahan untuk Mengoptimalkan Tampilan Responsif */

/* Penyesuaian Header pada Layar HP */
@media (max-width: 575.98px) {
    .page-header-title {
        font-size: 1.35rem;
    }
    
    /* Tampilan Tabel Ubah Menjadi Card View di HP agar Tidak Perlu Scroll Samping */
    .table-responsive-stack table, 
    .table-responsive-stack thead, 
    .table-responsive-stack tbody, 
    .table-responsive-stack th, 
    .table-responsive-stack td, 
    .table-responsive-stack tr { 
        display: block; 
    }
    
    .table-responsive-stack thead tr { 
        position: absolute;
        top: -9999px;
        left: -9999px;
    }
    
    .table-responsive-stack tr {
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        margin-bottom: 0.75rem;
        background-color: #fff;
        padding: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
    }
    
    .table-responsive-stack td { 
        border: none !important;
        position: relative;
        padding-left: 45% !important;
        text-align: right !important;
        min-height: 2.2rem;
        display: flex;
        align-items: center;
        justify-content: flex-end;
    }
    
    .table-responsive-stack td:before { 
        position: absolute;
        top: 50%;
        left: 0.75rem;
        transform: translateY(-50%);
        width: 40%; 
        padding-right: 10px; 
        white-space: nowrap;
        text-align: left;
        font-weight: bold;
        color: #495057;
        content: attr(data-label);
    }

    /* Membuat tombol aksi lebih besar & mudah ditekan jari di HP */
    .btn-action-group .btn {
        padding: 0.4rem 0.8rem;
        font-size: 0.9rem;
    }
}
</style>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-2 mb-3">
    <h2 class="h3 mb-0 page-header-title text-center text-sm-start fw-bold">Data Kelas</h2>
    <a href="index.php?page=kelas_tambah" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Tambah Kelas
    </a>
</div>

<div class="card shadow-sm mb-4 border-0">
    <div class="card-body bg-light rounded p-3 p-md-4">
        <form method="GET" action="index.php" class="row g-2 g-md-3">
            <input type="hidden" name="page" value="kelas">
            
            <div class="col-12 col-md-5">
                <label class="form-label d-md-none fw-semibold small text-muted">Pencarian</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="cari" class="form-control" placeholder="Cari Nama Kelas / Wali Kelas..." value="<?= htmlspecialchars($cari); ?>">
                </div>
            </div>
            
            <div class="col-12 col-md-4">
                <label class="form-label d-md-none fw-semibold small text-muted">Tahun Ajaran</label>
                <select name="filter_tahun" class="form-select">
                    <option value="">-- Semua Tahun Ajaran --</option>
                    <?php
                    $q_tahun = mysqli_query($conn, "SELECT DISTINCT tahun_ajaran FROM kelas ORDER BY tahun_ajaran DESC");
                    while($t = mysqli_fetch_array($q_tahun)){
                        $selected = ($filter_tahun == $t['tahun_ajaran']) ? 'selected' : '';
                        echo "<option value='".htmlspecialchars($t['tahun_ajaran'])."' $selected>".htmlspecialchars($t['tahun_ajaran'])."</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-secondary flex-fill">
                    <i class="fas fa-filter me-1"></i> <span>Tampilkan</span>
                </button>
                <button type="button" class="btn btn-outline-secondary flex-fill" onclick="window.location.href='index.php?page=kelas'">
                    <i class="fas fa-sync-alt me-1"></i> <span>Reset</span>
                </button>
            </div>
        </form>
    </div>
</div>
<div class="card shadow-sm border-0">
    <div class="card-body p-2 p-md-0">
        <div class="table-responsive-stack">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark text-nowrap">
                    <tr>
                        <th width="5%" class="text-center">No</th>
                        <th>Nama Kelas</th>
                        <th>Tahun Ajaran</th>
                        <th>Wali Kelas</th>
                        <th width="12%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    
                    if(mysqli_num_rows($query) == 0){
                        echo "<tr><td colspan='5' class='text-center text-danger fw-bold py-4'>Data kelas tidak ditemukan.</td></tr>";
                    } else {
                        while($data = mysqli_fetch_array($query)){
                    ?>
                    <tr>
                        <td data-label="No" class="text-md-center fw-bold"><?= $no++; ?></td>
                        <td data-label="Nama Kelas" class="fw-semibold text-primary"><?= htmlspecialchars($data['nama_kelas']); ?></td>
                        <td data-label="Tahun Ajaran"><?= htmlspecialchars($data['tahun_ajaran']); ?></td>
                        <td data-label="Wali Kelas"><?= htmlspecialchars($data['wali_kelas']); ?></td>
                        <td data-label="Aksi" class="text-md-center">
                            <div class="btn-action-group d-flex justify-content-end justify-content-md-center gap-1">
                                <a href="index.php?page=kelas_edit&id=<?= $data['id']; ?>" class="btn btn-sm btn-warning text-dark" title="Edit">
                                    <i class="fas fa-edit"></i> <span class="d-md-none ms-1">Edit</span>
                                </a>
                                <a href="index.php?page=kelas_hapus&id=<?= $data['id']; ?>" class="btn btn-sm btn-danger btn-hapus" title="Hapus">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tombolHapus = document.querySelectorAll('.btn-hapus');
    
    tombolHapus.forEach(tombol => {
        tombol.addEventListener('click', function(e) {
            e.preventDefault(); 
            const urlHapus = this.getAttribute('href'); 

            Swal.fire({
                title: 'Yakin hapus kelas ini?',
                text: "Data siswa yang ada di kelas ini juga akan ikut terhapus secara permanen!",
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