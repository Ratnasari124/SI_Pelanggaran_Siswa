<?php
/** @var mysqli $conn */

// 1. Pastikan parameter ID ada dan berupa angka di URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_hapus = intval($_GET['id']);

    // 2. Jalankan perintah DELETE di database berdasarkan ID pelanggaran
    $sql = "DELETE FROM pelanggaran WHERE id = '$id_hapus'";
    $query = mysqli_query($conn, $sql);

    if ($query) {
        // Jika berhasil dihapus, tampilkan SweetAlert lalu redirect
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Terhapus!',
                    text: 'Data pelanggaran siswa telah berhasil dihapus.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'index.php?page=pelanggaran';
                });
            });
        </script>";
    } else {
        // Jika gagal karena masalah database
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Gagal!',
                    text: 'Terjadi kesalahan saat menghapus data: " . mysqli_error($conn) . "',
                    icon: 'error'
                }).then(() => {
                    window.location.href = 'index.php?page=pelanggaran';
                });
            });
        </script>";
    }
} else {
    // Jika file ini diakses langsung tanpa mengirimkan ID lewat URL
    echo "<script>
        window.location.href = 'index.php?page=pelanggaran';
    </script>";
    exit;
}
?>