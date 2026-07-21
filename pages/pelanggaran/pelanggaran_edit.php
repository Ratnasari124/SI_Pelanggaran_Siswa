<?php
/** @var mysqli $conn */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. TENTUKAN ASAL MENU (Pengelompokan ATAU Semua)
if (isset($_GET['from_view']) && in_array($_GET['from_view'], ['pengelompokan', 'semua'])) {
    $from_view = $_GET['from_view'];
} else {
    // Fallback jika dari Session atau default
    $from_view = $_SESSION['last_view_pelanggaran'] ?? 'pengelompokan';
}

// Ambil ID Kasus Pelanggaran
$id_kasus = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';

// 2. PROSES UPDATE DATA JIKA FORM DISUBMIT
if (isset($_POST['update_pelanggaran'])) {
    $redirect_view = isset($_POST['from_view']) ? $_POST['from_view'] : $from_view;
    
    $tanggal          = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $id_jenis         = mysqli_real_escape_string($conn, $_POST['id_jenis']);
    $keterangan       = mysqli_real_escape_string($conn, $_POST['keterangan']);

    // Contoh Query Update
    $q_update = mysqli_query($conn, "UPDATE pelanggaran SET 
                                     tanggal = '$tanggal', 
                                     id_jenis = '$id_jenis', 
                                     keterangan = '$keterangan' 
                                     WHERE id = '$id_kasus'");

    if ($q_update) {
        echo "<script>
                alert('Data pelanggaran berhasil diperbarui!');
                window.location.href = 'index.php?page=pelanggaran&view=" . $redirect_view . "';
              </script>";
        exit;
    } else {
        echo "<script>alert('Gagal memperbarui data!');</script>";
    }
}

// 3. AMBIL DATA PELANGGARAN UNTUK DIEDIT
$q_detail = mysqli_query($conn, "SELECT p.*, s.nama AS nama_siswa, s.nis, k.nama_kelas 
                                 FROM pelanggaran p 
                                 JOIN siswa s ON p.id_siswa = s.id 
                                 LEFT JOIN kelas k ON s.id_kelas = k.id 
                                 WHERE p.id = '$id_kasus'");
$data = mysqli_fetch_assoc($q_detail);

if (!$data) {
    echo "<script>
            alert('Data pelanggaran tidak ditemukan!');
            window.location.href = 'index.php?page=pelanggaran&view=" . $from_view . "';
          </script>";
    exit;
}

// Ambil Pilihan Jenis Pelanggaran
$q_jenis = mysqli_query($conn, "SELECT * FROM jenis_pelanggaran ORDER BY nama_pelanggaran ASC");
?>

<div class="container-fluid py-3">

    <!-- TOMBOL KEMBALI (Dinamis: Kembali ke Pengelompokan / Semua sesuai asal) -->
    <div class="mb-3">
        <a href="index.php?page=pelanggaran&view=<?= htmlspecialchars($from_view) ?>" class="btn btn-secondary btn-sm px-3 shadow-sm">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Menu <?= $from_view == 'pengelompokan' ? 'Pengelompokan Siswa' : 'Semua Pelanggaran' ?>
        </a>
    </div>

    <!-- CARD FORM EDIT -->
    <div class="card border-0 shadow-sm rounded-3 bg-white p-4">
        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
            <h5 class="fw-bold text-dark mb-0">
                <i class="fas fa-edit text-warning me-2"></i>Edit Data Pelanggaran
            </h5>
            <span class="badge bg-<?= $from_view == 'pengelompokan' ? 'primary' : 'dark' ?> px-3 py-2">
                Asal Menu: <?= ucfirst($from_view) ?>
            </span>
        </div>

        <!-- INFORMASI SISWA (READONLY) -->
        <div class="row mb-3 bg-light p-3 rounded-2 mx-0">
            <div class="col-md-4 mb-2 mb-md-0">
                <small class="text-muted d-block">NIS / NISN</small>
                <span class="fw-bold text-dark"><?= htmlspecialchars($data['nis']) ?></span>
            </div>
            <div class="col-md-4 mb-2 mb-md-0">
                <small class="text-muted d-block">Nama Siswa</small>
                <span class="fw-bold text-dark"><?= htmlspecialchars($data['nama_siswa']) ?></span>
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">Kelas</small>
                <span class="fw-bold text-dark"><?= htmlspecialchars($data['nama_kelas'] ?? '-') ?></span>
            </div>
        </div>

        <form method="POST" action="">
            <!-- INPUT HIDDEN UNTUK MENJAGA ASAL VIEW -->
            <input type="hidden" name="from_view" value="<?= htmlspecialchars($from_view) ?>">

            <div class="row g-3">
                <!-- TANGGAL -->
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Tanggal Kejadian</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= $data['tanggal'] ?>" required>
                </div>

                <!-- JENIS PELANGGARAN -->
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Jenis Pelanggaran</label>
                    <select name="id_jenis" class="form-select" required>
                        <option value="">-- Pilih Jenis Pelanggaran --</option>
                        <?php while ($j = mysqli_fetch_assoc($q_jenis)): ?>
                            <option value="<?= $j['id'] ?>" <?= $j['id'] == $data['id_jenis'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($j['nama_pelanggaran']) ?> (+<?= $j['poin'] ?> Poin)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- KETERANGAN -->
                <div class="col-12">
                    <label class="form-label small fw-semibold">Keterangan / Catatan Tambahan</label>
                    <textarea name="keterangan" class="form-control" rows="3" placeholder="Tambahkan catatan jika ada..."><?= htmlspecialchars($data['keterangan']) ?></textarea>
                </div>
            </div>

            <!-- TOMBOL AKSI -->
            <div class="mt-4 pt-3 border-top d-flex gap-2">
                <button type="submit" name="update_pelanggaran" class="btn btn-primary btn-sm px-4">
                    <i class="fas fa-save me-1"></i> Simpan Perubahan
                </button>

                <!-- TOMBOL BATAL (Arah kembalinya sama persis dengan tombol Kembali) -->
                <a href="index.php?page=pelanggaran&view=<?= htmlspecialchars($from_view) ?>" class="btn btn-light btn-sm border px-3">
                    Batal
                </a>
            </div>
        </form>
    </div>

</div>