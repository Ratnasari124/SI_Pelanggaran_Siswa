<?php
/** @var mysqli $conn */

// 1. Menangkap nilai pencarian dari URL
$cari = isset($_GET['cari']) ? mysqli_real_escape_string($conn, trim($_GET['cari'])) : '';
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
$sql = "SELECT id, min_poin, max_poin, nama_sanksi FROM sanksi WHERE 1=1 $kondisi ORDER BY min_poin ASC";
$query = mysqli_query($conn, $sql);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Data Sanksi Pelanggaran</h2>
    <a href="index.php?page=sanksi_tambah" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Tambah Sanksi</a>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body bg-light p-3">
        <form method="GET" action="index.php" class="row g-2 align-items-center">
            <input type="hidden" name="page" value="sanksi">
            
            <div class="col-md-5">
                <input type="text" name="cari" class="form-control" placeholder="Cari nama sanksi..." value="<?= htmlspecialchars($cari); ?>">
            </div>
            
            <div class="col-md-4">
                <select name="filter_bobot" class="form-select">
                    <option value="">-- Semua Tingkatan Sanksi --</option>
                    <?php 
                    if ($query_dropdown && mysqli_num_rows($query_dropdown) > 0) {
                        while ($row_drop = mysqli_fetch_assoc($query_dropdown)) { 
                            $selected = ($filter_bobot == $row_drop['id']) ? 'selected' : '';
                            echo "<option value='".$row_drop['id']."' ".$selected.">";
                            echo htmlspecialchars($row_drop['nama_sanksi'])." (".$row_drop['min_poin']." - ".$row_drop['max_poin']." Poin)";
                            echo "</option>";
                        } 
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-secondary flex-fill"><i class="fas fa-search me-1"></i> Cari</button>
                <button type="button" class="btn btn-outline-secondary flex-fill" onclick="window.location.href='index.php?page=sanksi'"><i class="fas fa-sync-alt me-1"></i> Reset</button>
            </div>
        </form>
    </div>
</div>

<div class="table-responsive shadow-sm rounded">
    <table class="table table-bordered table-hover bg-white mb-0 align-middle">
        <thead class="table-dark text-center align-middle">
            <tr>
                <th width="5%">No</th>
                <th width="12%">Min Poin</th>
                <th width="12%">Max Poin</th>
                <th width="38%">Nama Sanksi</th>
                <th width="20%">Kategori Badge</th>
                <th width="13%">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (!$query || mysqli_num_rows($query) == 0) {
                echo "<tr><td colspan='6' class='text-center text-danger fw-bold py-4'>Data sanksi tidak ditemukan!</td></tr>";
            } else {
                $no = 1;
                while ($data = mysqli_fetch_assoc($query)) {
                    $min = intval($data['min_poin']);
                    $max = intval($data['max_poin']);
                    $range_poin = $min . " - " . $max . " Poin";
            ?>
            <tr>
                <!-- Nomor Urut -->
                <td class="text-center fw-bold"><?= $no++; ?></td>
                
                <!-- Rentang Poin Minimum & Maksimum -->
                <td class="text-center"><?= $min; ?> Poin</td>
                <td class="text-center"><?= $max; ?> Poin</td>
                
                <!-- Detail Sanksi -->
                <td><?= htmlspecialchars($data['nama_sanksi'] ?? ''); ?></td>
                
                <!-- Visual Badge Sanksi -->
                <td class="text-center">
                    <?php 
                        if ($min >= 150 || strpos(strtolower($data['nama_sanksi']), 'sangat berat') !== false) {
                            echo '<span class="badge bg-danger px-3 py-2 w-100">' . $range_poin . ' (Sangat Berat)</span>';
                        } elseif ($min >= 75 || strpos(strtolower($data['nama_sanksi']), 'berat') !== false) {
                            echo '<span class="badge bg-warning text-dark px-3 py-2 w-100">' . $range_poin . ' (Berat)</span>';
                        } elseif ($min >= 40 || strpos(strtolower($data['nama_sanksi']), 'sedang') !== false) {
                            echo '<span class="badge bg-info text-dark px-3 py-2 w-100">' . $range_poin . ' (Sedang)</span>';
                        } else {
                            echo '<span class="badge bg-secondary px-3 py-2 w-100">' . $range_poin . ' (Ringan)</span>';
                        }
                    ?>
                </td>
                
                <!-- Tombol Aksi -->
                <td class="text-center">
                    <div class="btn-group" role="group">
                        <a href="index.php?page=sanksi_edit&id=<?= $data['id']; ?>" class="btn btn-sm btn-warning text-dark me-1 rounded" title="Edit Data"><i class="fas fa-edit"></i> Edit</a>
                        <a href="index.php?page=sanksi_hapus&id=<?= $data['id']; ?>" class="btn btn-sm btn-danger btn-hapus rounded" title="Hapus Data"><i class="fas fa-trash"></i> Hapus</a>
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