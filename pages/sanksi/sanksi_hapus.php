<?php
/** @var mysqli $conn */

// 1. Cek apakah ada ID data yang dikirim melalui URL
if (isset($_GET['id'])) {
    // Mengamankan ID dengan intval agar hanya berupa angka
    $id = intval($_GET['id']);

    // 2. Jalankan query untuk menghapus data sanksi berdasarkan ID per baris
    $sql = "DELETE FROM sanksi WHERE id = $id";
    
    if (mysqli_query($conn, $sql)) {
        // Jika berhasil dihapus, langsung dialihkan kembali ke halaman utama tabel sanksi
        echo "<script>
            window.location.href = 'index.php?page=sanksi';
        </script>";
        exit;
    } else {
        // Jika gagal karena pembatasan relasi database (Foreign Key) atau masalah lainnya
        echo "<script>
            alert('Gagal menghapus data sanksi: " . mysqli_error($conn) . "');
            window.location.href = 'index.php?page=sanksi';
        </script>";
        exit;
    }
} else {
    // Jika tidak ada ID di URL, kembalikan ke halaman utama tabel sanksi
    echo "<script>
        window.location.href = 'index.php?page=sanksi';
    </script>";
    exit;
}
?>