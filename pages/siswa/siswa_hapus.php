<?php
/** @var mysqli $conn */
$id = $_GET['id'];

// 1. CEK DULU: Apakah siswa ini punya riwayat di tabel pelanggaran?
$cek_pelanggaran = mysqli_query($conn, "SELECT id FROM pelanggaran WHERE id_siswa = '$id'");

if (mysqli_num_rows($cek_pelanggaran) > 0) {
    // JIKA ADA: Tolak proses hapus dan munculkan pop-up peringatan
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Tidak Bisa Dihapus!',
                text: 'Siswa ini tidak bisa dihapus karena masih memiliki riwayat data pelanggaran.',
                icon: 'warning',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Kembali'
            }).then(() => {
                window.location.href = 'index.php?page=siswa';
            });
        });
    </script>";
} else {
    // JIKA TIDAK ADA: Lanjutkan proses hapus ke database
    $hapus = mysqli_query($conn, "DELETE FROM siswa WHERE id = '$id'");

    if ($hapus) {
        // Notifikasi Sukses
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Terhapus!',
                    text: 'Data siswa berhasil dihapus.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'index.php?page=siswa';
                });
            });
        </script>";
    } else {
        // Notifikasi Error Sistem
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Error!',
                    text: 'Terjadi kesalahan saat menghapus data siswa.',
                    icon: 'error'
                }).then(() => {
                    window.location.href = 'index.php?page=siswa';
                });
            });
        </script>";
    }
}
?>