<?php
/** @var mysqli $conn */

// 1. Menangkap nilai pencarian dari URL
$cari = isset($_GET['cari']) ? mysqli_real_escape_string($conn, $_GET['cari']) : '';
$filter_bobot = isset($_GET['filter_bobot']) ? mysqli_real_escape_string($conn, $_GET['filter_bobot']) : '';

// 2. Ambil semua list sanksi dari database untuk mengisi isi dropdown secara dinamis
$query_dropdown = mysqli_query($conn, "SELECT id, nama_sanksi, min_poin, max_poin FROM sanksi ORDER BY min_poin ASC");

// 3. Menyusun kondisi query pencarian tabel utama
$kondisi = "";

if ($cari != '') {
    $kondisi .= " AND nama_sanksi LIKE '%$cari%'";
}

// Jika user memilih salah satu sanksi di dropdown dinamis (pencarian berdasarkan ID sanksi)
if ($filter_bobot != '') {
    $kondisi .= " AND id = '$filter_bobot'";
}

// 4. Eksekusi query untuk data tabel sanksi
$sql = "SELECT * FROM sanksi WHERE 1=1 $kondisi ORDER BY min_poin ASC";
$query = mysqli_query($conn, $sql);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Data Sanksi Pelanggaran</h2>
    <a href="index.php?page=sanksi_tambah" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Sanksi</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body bg-light">
        <form method="GET" action="index.php" class="row g-3">
            <input type="hidden" name="page" value="sanksi">
            
            <div class="col-md-4">
                <input type="text" name="cari" class="form-control" placeholder="Cari nama sanksi..." value="<?= htmlspecialchars($cari); ?>">
            </div>
            
            <div class="col-md-4">
                <select name="filter_bobot" class="form-select">
                    <option value="">-- Semua Tingkatan Sanksi --</option>
                    <?php 
                    while ($row_drop = mysqli_fetch_assoc($query_dropdown)) { 
                        // Menentukan apakah opsi ini sedang dipilih/diselect
                        $selected = ($filter_bobot == $row_drop['id']) ? 'selected' : '';
                        
                        // Menampilkan nama sanksi beserta rentang poinnya secara otomatis
                        echo "<option value='".$row_drop['id']."' ".$selected.">";
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
                <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='index.php?page=sanksi'"><i class="fas fa-sync-alt"></i> Reset</button>
            </div>
        </form>
    </div>
</div>

<div class="table-responsive shadow-sm rounded">
    <table class="table table-bordered table-hover bg-white mb-0">
        <thead class="table-dark">
            <tr>
                <th width="5%" class="text-center">No</th>
                <th>Nama / Bentuk Sanksi</th>
                <th width="25%" class="text-center">Rentang Akumulasi Poin</th>
                <th width="15%" class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            if (mysqli_num_rows($query) == 0) {
                echo "<tr><td colspan='4' class='text-center text-danger fw-bold py-3'>Data sanksi tidak ditemukan!</td></tr>";
            } else {
                while ($data = mysqli_fetch_array($query)) {
                    $min = intval($data['min_poin']);
                    $max = intval($data['max_poin']);
                    $range_poin = $min . " - " . $max . " Poin";
            ?>
            <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= htmlspecialchars($data['nama_sanksi'] ?? ''); ?></td>
                <td class="text-center">
                    <?php 
                        // Badge warna dinamis otomatis berdasarkan nama sanksi atau rentang poin
                        if ($min >= 150 || strpos(strtolower($data['nama_sanksi']), 'sangat berat') !== false) {
                            echo '<span class="badge bg-danger px-3 py-2 fs-7 w-100">' . $range_poin . ' (Sangat Berat)</span>';
                        } elseif ($min >= 75 || strpos(strtolower($data['nama_sanksi']), 'berat') !== false) {
                            echo '<span class="badge bg-warning text-dark px-3 py-2 fs-7 w-100">' . $range_poin . ' (Berat)</span>';
                        } elseif ($min >= 40 || strpos(strtolower($data['nama_sanksi']), 'sedang') !== false) {
                            echo '<span class="badge bg-info text-dark px-3 py-2 fs-7 w-100">' . $range_poin . ' (Sedang)</span>';
                        } else {
                            echo '<span class="badge bg-secondary px-3 py-2 fs-7 w-100">' . $range_poin . ' (Ringan)</span>';
                        }
                    ?>
                </td>
                <td class="text-center">
                    <a href="index.php?page=sanksi_edit&id=<?= $data['id']; ?>" class="btn btn-sm btn-warning text-dark"><i class="fas fa-edit"></i></a>
                    <a href="index.php?page=sanksi_hapus&id=<?= $data['id']; ?>" class="btn btn-sm btn-danger btn-hapus"><i class="fas fa-trash"></i></a>
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
                title: 'Yakin hapus data sanksi ini?',
                text: "Tindakan ini tidak dapat dibatalkan!",
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