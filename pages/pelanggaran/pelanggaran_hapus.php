<?php
// 1. PASTIKAN SESSION AKTIF & ERROR REPORTING
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. HUBUNGKAN KE DATABASE (Menggunakan Jalur Absolut Aman)
$path_koneksi = dirname(__DIR__, 2) . '/koneksi.php';
if (file_exists($path_koneksi)) {
    include $path_koneksi;
} else {
    include '../../koneksi.php';
}

/** 
 * @var mysqli $conn 
 * @var mysqli $koneksi
 * @var mysqli $db
 */
$koneksi = isset($conn) ? $conn : (isset($db) ? $db : $koneksi);

if (!$koneksi instanceof mysqli) {
    die("Error: Objek koneksi database tidak valid. Periksa file koneksi.php Anda.");
}

// ========================================================
// 3. TANGKAP ID PELANGGARAN & MENU HALAMAN ASAL (REDIRECT)
// ========================================================
$id_hapus = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Tangkap asal tampilan menu (semua / pengelompokan)
if (isset($_GET['from']) && !empty($_GET['from'])) {
    $from_view = trim($_GET['from']);
} elseif (isset($_GET['view']) && !empty($_GET['view'])) {
    $from_view = trim($_GET['view']);
} elseif (isset($_SESSION['last_view_pelanggaran'])) {
    $from_view = $_SESSION['last_view_pelanggaran'];
} else {
    $from_view = 'semua'; // Default redirect
}

// URL Tujuan Redirect setelah proses hapus selesai
$redirect_url = "index.php?page=pelanggaran&view=" . urlencode($from_view);

// ========================================================
// 4. PROSES HAPUS DATA DARI DATABASE
// ========================================================
if ($id_hapus > 0) {
    // Gunakan Prepared Statement demi keamanan SQL Injection
    $stmt_del = $koneksi->prepare("DELETE FROM pelanggaran WHERE id = ?");
    $stmt_del->bind_param("i", $id_hapus);
    
    if ($stmt_del->execute()) {
        $stmt_del->close();
        echo "<script>
                alert('Berhasil! Catatan pelanggaran telah berhasil dihapus.');
                window.location.href = '{$redirect_url}';
              </script>";
        exit;
    } else {
        $stmt_del->close();
        echo "<script>
                alert('Gagal! Terjadi kesalahan saat menghapus data dari database.');
                window.location.href = '{$redirect_url}';
              </script>";
        exit;
    }
} else {
    // Jika ID tidak valid / 0
    echo "<script>
            alert('Peringatan! ID data pelanggaran tidak valid.');
            window.location.href = '{$redirect_url}';
          </script>";
    exit;
}
?>