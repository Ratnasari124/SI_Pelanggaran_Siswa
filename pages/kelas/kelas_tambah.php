<?php
/** @var mysqli $conn */
if (isset($_POST['simpan'])) {
    $nama_kelas   = $_POST['nama_kelas'];
    $tahun_ajaran = $_POST['tahun_ajaran']; // Menangkap input tahun ajaran
    $wali_kelas   = $_POST['wali_kelas'];

    // Update query INSERT untuk memasukkan tahun ajaran
    $simpan = mysqli_query($conn, "INSERT INTO kelas (nama_kelas, tahun_ajaran, wali_kelas) VALUES ('$nama_kelas', '$tahun_ajaran', '$wali_kelas')");
    
    if ($simpan) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Data kelas berhasil ditambahkan.',
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
                    text: 'Terjadi kesalahan saat menyimpan data.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
        </script>";
    }
}
?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="m-0"><i class="fas fa-plus"></i> Tambah Data Kelas</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Nama Kelas</label>
                <input type="text" name="nama_kelas" class="form-control" required placeholder="Contoh: 10 IPA 1">
            </div>
            
            <!-- INPUT TAHUN AJARAN DITAMBAHKAN DI SINI -->
            <div class="mb-3">
                <label class="form-label">Tahun Ajaran</label>
                <input type="text" name="tahun_ajaran" class="form-control" required placeholder="Contoh: 2024/2025">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Nama Wali Kelas</label>
                <input type="text" name="wali_kelas" class="form-control" required placeholder="Masukkan Nama Wali Kelas">
            </div>
            <button type="submit" name="simpan" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            <a href="index.php?page=kelas" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
        </form>
    </div>
</div>