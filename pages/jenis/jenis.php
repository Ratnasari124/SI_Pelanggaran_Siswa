<?php
/** @var mysqli $conn */

// 1. Ambil nilai filter (Pencarian & Filter Poin)
$cari = isset($_POST['cari']) ? mysqli_real_escape_string($conn, $_POST['cari']) : (isset($_GET['cari']) ? mysqli_real_escape_string($conn, $_GET['cari']) : '');
$filter_poin = isset($_POST['filter_poin']) ? mysqli_real_escape_string($conn, $_POST['filter_poin']) : (isset($_GET['filter_poin']) ? mysqli_real_escape_string($conn, $_GET['filter_poin']) : '');

// 2. Ambil daftar pengelompokan angka poin secara unik untuk dropdown
$query_angka_poin = mysqli_query($conn, "SELECT DISTINCT poin FROM jenis_pelanggaran ORDER BY poin ASC");

// 3. Menyusun kondisi query SQL
$kondisi = "";

if ($cari != '') {
    $kondisi .= " AND nama_pelanggaran LIKE '%$cari%'";
}

if ($filter_poin != '') {
    $poin_pilihan = intval($filter_poin);
    $kondisi .= " AND poin = '$poin_pilihan'";
}

// 4. PENGATURAN PAGINASI (7 Data per Halaman)
$limit = 7;
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$halaman_awal = ($halaman > 1) ? ($halaman * $limit) - $limit : 0;

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

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Data Jenis Pelanggaran</h2>
    <a href="index.php?page=jenis_tambah" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Pelanggaran</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body bg-light">
        <form method="POST" action="index.php?page=jenis" class="row g-3">
            
            <div class="col-md-4">
                <input type="text" name="cari" class="form-control" placeholder="Cari nama pelanggaran..." value="<?= htmlspecialchars($cari); ?>">
            </div>
            
            <div class="col-md-4">
                <select name="filter_poin" class="form-select">
                    <option value="">-- Semua Poin --</option>
                    <?php 
                    if ($query_angka_poin) {
                        while ($row_poin = mysqli_fetch_assoc($query_angka_poin)) {
                            $angka = $row_poin['poin'];
                            $selected = ($filter_poin != '' && intval($filter_poin) === intval($angka)) ? 'selected' : '';
                            echo "<option value='".$angka."' ".$selected.">".$angka." Poin</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Tampilkan</button>
            </div>
            <div class="col-md-2 d-grid">
                <a href="index.php?page=jenis" class="btn btn-outline-secondary text-center py-2"><i class="fas fa-sync-alt"></i> Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="table-responsive shadow-sm rounded mb-3">
    <table class="table table-bordered table-hover bg-white mb-0">
        <thead class="table-dark">
            <tr>
                <th width="5%" class="text-center">No</th>
                <th>Nama Pelanggaran</th>
                <th width="25%" class="text-center">Bobot (Poin)</th>
                <th width="15%" class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (mysqli_num_rows($query) == 0) {
                echo "<tr><td colspan='4' class='text-center text-danger fw-bold py-3'>Data tidak ditemukan!</td></tr>";
            } else {
                while ($data = mysqli_fetch_array($query)) {
                    $poin_aktif = intval($data['poin']);
            ?>
            <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= htmlspecialchars($data['nama_pelanggaran']); ?></td>
                <td class="text-center fw-bold text-primary">
                    <?= $poin_aktif; ?> Poin
                </td>
                <td class="text-center">
                    <a href="index.php?page=jenis_edit&id=<?= $data['id']; ?>" class="btn btn-sm btn-warning text-dark" title="Edit"><i class="fas fa-edit"></i></a>
                    <a href="index.php?page=jenis_hapus&id=<?= $data['id']; ?>" class="btn btn-sm btn-danger btn-hapus" title="Hapus"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php 
                } 
            } 
            ?>
        </tbody>
    </table>
</div>

<!-- TOMBOL PAGINASI BOOTSTRAP -->
<?php if ($total_halaman > 1): ?>
<nav aria-label="Page navigation">
    <ul class="pagination justify-content-end">
        <!-- Tombol Previous -->
        <li class="page-item <?= ($halaman <= 1) ? 'disabled' : ''; ?>">
            <a class="page-link" href="index.php?page=jenis&halaman=<?= $halaman - 1; ?>&cari=<?= urlencode($cari); ?>&filter_poin=<?= urlencode($filter_poin); ?>">Previous</a>
        </li>

        <!-- Angka Halaman -->
        <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
            <li class="page-item <?= ($halaman == $i) ? 'active' : ''; ?>">
                <a class="page-link" href="index.php?page=jenis&halaman=<?= $i; ?>&cari=<?= urlencode($cari); ?>&filter_poin=<?= urlencode($filter_poin); ?>"><?= $i; ?></a>
            </li>
        <?php endfor; ?>

        <!-- Tombol Next -->
        <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : ''; ?>">
            <a class="page-link" href="index.php?page=jenis&halaman=<?= $halaman + 1; ?>&cari=<?= urlencode($cari); ?>&filter_poin=<?= urlencode($filter_poin); ?>">Next</a>
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