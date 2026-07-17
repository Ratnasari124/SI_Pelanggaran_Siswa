<?php
/** @var mysqli $conn */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pesan = "";

// 1. PASTIKAN ID DATA YANG AKAN DIEDIT TERSEDIA DI URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>window.location.href = 'index.php?page=pelanggaran';</script>";
    exit;
}

$id_edit = intval($_GET['id']);

// 2. PROSES KETIKA TOMBOL UPDATE DIKLIK
if (isset($_POST['update'])) {
    $id_siswa   = intval($_POST['id_siswa']);
    $id_jenis   = intval($_POST['id_jenis']);
    $tanggal    = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $id_user_pilihan = isset($_POST['id_user_petugas']) ? intval($_POST['id_user_petugas']) : 0;
    
    // Validasi input wajib
    if (empty($id_siswa) || empty($id_jenis) || empty($tanggal) || empty($id_user_pilihan)) {
        $pesan = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    <strong>Gagal!</strong> Semua kolom wajib diisi.
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                  </div>";
    } else {
        // Query UPDATE data berdasarkan id
        $sql_update = "UPDATE pelanggaran SET 
                        id_siswa = '$id_siswa', 
                        id_jenis = '$id_jenis', 
                        tanggal = '$tanggal', 
                        keterangan = '$keterangan', 
                        id_user = '$id_user_pilihan' 
                       WHERE id = '$id_edit'";
        
        $query_update = mysqli_query($conn, $sql_update);

        if ($query_update) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Data pelanggaran siswa berhasil diperbarui.',
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
                        <strong>Gagal Memperbarui:</strong> " . mysqli_error($conn) . "
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                      </div>";
        }
    }
}

// 3. AMBIL DATA LAMA DARI DATABASE UNTUK DITAMPILKAN DI FORM
$query_lama = mysqli_query($conn, "SELECT * FROM pelanggaran WHERE id = '$id_edit'");
$data_lama  = mysqli_fetch_assoc($query_lama);

// Jika ID tidak ditemukan di database, tendang kembali ke halaman utama
if (!$data_lama) {
    echo "<script>window.location.href = 'index.php?page=pelanggaran';</script>";
    exit;
}

// 4. AMBIL DATA MASTER UNTUK OPSI DROPDOWN
$query_siswa = mysqli_query($conn, "SELECT id, nis, nama FROM siswa ORDER BY nama ASC");
$query_jenis = mysqli_query($conn, "SELECT id, nama_pelanggaran, poin FROM jenis_pelanggaran ORDER BY poin ASC, nama_pelanggaran ASC");
$query_petugas = mysqli_query($conn, "SELECT id, nama_lengkap, role FROM users WHERE role IN ('guru', 'provoost') ORDER BY role ASC, nama_lengkap ASC");
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Edit Catatan Pelanggaran Siswa</h2>
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
                            // KUNCI EDIT: Cek apakah ID ini sama dengan data lama
                            $selected = ($row_siswa['id'] == $data_lama['id_siswa']) ? 'selected' : '';
                            echo "<option value='".$row_siswa['id']."' ".$selected.">".htmlspecialchars($row_siswa['nis'])." - ".htmlspecialchars($row_siswa['nama'])."</option>";
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
                            // KUNCI EDIT: Cek apakah ID jenis sama dengan data lama
                            $selected = ($row_jenis['id'] == $data_lama['id_jenis']) ? 'selected' : '';
                            echo "<option value='".$row_jenis['id']."' ".$selected.">".htmlspecialchars($row_jenis['nama_pelanggaran'])." (+".$row_jenis['poin']." Poin)</option>";
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
                            // KUNCI EDIT: Cek apakah ID user pencatat sama dengan data lama
                            $selected = ($row_petugas['id'] == $data_lama['id_user']) ? 'selected' : '';
                            $role_display = ucfirst($row_petugas['role']); 
                            echo "<option value='".$row_petugas['id']."' ".$selected.">".htmlspecialchars($row_petugas['nama_lengkap'])." [".$role_display."]</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold">Tanggal Kejadian</label>
                <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($data_lama['tanggal']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold">Keterangan / Kronologi Singkat</label>
                <textarea name="keterangan" class="form-control" rows="3" placeholder="Contoh: Atribut seragam tidak lengkap."><?= htmlspecialchars($data_lama['keterangan']); ?></textarea>
            </div>
            
            <hr>
            <div class="d-flex justify-content-end gap-2">
                <button type="submit" name="update" class="btn btn-warning text-dark fw-bold"><i class="fas fa-save"></i> Perbarui Data</button>
                <a href="index.php?page=pelanggaran" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>