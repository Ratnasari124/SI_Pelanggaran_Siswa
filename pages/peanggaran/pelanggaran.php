<?php
/** @var mysqli $conn */

// 1. Menangkap nilai dari URL jika form pencarian disubmit (Metode GET)
$cari = isset($_GET['cari']) ? mysqli_real_escape_string($conn, $_GET['cari']) : '';
$filter_bobot = isset($_GET['filter_bobot']) ? mysqli_real_escape_string($conn, $_GET['filter_bobot']) : '';

// 2. Ambil list tingkatan sanksi murni untuk mengisi isi pilihan dropdown secara otomatis dari database
$query_dropdown = mysqli_query($conn, "SELECT pelanggaran FROM sanksi GROUP BY min_poin, max_poin ORDER BY min_poin ASC");

// 3. Merakit query SQL dinamis berdasarkan kolom nama_pelanggaran dan poin
$kondisi = "";
if ($cari != '') {
    $kondisi .= " AND nama_pelanggaran LIKE '%$cari%'";
}

// JIKA USER MEMILIH DROPDOWN (Memecah string rentang poin 'min-max' menjadi rentang dinamis)
if ($filter_bobot != '') {
    $pecah_poin = explode('-', $filter_bobot);
    $min_pilih = intval($pecah_poin[0]);
    $max_pilih = intval($pecah_poin[1]);
    
    // Menyaring agar hanya memunculkan poin jenis pelanggaran yang berada tepat di dalam rentang tersebut
    $kondisi .= " AND poin >= '$min_pilih' AND poin <= '$max_pilih'";
}

// 4. Eksekusi query data jenis_pelanggaran utama
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
                    <?php 
                    while ($row_drop = mysqli_fetch_assoc($query_dropdown)) { 
                        // Menggabungkan nilai min dan max menjadi format value gabungan, contoh: '32-60'
                        $value_poin = $row_drop['min_poin'] . "-" . $row_drop['max_poin'];
                        
                        // Menjaga agar pilihan dropdown tidak kembali kosong setelah tombol tampilkan diklik
                        $selected = ($filter_bobot == $value_poin) ? 'selected' : '';
                        
                        echo "<option value='".$value_poin."' ".$selected.">";
                        echo htmlspecialchars($row_drop['nama_sanksi'])." (".$row_drop['min_poin']." - ".$row_drop['max_poin']." Poin)";
                        echo "</option>";
                    } 
                    ?>
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
            if (mysqli_num_rows($query) == 0) {
                echo "<tr><td colspan='4' class='text-center text-danger fw-bold py-3'>Data pelanggaran tidak ditemukan di tingkatan poin ini!</td></tr>";
            } else {
                while ($data = mysqli_fetch_array($query)) {
                    $poin_aktif = intval($data['poin']);
            ?>
            <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= htmlspecialchars($data['nama_pelanggaran']); ?></td>
                <td class="text-center">
                    <?php 
                        // Badge Klasifikasi warna dinamis otomatis menyesuaikan angka nilai poin murni
                        if ($poin_aktif >= 150) {
                            echo '<span class="badge bg-danger px-3 py-2 fs-7 w-100">' . $poin_aktif . ' Poin (Sangat Berat)</span>';
                        } elseif ($poin_aktif >= 75) {
                            echo '<span class="badge bg-warning text-dark px-3 py-2 fs-7 w-100">' . $poin_aktif . ' Poin (Berat)</span>';
                        } elseif ($poin_aktif >= 40) {
                            echo '<span class="badge bg-info text-dark px-3 py-2 fs-7 w-100">' . $poin_aktif . ' Poin (Sedang)</span>';
                        } else {
                            echo '<span class="badge bg-secondary px-3 py-2 fs-7 w-100">' . $poin_aktif . ' Poin (Ringan)</span>';
                        }
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