<?php
/** @var mysqli $conn */

// Mengaktifkan error reporting jika ada kendala (bisa dihapus jika sudah lancar)
ini_set('display_errors', 1);
error_reporting(E_ALL);

$pesan = "";

// 1. PROSES KETIKA TOMBOL SIMPAN DIKLIK
if (isset($_POST['simpan'])) {
    // SINKRONISASI: Menggunakan 'nama_sanksi' sesuai dengan tag name di HTML bawah
    $nama_sanksi = mysqli_real_escape_string($conn, $_POST['nama_sanksi']);
    $min_poin    = intval($_POST['min_poin']);
    $max_poin    = intval($_POST['max_poin']);

    // Validasi aturan logika poin sanksi
    if ($max_poin < $min_poin) {
        $pesan = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    <strong>Gagal!</strong> Nilai Poin Maksimal tidak boleh lebih kecil dari Poin Minimal.
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                  </div>";
    } elseif (empty($nama_sanksi)) {
        $pesan = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    <strong>Gagal!</strong> Nama Sanksi wajib diisi.
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                  </div>";
    } else {
        // Query INSERT yang sudah diperbaiki variabelnya ($nama_sanksi)
        $sql = "INSERT INTO sanksi (nama_sanksi, min_poin, max_poin) VALUES ('$nama_sanksi', '$min_poin', '$max_poin')";
        $query = mysqli_query($conn, $sql);

        if ($query) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Data tingkatan sanksi berhasil ditambahkan.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'index.php?page=sanksi';
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
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Tambah Tingkatan Sanksi</h2>
    <a href="index.php?page=sanksi" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<?= $pesan; ?>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="POST" action="">
            
            <div class="mb-3">
                <label class="form-label fw-bold">Nama Sanksi / Tingkatan</label>
                <input type="text" name="nama_sanksi" class="form-control" placeholder="Contoh: Peringatan Keras, Skorsing 3 Hari" required>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Minimal Akumulasi Poin</label>
                    <input type="number" name="min_poin" class="form-control" placeholder="Contoh: 40" min="0" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Maksimal Akumulasi Poin</label>
                    <input type="number" name="max_poin" class="form-control" placeholder="Contoh: 74" min="0" required>
                </div>
            </div>
            
            <hr>
            <div class="d-flex justify-content-end gap-2">
                <button type="submit" name="simpan" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Sanksi</button>
                <button type="reset" class="btn btn-outline-secondary">Reset</button>
            </div>
        </form>
    </div>
</div>