<?php
/** @var mysqli $conn */

// 1. PERBAIKAN: Ambil nilai filter menggunakan POST agar tidak merusak routing URL template
$cari = isset($_POST['cari']) ? mysqli_real_escape_string($conn, $_POST['cari']) : '';
$filter_poin = isset($_POST['filter_poin']) ? mysqli_real_escape_string($conn, $_POST['filter_poin']) : '';

// 2. AMBIL DAFTAR PENGELOMPOKAN ANGKA POIN SECARA UNIK (DISTINCT) DARI DATABASE
$query_angka_poin = mysqli_query($conn, "SELECT DISTINCT poin FROM jenis_pelanggaran ORDER BY poin ASC");

// 3. Menyusun kondisi query pencarian SQL
$kondisi = "";

if ($cari != '') {
    $kondisi .= " AND nama_pelanggaran LIKE '%$cari%'";
}

if ($filter_poin != '') {
    $poin_pilihan = intval($filter_poin);
    $kondisi .= " AND poin = '$poin_pilihan'";
}

// 4. Jalankan query utama untuk menampilkan tabel data
$sql = "SELECT * FROM jenis_pelanggaran WHERE 1=1 $kondisi ORDER BY poin DESC, nama_pelanggaran ASC";
$query = mysqli_query($conn, $sql);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Data Jenis Pelanggaran</h2>
    <a href="index.php?page=jenis_tambah" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Pelanggaran</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body bg-light">
        <form method="POST" action="" class="row g-3">
            
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
                            // Menjaga agar pilihan dropdown tidak reset kembali setelah klik tombol Tampilkan
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

<div class="table-responsive shadow-sm rounded">
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
            $no = 1;
            if (mysqli_num_rows($query) == 0) {
                echo "<tr><td colspan='4' class='text-center text-danger fw-bold py-3'>Data tidak ditemukan untuk poin ini!</td></tr>";
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