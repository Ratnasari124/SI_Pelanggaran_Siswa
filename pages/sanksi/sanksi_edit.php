<?php
/** @var mysqli $conn */

// 1. Ambil ID dari parameter URL dan ambil data lama dari database
$id = $_GET['id'];
$query_data = mysqli_query($conn, "SELECT * FROM sanksi WHERE id = '$id'");
$data = mysqli_fetch_array($query_data);

// 2. Proses jika tombol update diklik
if (isset($_POST['update'])) {
    $nama_sanksi = mysqli_real_escape_string($conn, $_POST['nama_sanksi']);
    $min_poin    = intval($_POST['min_poin']);
    $max_poin    = intval($_POST['max_poin']);

    // Update data ke tabel sanksi
    $update = mysqli_query($conn, "UPDATE sanksi SET nama_sanksi='$nama_sanksi', min_poin='$min_poin', max_poin='$max_poin' WHERE id='$id'");
    
    if ($update) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Data sanksi berhasil diubah.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'index.php?page=sanksi';
                });
            });
        </script>";
    } else {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Gagal!',
                    text: 'Terjadi kesalahan saat mengubah data: " . mysqli_error($conn) . "',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
        </script>";
    }
}
?>

<div class="card shadow-sm mb-4 border-0">
    <div class="card-header bg-warning text-dark py-3">
        <h5 class="m-0 fw-bold"><i class="fas fa-edit"></i> Edit Data Sanksi</h5>
    </div>
    <div class="card-body p-4 bg-white">
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label fw-bold">Nama / Bentuk Sanksi</label>
                <textarea name="nama_sanksi" class="form-control" rows="4" required><?= htmlspecialchars($data['nama_sanksi']); ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold">Akumulasi Poin Minimal</label>
                    <input type="number" name="min_poin" class="form-control" value="<?= $data['min_poin']; ?>" min="0" required>
                </div>
                
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold">Akumulasi Poin Maksimal</label>
                    <input type="number" name="max_poin" class="form-control" value="<?= $data['max_poin']; ?>" min="0" required>
                </div>
            </div>
            
            <div class="border-top pt-3 d-flex justify-content-end">
                <a href="index.php?page=sanksi" class="btn btn-secondary me-2 px-4"><i class="fas fa-arrow-left"></i> Batal</a>
                <button type="submit" name="update" class="btn btn-warning px-4 fw-bold"><i class="fas fa-save"></i> Update Data</button>
            </div>
        </form>
    </div>
</div>