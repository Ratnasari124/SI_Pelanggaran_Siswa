<?php
/** @var mysqli $conn */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pesan = "";

// 1. PROSES KETIKA TOMBOL SIMPAN DIKLIK
if (isset($_POST['simpan'])) {
    $id_siswa   = intval($_POST['id_siswa']);
    $id_jenis   = intval($_POST['id_jenis']);
    $tanggal    = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    
    // Ambil ID Guru/Provoost yang dipilih secara manual dari form
    $id_user_pilihan = isset($_POST['id_user_petugas']) ? intval($_POST['id_user_petugas']) : 0;
    
    // Validasi input wajib
    if (empty($id_siswa) || empty($id_jenis) || empty($tanggal) || empty($id_user_pilihan)) {
        $pesan = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    <strong>Gagal!</strong> Semua kolom (Siswa, Pelanggaran, Tanggal, dan Petugas Penginput) wajib diisi.
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                  </div>";
    } else {
        // Query INSERT: id_user diisi dengan $id_user_pilihan (ID guru/provoost yang dipilih di form)
        $sql = "INSERT INTO pelanggaran (id_siswa, id_jenis, tanggal, keterangan, id_user) 
                VALUES ('$id_siswa', '$id_jenis', '$tanggal', '$keterangan', '$id_user_pilihan')";
        
        $query = mysqli_query($conn, $sql);

        if ($query) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Pelanggaran siswa berhasil dicatat.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'index.php?page=pelanggaran';
                    });
                });
            </script>";
        } else {
            $pesan = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                        <strong>Gagal Menyimpan:</strong> " . mysqli_error($conn) . "
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                      </div>";
        }
    }
}

// 2. AMBIL DATA SISWA UNTUK DROPDOWN
$query_siswa = mysqli_query($conn, "SELECT id, nis, nama FROM siswa ORDER BY nama ASC");

// 3. AMBIL DATA JENIS PELANGGARAN UNTUK DROPDOWN
$query_jenis = mysqli_query($conn, "SELECT id, nama_pelanggaran, poin FROM jenis_pelanggaran ORDER BY poin ASC, nama_pelanggaran ASC");

// 4. AMBIL DATA GURU DAN PROVOOST SAJA DARI TABEL USERS (Admin dikecualikan)
$query_petugas = mysqli_query($conn, "SELECT id, nama_lengkap, role FROM users WHERE role IN ('guru', 'provoost') ORDER BY role ASC, nama_lengkap ASC");
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Catat Pelanggaran Siswa</h2>
    <a href="index.php?page=pelanggaran" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<?= $pesan; ?>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="POST" action="">
            
            <div class="mb-3">
                <label class="form-label fw-bold">Pilih Siswa</label>
                <select name="id_siswa" class="form-select select2" required>
                    <option value="">-- Cari & Pilih Siswa --</option>
                    <?php 
                    if ($query_siswa) {
                        while ($row_siswa = mysqli_fetch_assoc($query_siswa)) {
                            echo "<option value='".$row_siswa['id']."'>".htmlspecialchars($row_siswa['nis'])." - ".htmlspecialchars($row_siswa['nama'])."</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold">Bentuk Pelanggaran</label>
                <select name="id_jenis" class="form-select select2" required>
                    <option value="">-- Pilih Jenis Pelanggaran --</option>
                    <?php 
                    if ($query_jenis) {
                        while ($row_jenis = mysqli_fetch_assoc($query_jenis)) {
                            echo "<option value='".$row_jenis['id']."'>".htmlspecialchars($row_jenis['nama_pelanggaran'])." (+".$row_jenis['poin']." Poin)</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Petugas Pencatat (Guru / Provoost)</label>
                <select name="id_user_petugas" class="form-select select2" required>
                    <option value="">-- Pilih Guru / Provoost yang Menindak --</option>
                    <?php 
                    if ($query_petugas && mysqli_num_rows($query_petugas) > 0) {
                        while ($row_petugas = mysqli_fetch_assoc($query_petugas)) {
                            // Menampilkan nama lengkap beserta rolenya agar jelas (contoh: Ahmad, S.Pd (guru) atau Sertu Budi (provoost))
                            $role_display = ucfirst($row_petugas['role']); // Membuat huruf pertama kapital (Guru / Provoost)
                            echo "<option value='".$row_petugas['id']."'>".htmlspecialchars($row_petugas['nama_lengkap'])." [".$role_display."]</option>";
                        }
                    } else {
                        echo "<option value='' disabled>Tidak ada data Guru atau Provoost di tabel users!</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold">Tanggal Kejadian</label>
                <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d'); ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold">Keterangan / Kronologi Singkat</label>
                <textarea name="keterangan" class="form-control" rows="3" placeholder="Contoh: Atribut seragam tidak lengkap saat upacara bendera."></textarea>
            </div>
            
            <hr>
            <div class="d-flex justify-content-end gap-2">
                <button type="submit" name="simpan" class="btn btn-danger"><i class="fas fa-save"></i> Simpan Catatan</button>
                <button type="reset" class="btn btn-outline-secondary">Reset</button>
            </div>
        </form>
    </div>
</div>