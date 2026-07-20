<?php
/** @var mysqli $conn */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. MENGAMBIL PARAMETER URL
$id     = isset($_GET['id']) ? intval($_GET['id']) : 0;
$source = isset($_GET['source']) ? $_GET['source'] : 'semua';

// Menentukan URL Kembali
$back_url = "index.php?page=pelanggaran&view=semua";
if ($source === 'pengelompokan') {
    $back_url = "index.php?page=pelanggaran&view=pengelompokan";
}

// 2. PROSES UPDATE DATA SAAT FORM DISUBMIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pelanggaran = intval($_POST['id_pelanggaran']);
    $id_jenis       = intval($_POST['id_jenis']);
    $id_user        = intval($_POST['id_user']);
    $tanggal        = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $keterangan     = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $redirect_to    = mysqli_real_escape_string($conn, $_POST['redirect_to']);

    if ($id_pelanggaran > 0 && $id_jenis > 0) {
        $sql_update = "UPDATE pelanggaran SET 
                        id_jenis = '$id_jenis',
                        id_user = '$id_user',
                        tanggal = '$tanggal',
                        keterangan = '$keterangan'
                       WHERE id = '$id_pelanggaran'";

        if (mysqli_query($conn, $sql_update)) {
            echo "<script>
                    alert('Data pelanggaran berhasil diperbarui!');
                    window.location.href = '$redirect_to';
                  </script>";
            exit;
        } else {
            $error_msg = "Gagal memperbarui data: " . mysqli_error($conn);
        }
    } else {
        $error_msg = "Mohon pilih jenis pelanggaran dengan benar!";
    }
}

// 3. AMBIL DATA UNTUK FORM EDIT
if ($source === 'pengelompokan') {
    // Jika dari Pengelompokan (ID adalah ID Siswa), ambil kasus pelanggaran terbaru milik siswa tersebut
    $sql_get = "SELECT p.id AS id_pelanggaran, p.tanggal, p.keterangan, p.id_jenis, p.id_user, 
                       s.id AS id_siswa, s.nama AS nama_siswa, s.nis, k.nama_kelas
                FROM pelanggaran p
                JOIN siswa s ON p.id_siswa = s.id
                LEFT JOIN kelas k ON s.id_kelas = k.id
                WHERE p.id_siswa = '$id'
                ORDER BY p.tanggal DESC, p.id DESC LIMIT 1";
} else {
    // Jika dari Menu Semua (ID adalah ID Kasus Pelanggaran spesifik)
    $sql_get = "SELECT p.id AS id_pelanggaran, p.tanggal, p.keterangan, p.id_jenis, p.id_user, 
                       s.id AS id_siswa, s.nama AS nama_siswa, s.nis, k.nama_kelas
                FROM pelanggaran p
                JOIN siswa s ON p.id_siswa = s.id
                LEFT JOIN kelas k ON s.id_kelas = k.id
                WHERE p.id = '$id'";
}

$q_get = mysqli_query($conn, $sql_get);
$data_edit = mysqli_fetch_assoc($q_get);

if (!$data_edit) {
    echo "<div class='container-fluid py-3'><div class='alert alert-danger'>Data catatan pelanggaran tidak ditemukan. <a href='$back_url' class='alert-link'>Kembali</a></div></div>";
    exit;
}

// 4. MASTER DATA DROPDOWN
$q_jenis   = mysqli_query($conn, "SELECT id, nama_pelanggaran, poin FROM jenis_pelanggaran ORDER BY nama_pelanggaran ASC");
$q_petugas = mysqli_query($conn, "SELECT id, nama_lengkap FROM users ORDER BY nama_lengkap ASC");
?>

<div class="container-fluid py-3">
    <div class="card shadow-sm border-0 rounded-3 mb-4 bg-white">
        <div class="card-body p-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-1 text-dark"><i class="fas fa-edit text-warning me-2"></i>Edit Catatan Pelanggaran</h5>
                <small class="text-muted">
                    Mode Pengeditan: <span class="badge bg-secondary"><?= strtoupper($source) ?></span>
                </small>
            </div>
            <a href="<?= $back_url ?>" class="btn btn-light btn-sm border px-3">
                <i class="fas fa-arrow-left me-1"></i> Batal / Kembali
            </a>
        </div>
    </div>

    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $error_msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-3 bg-white">
        <div class="card-header bg-dark text-white fw-bold py-3">
            <i class="fas fa-user-graduate me-2"></i>Informasi Pelanggaran Siswa
        </div>
        <div class="card-body p-4">
            <form action="" method="POST">
                <input type="hidden" name="id_pelanggaran" value="<?= $data_edit['id_pelanggaran'] ?>">
                <input type="hidden" name="redirect_to" value="<?= $back_url ?>">

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold">Nama Lengkap Siswa</label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($data_edit['nama_siswa']) ?>" readonly>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold">NIS / NISN</label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($data_edit['nis']) ?>" readonly>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold">Kelas</label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($data_edit['nama_kelas'] ?? '-') ?>" readonly>
                    </div>

                    <hr class="my-3 text-muted">

                    <div class="col-md-6">
                        <label class="form-label text-secondary small fw-bold">Tanggal Pelanggaran <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" class="form-control" value="<?= $data_edit['tanggal'] ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-secondary small fw-bold">Petugas Pencatat <span class="text-danger">*</span></label>
                        <select name="id_user" class="form-select" required>
                            <option value="">-- Pilih Petugas --</option>
                            <?php while ($petugas = mysqli_fetch_assoc($q_petugas)): ?>
                                <option value="<?= $petugas['id'] ?>" <?= $petugas['id'] == $data_edit['id_user'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($petugas['nama_lengkap']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label text-secondary small fw-bold">Jenis Pelanggaran & Poin <span class="text-danger">*</span></label>
                        <select name="id_jenis" class="form-select" required>
                            <option value="">-- Pilih Jenis Pelanggaran --</option>
                            <?php while ($jenis = mysqli_fetch_assoc($q_jenis)): ?>
                                <option value="<?= $jenis['id'] ?>" <?= $jenis['id'] == $data_edit['id_jenis'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($jenis['nama_pelanggaran']) ?> (+<?= $jenis['poin'] ?> Poin)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label text-secondary small fw-bold">Bentuk & Keterangan Pelanggaran</label>
                        <textarea name="keterangan" class="form-control" rows="3" placeholder="Tuliskan detail kejadian atau catatan tambahan jika ada..."><?= htmlspecialchars($data_edit['keterangan']) ?></textarea>
                    </div>

                    <div class="col-md-12 text-end mt-4">
                        <a href="<?= $back_url ?>" class="btn btn-secondary px-4 me-2">Batal</a>
                        <button type="submit" class="btn btn-warning text-dark fw-bold px-4">
                            <i class="fas fa-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>