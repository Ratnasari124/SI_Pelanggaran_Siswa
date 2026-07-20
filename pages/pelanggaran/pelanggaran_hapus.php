<?php
// Aktifkan pelaporan error PHP untuk mempermudah pelacakan jika ada kendala
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. HUBUNGKAN KE DATABASE (Menggunakan Absolute Path agar anti-tersesat)
$path_koneksi = dirname(__DIR__, 2) . '/koneksi.php';
if (file_exists($path_koneksi)) {
    include $path_koneksi;
} else {
    include '../../koneksi.php';
}

// Sinkronisasi variabel koneksi database
if (isset($conn) && !isset($koneksi)) {
    $koneksi = $conn;
} elseif (isset($db) && !isset($koneksi)) {
    $koneksi = $db;
}

// Validasi koneksi
if (!isset($koneksi) || !$koneksi instanceof mysqli) {
    die("Error: Koneksi database gagal.");
}

// 2. TANGKAP PARAMETER DARI URL
$id_kasus = isset($_GET['id_kasus']) ? (int)$_GET['id_kasus'] : 0;
$asal = isset($_GET['asal']) ? $_GET['asal'] : '';
$source = isset($_GET['source']) ? $_GET['source'] : '';
$id_kelompok = isset($_GET['id_kelompok']) ? (int)$_GET['id_kelompok'] : 0;

if ($id_kasus == 0) {
    echo "<script>alert('ID Catatan tidak valid!'); window.history.back();</script>";
    exit;
}

// ========================================================
// FIX: MENGUBAH 'DELETE FROM kasus' MENJADI 'DELETE FROM pelanggaran'
// ========================================================
$query_hapus = "DELETE FROM pelanggaran WHERE id = ?";
$stmt = $koneksi->prepare($query_hapus);

if ($stmt) {
    $stmt->bind_param("i", $id_kasus);
    
    if ($stmt->execute()) {
        // 3. REDIRECT DINAMIS SESUAI ASAL HALAMAN PENGHAPUSAN
        if ($asal == 'detail' && $id_kelompok > 0) {
            // Kembali ke halaman detail rekap siswa semula
            echo "<script>
                    alert('Catatan pelanggaran berhasil dihapus!');
                    window.location.href = '../../index.php?page=pelanggaran_detail&id=" . $id_kelompok . "&source=" . $source . "';
                  </script>";
        } elseif ($asal == 'menu_semua') {
            // Kembali ke menu utama tab Semua Pelanggaran
            echo "<script>
                    alert('Catatan pelanggaran berhasil dihapus!');
                    window.location.href = '../../index.php?page=pelanggaran&view=semua';
                  </script>";
        } else {
            // Fallback default jika parameter asal tidak lengkap
            echo "<script>
                    alert('Catatan pelanggaran berhasil dihapus!');
                    window.location.href = '../../index.php?page=pelanggaran';
                  </script>";
        }
    } else {
        echo "<script>alert('Gagal menghapus data dari database.'); window.history.back();</script>";
    }
    $stmt->close();
} else {
    echo "Gagal mempersiapkan query hapus: " . $koneksi->error;
}
?>