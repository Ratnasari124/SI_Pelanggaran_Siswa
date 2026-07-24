<?php
/** @var mysqli $conn */

if (!isset($_GET['id'])) {
    header("Location: index.php?page=user");
    exit;
}

$id = $_GET['id'];

// 1. CEK: Apakah user ini sudah pernah menginput data di tabel pelanggaran?
// Sesuaikan nama kolom 'id_user' dengan yang ada di tabel pelanggaran Anda
$query_cek = "SELECT id FROM pelanggaran WHERE id_user = ?";
$stmt_cek = $conn->prepare($query_cek);
$stmt_cek->bind_param("i", $id);
$stmt_cek->execute();
$cek_riwayat = $stmt_cek->get_result();

$script = "";

if ($cek_riwayat->num_rows > 0) {
    $script = "Swal.fire({
        title: 'Tidak Bisa Dihapus!',
        text: 'User ini memiliki riwayat input data pelanggaran.',
        icon: 'warning',
        confirmButtonColor: '#3085d6',
        confirmButtonText: 'Kembali'
    }).then(() => { window.location.href = 'index.php?page=user'; });";
} else {
    // 2. PROSES HAPUS - Perhatikan nama tabelnya 'users' (dengan s)
    $query_hapus = "DELETE FROM users WHERE id = ?";
    $stmt_hapus = $conn->prepare($query_hapus);
    $stmt_hapus->bind_param("i", $id);

    if ($stmt_hapus->execute()) {
        $script = "Swal.fire({
            title: 'Terhapus!',
            text: 'Data user berhasil dihapus.',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
        }).then(() => { window.location.href = 'index.php?page=user'; });";
    } else {
        $script = "Swal.fire({
            title: 'Error!',
            text: 'Gagal menghapus data.',
            icon: 'error'
        }).then(() => { window.location.href = 'index.php?page=user'; });";
    }
}
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?= $script ?>
    });
</script>