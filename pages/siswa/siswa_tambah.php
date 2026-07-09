<?php
/** @var mysqli $conn */
// Proses Simpan Data
if (isset($_POST['simpan'])) {
    $nis      = $_POST['nis'];
    $nama     = $_POST['nama'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $id_kelas = $_POST['id_kelas'];
    $no_hp    = $_POST['no_hp'];

    // CEK DUPLIKASI DATA
    $cek_duplikat = mysqli_query($conn, "SELECT * FROM siswa WHERE nis = '$nis' OR nama = '$nama'");
    
    if (mysqli_num_rows($cek_duplikat) > 0) {
        // Jika NIS atau Nama sudah ada, munculkan peringatan
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Peringatan!',
                    text: 'NISN atau Nama Siswa sudah terdaftar di database. Silakan periksa kembali.',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
            });
        </script>";
    } else {
        // Jika tidak ada duplikat, lanjutkan proses simpan
        $simpan = mysqli_query($conn, "INSERT INTO siswa (nis, nama, jenis_kelamin, id_kelas, no_hp) VALUES ('$nis', '$nama', '$jenis_kelamin', '$id_kelas', '$no_hp')");
        
        if ($simpan) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Data siswa berhasil ditambahkan.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'index.php?page=siswa';
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
}
?>

<!-- Bagian form HTML di bawah ini tetap sama seperti sebelumnya -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="m-0"><i class="fas fa-plus"></i> Tambah Data Siswa</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">NIS (Nomor Induk Siswa)</label>
                <input type="text" name="nis" class="form-control" required placeholder="Masukkan NIS">
            </div>
            <div class="mb-3">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" required placeholder="Masukkan Nama Siswa">
            </div>
            <div class="mb-3">
                <label class="form-label">Jenis Kelamin</label>
                <select name="jenis_kelamin" class="form-select" required>
                    <option value="L">Laki-laki</option>
                    <option value="P">Perempuan</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Kelas</label>
                <select name="id_kelas" class="form-select" required>
                    <option value="">-- Pilih Kelas --</option>
                    <?php
                    $q_kelas = mysqli_query($conn, "SELECT * FROM kelas");
                    while($kelas = mysqli_fetch_array($q_kelas)){
                        echo "<option value='{$kelas['id']}'>{$kelas['nama_kelas']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">No. HP Orang Tua</label>
                <input type="text" name="no_hp" class="form-control" required placeholder="Contoh: 08123456789">
            </div>
            <button type="submit" name="simpan" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            <a href="index.php?page=siswa" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
        </form>
    </div>
</div>