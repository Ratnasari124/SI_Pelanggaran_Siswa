<?php
/** @var mysqli $conn */
$id = $_GET['id'];
$query_data = mysqli_query($conn, "SELECT * FROM siswa WHERE id = '$id'");
$data = mysqli_fetch_array($query_data);

// Proses Update Data
if (isset($_POST['update'])) {
    $nis           = $_POST['nis'];
    $nama          = $_POST['nama'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $id_kelas      = $_POST['id_kelas'];
    $no_hp         = $_POST['no_hp'];
    $status        = $_POST['status'];

    // CEK DUPLIKASI DATA (Kecuali data dirinya sendiri)
    $cek_duplikat = mysqli_query($conn, "SELECT * FROM siswa WHERE (nis = '$nis' OR nama = '$nama') AND id != '$id'");
    
    if (mysqli_num_rows($cek_duplikat) > 0) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Peringatan!',
                    text: 'NIS atau Nama Siswa sudah digunakan oleh siswa lain.',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
            });
        </script>";
    } else {
        // Update tanpa menyentuh created_at
        $update = mysqli_query($conn, "UPDATE siswa SET 
            nis='$nis', 
            nama='$nama', 
            jenis_kelamin='$jenis_kelamin', 
            id_kelas='$id_kelas', 
            no_hp='$no_hp', 
            status='$status' 
            WHERE id='$id'");
        
        if ($update) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Data siswa berhasil diubah.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'index.php?page=siswa';
                    });
                });
            </script>";
        }
    }
}
?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="m-0"><i class="fas fa-edit"></i> Edit Data Siswa</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">NIS</label>
                <input type="text" name="nis" class="form-control" value="<?= $data['nis']; ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" value="<?= $data['nama']; ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Jenis Kelamin</label>
                <select name="jenis_kelamin" class="form-select" required>
                    <option value="L" <?= ($data['jenis_kelamin'] == 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                    <option value="P" <?= ($data['jenis_kelamin'] == 'P') ? 'selected' : ''; ?>>Perempuan</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Kelas</label>
                <select name="id_kelas" class="form-select" required>
                    <?php
                    $q_kelas = mysqli_query($conn, "SELECT * FROM kelas");
                    while($kelas = mysqli_fetch_array($q_kelas)){
                        $selected = ($kelas['id'] == $data['id_kelas']) ? 'selected' : '';
                        echo "<option value='{$kelas['id']}' $selected>{$kelas['nama_kelas']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    <option value="Aktif" <?= ($data['status'] == 'Aktif') ? 'selected' : ''; ?>>Aktif</option>
                    <option value="Tidak Aktif" <?= ($data['status'] == 'Tidak Aktif') ? 'selected' : ''; ?>>Tidak Aktif</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">No. HP Orang Tua</label>
                <input type="text" name="no_hp" class="form-control" value="<?= $data['no_hp']; ?>" required>
            </div>
            <button type="submit" name="update" class="btn btn-warning"><i class="fas fa-save"></i> Update</button>
            <a href="index.php?page=siswa" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Batal</a>
        </form>
    </div>
</div>