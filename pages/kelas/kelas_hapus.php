<?php
/** @var mysqli $conn */
$id = $_GET['id'];

// Menghapus kelas 
$hapus = mysqli_query($conn, "DELETE FROM kelas WHERE id = '$id'");

if ($hapus) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Terhapus!',
                text: 'Data kelas berhasil dihapus.',
                icon: 'success',
                timer: 1500,
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
                text: 'Data kelas gagal dihapus.',
                icon: 'error'
            }).then(() => {
                window.location.href = 'index.php?page=kelas';
            });
        });
    </script>";
}
?>