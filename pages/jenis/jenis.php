<?php
/** @var mysqli $conn */

// 1. Menangkap nilai dari URL jika form pencarian disubmit
$cari = isset($_GET['cari']) ? $_GET['cari'] : '';
$filter_bobot = isset($_GET['filter_bobot']) ? $_GET['filter_bobot'] : '';

// 2. Merakit query SQL dinamis berdasarkan kolom nama_pelanggaran dan poin
$kondisi = "";
if ($cari != '') {
    $kondisi .= " AND nama_pelanggaran LIKE '%$cari%'";
}

if ($filter_bobot != '') {
    if ($filter_bobot == 'Sangat Berat')  $kondisi .= " AND poin = 150";
    elseif ($filter_bobot == 'Berat')     $kondisi .= " AND poin = 75";
    elseif ($filter_bobot == 'Sedang')    $kondisi .= " AND poin = 40";
    elseif ($filter_bobot == 'Ringan')    $kondisi .= " AND poin = 10";
}

// 3. Eksekusi query data_pelanggaran
$sql = "SELECT * FROM jenis_pelanggaran WHERE 1=1 $kondisi ORDER BY poin DESC, nama_pelanggaran ASC";
$query = mysqli_query($conn, $sql);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Data Jenis Pelanggaran</h2>
    <a href="index.php?page=jenis_tambah" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Pelanggaran</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body bg-light">
        <form method="GET" action="index.php" class="row g-3">
            <input type="hidden" name="page" value="jenis">
            
            <div class="col-md-4">
                <input type="text" name="cari" class="form-control" placeholder="Cari nama pelanggaran..." value="<?= htmlspecialchars($cari); ?>">
            </div>
            
            <div class="col-md-4">
                <select name="filter_bobot" class="form-select">
                    <option value="">-- Semua Tingkatan Poin --</option>
                    <option value="Sangat Berat" <?= $filter_bobot == 'Sangat Berat' ? 'selected' : ''; ?>>Sangat Berat (150 Poin)</option>
                    <option value="Berat" <?= $filter_bobot == 'Berat' ? 'selected' : ''; ?>>Berat (75 Poin)</option>
                    <option value="Sedang" <?= $filter_bobot == 'Sedang' ? 'selected' : ''; ?>>Sedang (40 Poin)</option>
                    <option value="Ringan" <?= $filter_bobot == 'Ringan' ? 'selected' : ''; ?>>Ringan (10 Poin)</option>
                </select>
            </div>
            
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Tampilkan</button>
            </div>
            <div class="col-md-2 d-grid">
                <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='index.php?page=jenis'"><i class="fas fa-sync-alt"></i> Reset</button>
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
                <th width="25%" class="text-center">Klasifikasi Bobot (Poin)</th>
                <th width="15%" class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            if(mysqli_num_rows($query) == 0){
                echo "<tr><td colspan='4' class='text-center text-danger fw-bold'>Data tidak ditemukan!</td></tr>";
            } else {
                while($data = mysqli_fetch_array($query)){
            ?>
            <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= htmlspecialchars($data['nama_pelanggaran']); ?></td>
                <td class="text-center">
                    <?php 
                        if($data['poin'] >= 150)       echo '<span class="badge bg-danger px-3 py-2 fs-7 w-100">Sangat Berat (150 Poin)</span>';
                        elseif($data['poin'] >= 75)   echo '<span class="badge bg-warning text-dark px-3 py-2 fs-7 w-100">Berat (75 Poin)</span>';
                        elseif($data['poin'] >= 40)   echo '<span class="badge bg-info text-dark px-3 py-2 fs-7 w-100">Sedang (40 Poin)</span>';
                        else                          echo '<span class="badge bg-secondary px-3 py-2 fs-7 w-100">Ringan (10 Poin)</span>';
                    ?>
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