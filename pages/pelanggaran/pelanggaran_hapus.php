<?php
/** @var mysqli $conn */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ambil parameter dari URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$source = isset($_GET['source']) ? $_GET['source'] : 'semua';

// 1. PROSES HAPUS UNTUK MENU PENGELOMPOKAN (Hapus semua pelanggaran siswa ini)
if ($source === 'pengelompokan') {
    $redirect_url = "index.php?page=pelanggaran&view=pengelompokan";
    
    if ($id <= 0) {
        echo "<script>alert('ID Siswa tidak valid!'); window.location.href = '$redirect_url';</script>";
        exit;
    }

    // Query menghapus semua records di tabel pelanggaran yang id_siswa-nya dipilih
    $sql_hapus = "DELETE FROM pelanggaran WHERE id_siswa = '$id'";
    $eksekusi = mysqli_query($conn, $sql_hapus);

    if ($eksekusi) {
        echo "<script>
                alert('Semua riwayat catatan pelanggaran siswa tersebut berhasil dibersihkan!');
                window.location.href = '$redirect_url';
              </script>";
    } else {
        echo "<script>
                alert('Gagal menghapus data: " . mysqli_error($conn) . "');
                window.location.href = '$redirect_url';
              </script>";
    }
    exit;
} 

// 2. PROSES HAPUS UNTUK MENU SEMUA (Hapus single kasus / default)
else {
    $redirect_url = "index.php?page=pelanggaran&view=semua";

    if ($id <= 0) {
        echo "<script>alert('ID Kasus Pelanggaran tidak valid!'); window.location.href = '$redirect_url';</script>";
        exit;
    }

    // Query menghapus satu kasus saja berdasarkan ID pelanggaran
    $sql_hapus = "DELETE FROM pelanggaran WHERE id = '$id'";
    $eksekusi = mysqli_query($conn, $sql_hapus);

    if ($eksekusi) {
        echo "<script>
                alert('Data catatan pelanggaran berhasil dihapus!');
                window.location.href = '$redirect_url';
              </script>";
    } else {
        echo "<script>
                alert('Gagal menghapus data: " . mysqli_error($conn) . "');
                window.location.href = '$redirect_url';
              </script>";
    }
    exit;
}
?>