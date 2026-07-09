<?php
/** @var mysqli $conn */
require_once '../../koneksi.php';

// Pastikan ID tersedia di URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='alert alert-danger'>ID Siswa tidak ditemukan.</div>";
    exit;
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Jalankan satu query saja yang benar
$query = mysqli_query($conn, "SELECT siswa.*, kelas.nama_kelas, kelas.tahun_ajaran 
                              FROM siswa 
                              LEFT JOIN kelas ON siswa.id_kelas = kelas.id 
                              WHERE siswa.id = '$id'");

$data = mysqli_fetch_array($query);

if (!$data) {
    echo "<div class='alert alert-warning'>Data siswa tidak ditemukan di database.</div>";
    exit;
}
?>

<div class="container-fluid">
    <div class="row">
        <!-- Kolom Kiri: Informasi Identitas -->
        <div class="col-md-6">
            <h6 class="text-primary border-bottom pb-2 mb-3"><i class="fas fa-user-circle"></i> Informasi Pribadi</h6>
            <table class="table table-borderless table-sm">
                <tr><th width="40%">NIS</th><td>: <?= htmlspecialchars($data['nis']) ?></td></tr>
                <tr><th>Nama Lengkap</th><td>: <?= htmlspecialchars($data['nama']) ?></td></tr>
                <tr><th>Jenis Kelamin</th><td>: <?= ($data['jenis_kelamin'] == 'L') ? 'Laki-laki' : 'Perempuan' ?></td></tr>
                <tr><th>No. HP Orang Tua</th><td>: <?= htmlspecialchars($data['no_hp']) ?></td></tr>
            </table>
        </div>

        <!-- Kolom Kanan: Informasi Akademik & Status -->
        <div class="col-md-6">
            <h6 class="text-primary border-bottom pb-2 mb-3"><i class="fas fa-school"></i> Informasi Akademik</h6>
            <table class="table table-borderless table-sm">
                <tr><th width="40%">Kelas</th><td>: <?= htmlspecialchars($data['nama_kelas'] ?? '-') ?></td></tr>
                <tr><th>Tahun Ajaran</th><td>: <?= htmlspecialchars($data['tahun_ajaran'] ?? '-') ?></td></tr>
                <tr><th>Status</th><td>: 
                    <span class="badge <?= ($data['status'] == 'Aktif') ? 'bg-success' : 'bg-danger' ?>">
                        <?= $data['status'] ?>
                    </span>
                </td></tr>
            </table>
        </div>
    </div>
</div>