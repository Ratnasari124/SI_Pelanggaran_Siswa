<?php
/** @var mysqli $conn */

// 1. Cek apakah ada ID data yang dikirim melalui URL
if (isset($_GET['id'])) {
    // Mengamankan ID dengan intval agar hanya berupa angka
    $id = intval($_GET['id']);

    // 2. Jalankan query untuk menghapus data jenis pelanggaran berdasarkan ID per baris
    $sql = "DELETE FROM jenis_pelanggaran WHERE id = $id";
    
    if (mysqli_query($conn, $sql)) {
        // Jika berhasil dihapus, langsung dialihkan kembali ke halaman utama tabel jenis pelanggaran
        echo "<script>
            window.location.href = 'index.php?page=jenis';
        </script>";
        exit;
    } else {
        // Jika gagal karena pembatasan relasi database database (Foreign Key)
        echo "<script>
            alert('Gagal menghapus data: " . mysqli_error($conn) . "');
            window.location.href = 'index.php?page=jenis';
        </script>";
        exit;
    }
} else {
    // Jika tidak ada ID di URL, kembalikan ke halaman utama
    echo "<script>
        window.location.href = 'index.php?page=jenis';
    </script>";
    exit;
}
?>