<?php
// 1. PAKSA PHP UNTUK MENAMPILKAN ERROR (Agar tidak muncul layar putih polos jika ada yang salah)
error_reporting(E_ALL);
ini_set('display_errors', 1);

/** @var mysqli $conn */
// Pastikan variabel $conn dari file koneksi.php utama di index.php terbaca dengan baik
if (!isset($conn)) {
    die("<div class='alert alert-danger fw-bold'>Koneksi database tidak ditemukan! Pastikan file koneksi.php dimuat dengan benar di index.php.</div>");
}

$pesan = "";

// 2. PROSES JIKA TOMBOL SIMPAN DIKLIK
if (isset($_POST['simpan_pelanggaran'])) {
    $nama_pelanggaran = mysqli_real_escape_string($conn, $_POST['nama_pelanggaran']);
    $poin             = intval($_POST['poin']);

    if (!empty($nama_pelanggaran) && $poin > 0) {
        // Query insert data ke tabel jenis_pelanggaran
        $sql = "INSERT INTO jenis_pelanggaran (nama_pelanggaran, poin) VALUES ('$nama_pelanggaran', '$poin')";
        
        if (mysqli_query($conn, $sql)) {
            // Jika sukses, langsung dialihkan kembali ke halaman utama tabel jenis pelanggaran
            echo "<script>window.location.href='index.php?page=jenis';</script>";
            exit;
        } else {
            $pesan = "<div class='alert alert-danger fw-bold'>Gagal menyimpan ke database: " . mysqli_error($conn) . "</div>";
        }
    } else {
        $pesan = "<div class='alert alert-warning fw-bold'>Harap isi semua kolom dengan benar!</div>";
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Tambah Jenis Pelanggaran Baru</h2>
    <a href="index.php?page=jenis" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body bg-white p-4">
        
        <?= $pesan; ?>

        <form method="POST" action="">
            

            <div class="mb-3">
                <label for="nama_pelanggaran" class="form-label fw-bold">Nama / Detail Pelanggaran</label>
                <textarea class="form-control" id="nama_pelanggaran" name="nama_pelanggaran" rows="4" placeholder="Contoh: Berambut Gondrong, dicat dan potongan tidak rapi..." required></textarea>
            </div>

            <div class="mb-4">
                <label for="poin" class="form-label fw-bold">Klasifikasi Bobot Poin</label>
                <select name="poin" id="poin" class="form-select" required>
                    <option value="">-- Pilih Tingkat Sanksi Pelanggaran --</option>
                    <option value="10">Ringan (10 Poin)</option>
                    <option value="40">Sedang (40 Poin)</option>
                    <option value="75">Berat (75 Poin)</option>
                    <option value="150">Sangat Berat (150 Poin)</option>
                </select>
            </div>

            <div class="border-top pt-3 d-flex justify-content-end">
                <button type="reset" class="btn btn-outline-secondary me-2 px-4">Reset Form</button>
                <button type="submit" name="simpan_pelanggaran" class="btn btn-primary px-4 fw-bold"><i class="fas fa-save"></i> Simpan Data</button>
            </div>

        </form>

    </div>
</div>