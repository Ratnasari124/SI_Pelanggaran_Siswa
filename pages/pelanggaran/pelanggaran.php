<?php
/** @var mysqli $conn */

// 1. Ambil nilai filter menggunakan POST
$cari = isset($_POST['cari']) ? mysqli_real_escape_string($conn, $_POST['cari']) : '';
$filter_poin = isset($_POST['filter_poin']) ? mysqli_real_escape_string($conn, $_POST['filter_poin']) : '';

// 2. Ambil daftar poin unik dari tabel jenis_pelanggaran
$query_angka_poin = mysqli_query($conn, "SELECT DISTINCT poin FROM jenis_pelanggaran ORDER BY poin ASC");

// 3. Menyusun kondisi query pencarian
$kondisi = "";

// PERBAIKAN: Mengubah siswa.nama_siswa menjadi siswa.nama sesuai struktur DB Anda
if ($cari != '') {
    $kondisi .= " AND (siswa.nama LIKE '%$cari%' OR jenis_pelanggaran.nama_pelanggaran LIKE '%$cari%')";
}

if ($filter_poin != '') {
    $poin_pilihan = intval($filter_poin);
    $kondisi .= " AND jenis_pelanggaran.poin = '$poin_pilihan'";
}

// 4. Query utama - PERBAIKAN: mengambil siswa.nama AS nama_siswa
$sql = "SELECT 
            pelanggaran.id,
            pelanggaran.id_siswa,
            pelanggaran.id_jenis,
            pelanggaran.tanggal,
            pelanggaran.keterangan,
            pelanggaran.id_user,
            pelanggaran.created_at,
            siswa.nama AS nama_siswa,
            jenis_pelanggaran.nama_pelanggaran,
            jenis_pelanggaran.poin
        FROM pelanggaran
        INNER JOIN siswa ON pelanggaran.id_siswa = siswa.id
        INNER JOIN jenis_pelanggaran ON pelanggaran.id_jenis = jenis_pelanggaran.id
        WHERE 1=1 $kondisi 
        ORDER BY pelanggaran.tanggal DESC, pelanggaran.id DESC";

$query = mysqli_query($conn, $sql);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Data Pelanggaran Siswa</h2>
    <a href="index.php?page=pelanggaran_tambah" class="btn btn-danger"><i class="fas fa-plus"></i> Catat Pelanggaran</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body bg-light">
        <form method="POST" action="" class="row g-3">
            
            <div class="col-md-4">
                <input type="text" name="cari" class="form-control" placeholder="Cari nama siswa / pelanggaran..." value="<?= htmlspecialchars($cari); ?>">
            </div>
            
            <div class="col-md-4">
                <select name="filter_poin" class="form-select">
                    <option value="">-- Semua Poin --</option>
                    <?php 
                    if ($query_angka_poin) {
                        while ($row_poin = mysqli_fetch_assoc($query_angka_poin)) {
                            $angka = $row_poin['poin'];
                            $selected = ($filter_poin != '' && intval($filter_poin) === intval($angka)) ? 'selected' : '';
                            echo "<option value='".$angka."' ".$selected.">".$angka." Poin</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Tampilkan</button>
            </div>
            <div class="col-md-2 d-grid">
                <a href="index.php?page=pelanggaran" class="btn btn-outline-secondary text-center py-2"><i class="fas fa-sync-alt"></i> Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="table-responsive shadow-sm rounded">
    <table class="table table-bordered table-hover bg-white mb-0">
        <thead class="table-dark">
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="15%" class="text-center">Tanggal</th>
                <th>Nama Siswa</th>
                <th>Bentuk Pelanggaran</th>
                <th width="12%" class="text-center">Poin</th>
                <th>Keterangan</th>
                <th>Pencatat Pelanggaran</th>
                <th width="12%" class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            if (!$query || mysqli_num_rows($query) == 0) {
                echo "<tr><td colspan='8' class='text-center text-danger fw-bold py-3'>Tidak ada data pelanggaran ditemukan!</td></tr>";
            } else {
                while ($data = mysqli_fetch_array($query)) {
            ?>
            <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td class="text-center"><?= date('d-m-Y', strtotime($data['tanggal'])); ?></td>
                <td><?= htmlspecialchars($data['nama_siswa']); ?></td>
                <td><?= htmlspecialchars($data['nama_pelanggaran']); ?></td>
                <td class="text-center fw-bold text-danger">+ <?= intval($data['poin']); ?> Poin</td>
                <td><?= htmlspecialchars($data['keterangan'] ?: '-'); ?></td>
                <td>
                    <?php
                    $id_user = $data['id_user'];
                    $query_user = mysqli_query($conn, "SELECT nama_lengkap, role FROM users WHERE id = '$id_user'");
                    if ($user_data = mysqli_fetch_assoc($query_user)) {
                        echo htmlspecialchars($user_data['nama_lengkap']) . " [" . ucfirst($user_data['role']) . "]";
                    } else {
                        echo "<span class='text-muted'>-</span>";
                    }
                    ?>
                </td>
                <td class="text-center">
                    <a href="index.php?page=pelanggaran_edit&id=<?= $data['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                    
                    <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'guru'): ?>
                        <!-- Tambahkan class btn-hapus di sini agar skrip SweetAlert bekerja -->
                        <a href="index.php?page=pelanggaran_hapus&id=<?= $data['id']; ?>" 
                           class="btn btn-sm btn-danger btn-hapus">Hapus</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php 
                } 
            } 
            ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tombolHapus = document.querySelectorAll('.btn-hapus');
    tombolHapus.forEach(tombol => {
        tombol.addEventListener('click', function(e) {
            e.preventDefault(); 
            const urlHapus = this.getAttribute('href'); 
            Swal.fire({
                title: 'Yakin hapus data pelanggaran ini?',
                text: "Poin pelanggaran siswa yang bersangkutan akan berkurang otomatis!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = urlHapus;
                }
            });
        });
    });
});
</script>