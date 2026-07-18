<?php
/** @var mysqli $conn */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ambil data daftar pelanggaran dari database beserta relasinya
$sql = "SELECT 
            p.id, 
            p.tanggal, 
            s.nis, 
            s.nama AS nama_siswa, 
            k.nama_kelas,
            j.nama_pelanggaran, 
            j.poin
        FROM pelanggaran p
        JOIN siswa s ON p.id_siswa = s.id
        JOIN jenis_pelanggaran j ON p.id_jenis = j.id
        LEFT JOIN kelas k ON s.id_kelas = k.id
        ORDER BY p.tanggal DESC, p.id DESC";

$query = mysqli_query($conn, $sql);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-0 fw-bold text-dark">Data Pelanggaran Siswa</h3>
        <p class="text-muted small mb-0">Kelola dan pantau catatan pelanggaran serta perolehan poin kedisiplinan siswa.</p>
    </div>
    <a href="index.php?page=pelanggaran_tambah" class="btn btn-sm btn-danger shadow-sm">
        <i class="fas fa-plus me-1"></i> Catat Pelanggaran
    </a>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-3">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0" style="font-size: 0.9rem;">
                <thead class="table-dark text-nowrap">
                    <tr>
                        <th width="5%" class="text-center">No</th>
                        <th width="12%">Tanggal</th>
                        <th width="12%">NIS / NISN</th>
                        <th>Nama Siswa</th>
                        <th width="10%">Kelas</th>
                        <th>Bentuk Pelanggaran</th>
                        <th width="8%" class="text-center">Poin</th>
                        <th width="15%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if ($query && mysqli_num_rows($query) > 0) {
                        while ($r = mysqli_fetch_assoc($query)) {
                            // Format tanggal lokal (dd/mm/yyyy)
                            $tgl = date('d/m/Y', strtotime($r['tanggal']));
                    ?>
                            <tr>
                                <td class="text-center text-secondary"><?= $no++; ?></td>
                                <td><?= $tgl; ?></td>
                                <td class="fw-semibold text-secondary"><?= htmlspecialchars($r['nis']); ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($r['nama_siswa']); ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['nama_kelas'] ?? '-'); ?></span></td>
                                <td class="text-danger"><?= htmlspecialchars($r['nama_pelanggaran']); ?></td>
                                <td class="text-center">
                                    <span class="badge bg-danger rounded-pill">+<?= $r['poin']; ?></span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <!-- 1. TOMBOL DETAIL BARU -->
                                        <a href="index.php?page=pelanggaran_detail&id=<?= $r['id']; ?>" 
                                           class="btn btn-sm btn-info text-white shadow-sm d-inline-flex align-items-center justify-content-center" 
                                           style="width: 30px; height: 30px;"
                                           title="Lihat Rincian Detail">
                                            <i class="fas fa-eye" style="font-size: 0.85rem;"></i>
                                        </a>
                                        
                                        <!-- 2. TOMBOL EDIT -->
                                        <a href="index.php?page=pelanggaran_edit&id=<?= $r['id']; ?>" 
                                           class="btn btn-sm btn-warning text-white shadow-sm d-inline-flex align-items-center justify-content-center" 
                                           style="width: 30px; height: 30px;"
                                           title="Ubah Data">
                                            <i class="fas fa-edit" style="font-size: 0.85rem;"></i>
                                        </a>
                                        
                                        <!-- 3. TOMBOL HAPUS -->
                                        <a href="index.php?page=pelanggaran_hapus&id=<?= $r['id']; ?>" 
                                           class="btn btn-sm btn-danger shadow-sm d-inline-flex align-items-center justify-content-center" 
                                           style="width: 30px; height: 30px;"
                                           title="Hapus Catatan" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus data pelanggaran <?= htmlspecialchars($r['nama_siswa']); ?>?')">
                                            <i class="fas fa-trash" style="font-size: 0.85rem;"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                    <?php 
                        }
                    } else {
                        echo "<tr><td colspan='8' class='text-center text-muted py-4'><em>Belum ada catatan pelanggaran yang diinputkan.</em></td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
