<?php
/** @var mysqli $conn */

// 1. Menangkap nilai pencarian dan filter poin dengan aman
$cari = isset($_GET['cari']) ? mysqli_real_escape_string($conn, $_GET['cari']) : '';
$filter_poin = isset($_GET['filter_poin']) ? $_GET['filter_poin'] : '';

// 2. Menyusun kondisi query secara dinamis
$kondisi = "";
if ($cari != '') {
    $kondisi .= " AND nama_pelanggaran LIKE '%$cari%'";
}

if ($filter_poin != '') {
    if ($filter_poin == 'ringan') {
        $kondisi .= " AND poin <= 15";
    } elseif ($filter_poin == 'sedang') {
        $kondisi .= " AND poin > 15 AND poin <= 50";
    } elseif ($filter_poin == 'berat') {
        $kondisi .= " AND poin > 50";
    }
}

// 3. Eksekusi query database jenis_pelanggaran
$sql = "SELECT * FROM jenis_pelanggaran WHERE 1=1 $kondisi ORDER BY id DESC";
$query = mysqli_query($conn, $sql);
?>

<!-- KEPALA HALAMAN -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Data Jenis Pelanggaran</h2>
    <a href="index.php?page=jenis_tambah" class="btn btn-primary">
        <i class="fas fa-plus"></i> Tambah Jenis Pelanggaran
    </a>
</div>

<!-- FORM PENCARIAN & FILTER -->
<div class="card shadow-sm mb-4">
    <div class="card-body bg-light">
        <form method="GET" action="index.php" class="row g-2">
            <!-- Hidden input penting agar halaman tidak dialihkan ke dashboard saat disubmit -->
            <input type="hidden" name="page" value="jenis">
            
            <div class="col-md-5">
                <input type="text" name="cari" class="form-control" placeholder="Cari nama pelanggaran..." value="<?= htmlspecialchars($cari); ?>">
            </div>
            
            <div class="col-md-3">
                <select name="filter_poin" class="form-select">
                    <option value="">Semua Tingkatan Poin</option>
                    <option value="ringan" <?= ($filter_poin == 'ringan') ? 'selected' : ''; ?>>Ringan (≤ 15 Poin)</option>
                    <option value="sedang" <?= ($filter_poin == 'sedang') ? 'selected' : ''; ?>>Sedang (16 - 50 Poin)</option>
                    <option value="berat" <?= ($filter_poin == 'berat') ? 'selected' : ''; ?>>Berat (> 50 Poin)</option>
                </select>
            </div>
            
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-search"></i> Tampilkan
                </button>
            </div>
            <div class="col-md-2 d-grid">
                <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='index.php?page=jenis'">
                    <i class="fas fa-sync-alt"></i> Reset
                </button>
            </div>
        </form>
    </div>
</div>

<!-- TABEL DATA -->
<div class="table-responsive shadow-sm rounded">
    <table class="table table-bordered table-hover bg-white mb-0">
        <thead class="table-dark">
            <tr>
                <th width="5%" class="text-center">No</th>
                <th>Nama Pelanggaran</th>
                <th width="20%" class="text-center">Bobot Poin</th>
                <th width="15%" class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            if (mysqli_num_rows($query) == 0) {
                echo "<tr><td colspan='4' class='text-center text-danger fw-bold py-3'>Data tidak ditemukan!</td></tr>";
            } else {
                while ($data = mysqli_fetch_array($query)) {
                    // Logika pewarnaan badge poin otomatis
                    if ($data['poin'] <= 15) {
                        $badge_class = 'bg-success';
                    } elseif ($data['poin'] <= 50) {
                        $badge_class = 'bg-warning text-dark';
                    } else {
                        $badge_class = 'bg-danger';
                    }
            ?>
            <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= htmlspecialchars($data['nama_pelanggaran']); ?></td>
                <td class="text-center">
                    <span class="badge <?= $badge_class; ?> px-2 py-2 fs-6" style="min-width: 80px;">
                        <?= $data['poin']; ?> Poin
                    </span>
                </td>
                <td class="text-center">
                    <a href="index.php?page=jenis_edit&id=<?= $data['id']; ?>" class="btn btn-sm btn-warning text-dark" title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-info text-white btn-detail" 
                            data-bs-toggle="modal" 
                            data-bs-target="#modalDetail" 
                            data-id="<?= $data['id']; ?>" 
                            title="Detail">
                        <i class="fas fa-eye"></i>
                    </button>
                    <a href="index.php?page=jenis_hapus&id=<?= $data['id']; ?>" class="btn btn-sm btn-danger btn-hapus" title="Hapus">
                        <i class="fas fa-trash"></i>
                    </a>
                </td>
            </tr>
            <?php 
                } 
            } 
            ?>
        </tbody>
    </table>
</div>

<!-- MODAL DETAIL (POPUP VIEW) -->
<div class="modal fade" id="modalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-info-circle text-info"></i> Detail Jenis Pelanggaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="detailBody">
                <div class="text-center py-3">
                    <div class="spinner-border text-info" role="status"></div>
                    <div class="mt-2">Memuat data...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JAVASCRIPT AJAX & SWEETALERT2 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Handler AJAX untuk Modal Detail
    const tombolDetail = document.querySelectorAll('.btn-detail');
    tombolDetail.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const detailBody = document.getElementById('detailBody');
            
            // Tampilkan loading spinner setiap kali modal dibuka
            detailBody.innerHTML = `
                <div class="text-center py-3">
                    <div class="spinner-border text-info" role="status"></div>
                    <div class="mt-2">Memuat data...</div>
                </div>`;
            
            // Jalankan request ke file detail
            fetch('pages/jenis/jenis_detail.php?id=' + id)
                .then(response => response.text())
                .then(html => {
                    detailBody.innerHTML = html;
                })
                .catch(error => {
                    detailBody.innerHTML = '<div class="alert alert-danger text-center">Gagal memuat detail data.</div>';
                });
        });
    });

    // 2. Handler SweetAlert2 untuk Konfirmasi Hapus Data
    const tombolHapus = document.querySelectorAll('.btn-hapus');
    tombolHapus.forEach(tombol => {
        tombol.addEventListener('click', function(e) {
            e.preventDefault(); 
            const urlHapus = this.getAttribute('href'); 
            
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data jenis pelanggaran ini akan dihapus permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = urlHapus;
                }
            });
        });
    });
});
</script>