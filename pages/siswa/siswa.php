<?php
/** @var mysqli $conn */

// Menangkap nilai pencarian dan filter
$cari = isset($_GET['cari']) ? $_GET['cari'] : '';
$filter_kelas = isset($_GET['filter_kelas']) ? $_GET['filter_kelas'] : '';
// Default status adalah 'Aktif' jika tidak ada filter yang dipilih
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : 'Aktif';

$kondisi = "";
if ($cari != '') {
    $kondisi .= " AND (siswa.nama LIKE '%$cari%' OR siswa.nis LIKE '%$cari%')";
}
if ($filter_kelas != '') {
    $kondisi .= " AND siswa.id_kelas = '$filter_kelas'";
}
// Filter Status: jika 'Semua', maka jangan tambahkan kondisi status
if ($filter_status != 'Semua') {
    $kondisi .= " AND siswa.status = '$filter_status'";
}

$sql = "SELECT siswa.*, kelas.nama_kelas, kelas.tahun_ajaran 
        FROM siswa 
        LEFT JOIN kelas ON siswa.id_kelas = kelas.id 
        WHERE 1=1 $kondisi 
        ORDER BY siswa.id DESC";

$query = mysqli_query($conn, $sql);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Data Siswa</h2>
    <a href="index.php?page=siswa_tambah" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Siswa</a>
</div>

<!-- FORM SEARCH & FILTER -->
<div class="card shadow-sm mb-4">
    <div class="card-body bg-light">
        <form method="GET" action="index.php" class="row g-2">
            <!-- Hidden input agar tetap berada di halaman siswa saat form disubmit -->
            <input type="hidden" name="page" value="siswa">
            
            <div class="col-md-3">
                <input type="text" name="cari" class="form-control" placeholder="Cari NIS atau Nama Siswa..." value="<?= $cari; ?>">
            </div>
            
            <div class="col-md-3">
                <select name="filter_kelas" class="form-select">
                    <option value="">Semua Kelas & Tahun Ajaran</option>
                    <?php
                    $q_kelas = mysqli_query($conn, "SELECT * FROM kelas ORDER BY tahun_ajaran DESC, nama_kelas ASC");
                    while($k = mysqli_fetch_array($q_kelas)){
                        $selected = ($filter_kelas == $k['id']) ? 'selected' : '';
                        // Menampilkan Nama Kelas beserta Tahun Ajarannya
                        echo "<option value='{$k['id']}' $selected>{$k['nama_kelas']} ({$k['tahun_ajaran']})</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="filter_status" class="form-select">
                    <option value="Semua" <?= ($filter_status == 'Semua') ? 'selected' : ''; ?>>Semua</option>
                    <option value="Aktif" <?= ($filter_status == 'Aktif') ? 'selected' : ''; ?>>Aktif</option>
                    <option value="Tidak Aktif" <?= ($filter_status == 'Tidak Aktif') ? 'selected' : ''; ?>>Tidak Aktif</option>
                </select>
            </div>
            
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Tampilkan</button>
            </div>
            <div class="col-md-2 d-grid">
                <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='index.php?page=siswa&filter_status=Aktif'"><i class="fas fa-sync-alt"></i> Reset</button>
            </div>
        </form>
    </div>
</div>
<!-- END FORM SEARCH & FILTER -->

<div class="table-responsive shadow-sm rounded">
    <table class="table table-bordered table-hover bg-white mb-0">
        <thead class="table-dark">
            <tr>
                <th width="5%">No</th>
                <th width="15%">NIS</th>
                <th>Nama Siswa</th>
                <th width="20%">Kelas (Tahun Ajaran)</th>
                <th width="10%">Status</th>
                <th width="12%">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            if(mysqli_num_rows($query) == 0){
                echo "<tr><td colspan='6' class='text-center text-danger fw-bold'>Data tidak ditemukan!</td></tr>";
            } else {
                while($data = mysqli_fetch_array($query)){
            ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= $data['nis']; ?></td>
                <td><?= $data['nama']; ?></td>
                <!-- Menampilkan Kelas + Tahun Ajaran -->
                <td><?= $data['nama_kelas']; ?> <span class="badge bg-info text-dark"><?= $data['tahun_ajaran']; ?></span></td>
                <td>
                    <span class="badge <?= ($data['status'] == 'Aktif') ? 'bg-success' : 'bg-danger' ?>">
                        <?= $data['status'] ?>
                    </span>
                </td>
                <td>
                    <a href="index.php?page=siswa_edit&id=<?= $data['id']; ?>" class="btn btn-sm btn-warning text-dark" title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="#" class="btn btn-sm btn-info text-white btn-detail" 
                        data-bs-toggle="modal" 
                        data-bs-target="#modalDetail" 
                        data-id="<?= $data['id']; ?>" 
                        title="Detail">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="index.php?page=siswa_hapus&id=<?= $data['id']; ?>" class="btn btn-sm btn-danger btn-hapus" title="Hapus">
                        <i class="fas fa-trash"></i>
                    </a>
                </td>
            </tr>
            <?php 
                } 
            } 
            ?>
        </tbody>
    </table>
    <!-- Modal Detail -->
<div class="modal fade" id="modalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailBody">
                <!-- Data akan dimuat disini oleh AJAX -->
                <div class="text-center">Memuat data...</div>
            </div>
        </div>
    </div>
</div>

<script>
// Skrip untuk menangkap klik tombol detail
document.querySelectorAll('.btn-detail').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        fetch('pages/siswa/siswa_detail.php?id=' + id)
            .then(response => response.text())
            .then(data => {
                document.getElementById('detailBody').innerHTML = data;
            });
    });
});
</script>
</div>

<!-- Script SweetAlert2 untuk Hapus (Tetap sama seperti sebelumnya) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tombolHapus = document.querySelectorAll('.btn-hapus');
    tombolHapus.forEach(tombol => {
        tombol.addEventListener('click', function(e) {
            e.preventDefault(); 
            const urlHapus = this.getAttribute('href'); 
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data siswa ini akan dihapus permanen!",
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