<?php
/** @var mysqli $conn */
$id = $_GET['id'];
$query_data = mysqli_query($conn, "SELECT * FROM kelas WHERE id = '$id'");
$data = mysqli_fetch_array($query_data);

if (isset($_POST['update'])) {
    $nama_kelas   = $_POST['nama_kelas'];
    $tahun_ajaran = $_POST['tahun_ajaran']; // Menangkap input tahun ajaran
    $wali_kelas   = $_POST['wali_kelas'];

    // Update query UPDATE untuk mengubah tahun ajaran
    $update = mysqli_query($conn, "UPDATE kelas SET nama_kelas='$nama_kelas', tahun_ajaran='$tahun_ajaran', wali_kelas='$wali_kelas' WHERE id='$id'");
    
    if ($update) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Data kelas berhasil diubah.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'index.php?page=kelas';
                });
            });
        </script>";
    } else {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Gagal!',
                    text: 'Terjadi kesalahan saat mengubah data.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
        </script>";
    }
}
?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="m-0"><i class="fas fa-edit"></i> Edit Data Kelas</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Nama Kelas</label>
                <input type="text" name="nama_kelas" class="form-control" value="<?= $data['nama_kelas']; ?>" required>
            </div>
            
            <!-- INPUT TAHUN AJARAN DITAMBAHKAN DI SINI -->
            <div class="mb-3">
                <label class="form-label">Tahun Ajaran</label>
                <input type="text" name="tahun_ajaran" class="form-control" value="<?= $data['tahun_ajaran']; ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Nama Wali Kelas</label>
                <input type="text" name="wali_kelas" class="form-control" value="<?= $data['wali_kelas']; ?>" required>
            </div>
            <button type="submit" name="update" class="btn btn-warning"><i class="fas fa-save"></i> Update</button>
            <a href="index.php?page=kelas" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Batal</a>
        </form>
    </div>
</div>