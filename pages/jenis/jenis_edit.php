<?php
/** @var mysqli $conn */

// Mengaktifkan laporan error untuk memudahkan pelacakan jika ada kendala
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pesan = "";

// 1. MENGAMBIL DATA YANG AKAN DIEDIT BERDASARKAN ID
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Menggunakan nama tabel database Anda yang benar: 'data_pelanggaran'
    $sql_ambil = "SELECT * FROM jenis_pelanggaran WHERE id = $id";
    $query_ambil = mysqli_query($conn, $sql_ambil);
    $data = mysqli_fetch_assoc($query_ambil);

    // Jika data tidak ditemukan di database
    if (!$data) {
        echo "<div class='alert alert-danger fw-bold m-3'>Data pelanggaran tidak ditemukan!</div>";
        echo "<a href='index.php?page=jenis' class='btn btn-secondary ms-3'>Kembali</a>";
        exit;
    }
} else {
    echo "<div class='alert alert-danger fw-bold m-3'>ID Pelanggaran tidak ditentukan!</div>";
    echo "<a href='index.php?page=jenis' class='btn btn-secondary ms-3'>Kembali</a>";
    exit;
}

// 2. PROSES LOGIKA KETIKA TOMBOL SIMPAN/PERBAHARUI DIKLIK
if (isset($_POST['update_pelanggaran'])) {
    $nama_pelanggaran = mysqli_real_escape_string($conn, $_POST['nama_pelanggaran']);
    $poin             = intval($_POST['poin']);

    if (!empty($nama_pelanggaran) && $poin > 0) {
        // Query UPDATE ke tabel jenis_pelanggaran
        $sql_update = "UPDATE jenis_pelanggaran SET 
                        nama_pelanggaran = '$nama_pelanggaran', 
                        poin = '$poin' 
                       WHERE id = $id";
                       
        if (mysqli_query($conn, $sql_update)) {
            // Jika sukses update, langsung dialihkan kembali ke tabel utama jenis pelanggaran
            echo "<script>window.location.href='index.php?page=jenis';</script>";
            exit;
        } else {
            $pesan = "<div class='alert alert-danger fw-bold'>Gagal memperbarui data: " . mysqli_error($conn) . "</div>";
        }
    } else {
        $pesan = "<div class='alert alert-warning fw-bold'>Harap isi semua kolom dengan benar!</div>";
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Edit Jenis Pelanggaran</h2>
    <a href="index.php?page=jenis" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body bg-white p-4">
        
        <?= $pesan; ?>

        <form method="POST" action="">
            
            <div class="mb-3">
                <label for="nama_pelanggaran" class="form-label fw-bold">Nama / Detail Pelanggaran</label>
                <textarea class="form-control" id="nama_pelanggaran" name="nama_pelanggaran" rows="4" required><?= htmlspecialchars($data['nama_pelanggaran']); ?></textarea>
            </div>

            <div class="mb-4">
                <label for="poin" class="form-label fw-bold">Klasifikasi Bobot Poin</label>
                <select name="poin" id="poin" class="form-select" required>
                    <option value="">-- Pilih Tingkat Sanksi Pelanggaran --</option>
                    <option value="10" <?= ($data['poin'] == 10) ? 'selected' : ''; ?>>Ringan (10 Poin)</option>
                    <option value="40" <?= ($data['poin'] == 40) ? 'selected' : ''; ?>>Sedang (40 Poin)</option>
                    <option value="75" <?= ($data['poin'] == 75) ? 'selected' : ''; ?>>Berat (75 Poin)</option>
                    <option value="150" <?= ($data['poin'] == 150) ? 'selected' : ''; ?>>Sangat Berat (150 Poin)</option>
                </select>
            </div>

            <div class="border-top pt-3 d-flex justify-content-end">
                <a href="index.php?page=jenis" class="btn btn-outline-secondary me-2 px-4">Batal</a>
                <button type="submit" name="update_pelanggaran" class="btn btn-warning px-4 fw-bold text-dark"><i class="fas fa-save"></i> Simpan Perubahan</button>
            </div>

        </form>

    </div>
</div>