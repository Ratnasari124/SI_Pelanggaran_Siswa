<?php
/** @var mysqli $conn */

// 1. Pastikan Session Aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Menangkap & Mengamankan Nilai Pencarian dari URL
$cari         = isset($_GET['cari']) ? mysqli_real_escape_string($conn, trim($_GET['cari'])) : '';
$filter_bobot = isset($_GET['filter_bobot']) ? mysqli_real_escape_string($conn, trim($_GET['filter_bobot'])) : '';

// 3. Ambil List Sanksi untuk Dropdown Dinamis
$query_dropdown = mysqli_query($conn, "SELECT id, nama_sanksi, min_poin, max_poin FROM sanksi ORDER BY min_poin ASC");

// 4. Menyusun Kondisi Query Utama
$kondisi = "";

if ($cari != '') {
    $kondisi .= " AND nama_sanksi LIKE '%$cari%'";
}

if ($filter_bobot != '') {
    $kondisi .= " AND id = '$filter_bobot'";
}

// 5. Eksekusi Query Data Tabel Sanksi
$sql   = "SELECT id, min_poin, max_poin, nama_sanksi FROM sanksi WHERE 1=1 $kondisi ORDER BY min_poin ASC";
$query = mysqli_query($conn, $sql);

// 6. Penanganan Notifikasi SweetAlert2 dari Session (Post-Redirect)
$swal_script = '';
if (isset($_SESSION['status_pesan'])) {
    $tipe  = $_SESSION['status_pesan']['tipe'] ?? 'success';
    $judul = $_SESSION['status_pesan']['judul'] ?? 'Berhasil!';
    $teks  = $_SESSION['status_pesan']['teks'] ?? 'Data sanksi berhasil diproses.';
    
    $swal_script = "
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: '{$tipe}',
                title: '{$judul}',
                text: '{$teks}',
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true
            });
        }
    });
    </script>";
    unset($_SESSION['status_pesan']);
}
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?= $swal_script ?>

<style>
    /* Pembungkus Tabel Responsif dengan Touch Scrolling */
    .table-responsive-custom {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    /* Penyesuaian Perangkat Android / Layar Kecil (Mobile) */
    @media (max-width: 575.98px) {
        .header-title-responsive {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 10px !important;
        }
        .header-title-responsive a {
            width: 100% !important;
            text-align: center;
        }
        .filter-btn-group .btn {
            width: 100% !important;
        }
        .table-custom th, .table-custom td {
            font-size: 0.82rem !important;
            padding: 8px 10px !important;
            white-space: nowrap;
        }
        .badge-responsive {
            font-size: 0.75rem !important;
            white-space: normal !important;
            word-break: break-word;
        }
    }

    /* Penyesuaian Tablet / iPad (768px - 991px) */
    @media (min-width: 576px) and (max-width: 991.98px) {
        .table-custom th, .table-custom td {
            font-size: 0.88rem !important;
        }
    }
</style>

<div class="container-fluid py-2 py-md-3">

    <div class="d-flex justify-content-between align-items-center mb-3 header-title-responsive">
        <h2 class="h4 fw-bold mb-0 text-dark">
            <i class="fas fa-gavel text-warning me-2"></i>Data Sanksi Pelanggaran
        </h2>
        <a href="index.php?page=sanksi_tambah" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus me-1"></i> Tambah Sanksi
        </a>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body bg-light rounded-3 p-3">
            <form method="GET" action="index.php" class="row g-2 align-items-center">
                <input type="hidden" name="page" value="sanksi">
                
                <div class="col-12 col-md-5 col-lg-5">
                    <input type="text" name="cari" class="form-control" placeholder="Cari nama sanksi..." value="<?= htmlspecialchars($cari); ?>">
                </div>
                
                <div class="col-12 col-md-4 col-lg-4">
                    <select name="filter_bobot" class="form-select">
                        <option value="">-- Semua Tingkatan Sanksi --</option>
                        <?php 
                        if ($query_dropdown && mysqli_num_rows($query_dropdown) > 0) {
                            while ($row_drop = mysqli_fetch_assoc($query_dropdown)) { 
                                $selected = ($filter_bobot == $row_drop['id']) ? 'selected' : '';
                                echo "<option value='".$row_drop['id']."' ".$selected.">";
                                echo htmlspecialchars($row_drop['nama_sanksi'])." (".$row_drop['min_poin']." - ".$row_drop['max_poin']." Poin)";
                                echo "</option>";
                            } 
                        }
                        ?>
                    </select>
                </div>
                
                <div class="col-12 col-md-3 col-lg-3 filter-btn-group">
                    <div class="d-flex gap-2 w-100">
                        <button type="submit" class="btn btn-secondary flex-fill">
                            <i class="fas fa-search me-1"></i> Cari
                        </button>
                        <button type="button" class="btn btn-outline-secondary flex-fill" onclick="window.location.href='index.php?page=sanksi'">
                            <i class="fas fa-sync-alt me-1"></i> Reset
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body p-0">
            <div class="table-responsive-custom">
                <table class="table table-bordered table-hover align-middle table-custom bg-white mb-0">
                    <thead class="table-dark text-center align-middle text-nowrap">
                        <tr>
                            <th width="5%">No</th>
                            <th width="12%">Min Poin</th>
                            <th width="12%">Max Poin</th>
                            <th>Nama Sanksi</th>
                            <th width="25%">Kategori Badge</th>
                            <th width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (!$query || mysqli_num_rows($query) == 0) {
                            echo "<tr><td colspan='6' class='text-center text-danger fw-bold py-4'>Data sanksi tidak ditemukan!</td></tr>";
                        } else {
                            $no = 1;
                            while ($data = mysqli_fetch_assoc($query)) {
                                $min = intval($data['min_poin']);
                                $max = intval($data['max_poin']);
                                $range_poin = $min . " - " . $max . " Poin";
                        ?>
                        <tr>
                            <td class="text-center fw-bold"><?= $no++; ?></td>
                            
                            <td class="text-center text-nowrap"><?= $min; ?> Poin</td>
                            <td class="text-center text-nowrap"><?= $max; ?> Poin</td>
                            
                            <td class="fw-bold text-dark"><?= htmlspecialchars($data['nama_sanksi'] ?? ''); ?></td>
                            
                            <td class="text-center text-nowrap">
                                <?php 
                                    if ($min >= 150 || strpos(strtolower($data['nama_sanksi']), 'sangat berat') !== false) {
                                        echo '<span class="badge bg-danger px-3 py-2 w-100 d-inline-block badge-responsive">' . $range_poin . ' (Sangat Berat)</span>';
                                    } elseif ($min >= 75 || strpos(strtolower($data['nama_sanksi']), 'berat') !== false) {
                                        echo '<span class="badge bg-warning text-dark px-3 py-2 w-100 d-inline-block badge-responsive">' . $range_poin . ' (Berat)</span>';
                                    } elseif ($min >= 40 || strpos(strtolower($data['nama_sanksi']), 'sedang') !== false) {
                                        echo '<span class="badge bg-info text-dark px-3 py-2 w-100 d-inline-block badge-responsive">' . $range_poin . ' (Sedang)</span>';
                                    } else {
                                        echo '<span class="badge bg-secondary px-3 py-2 w-100 d-inline-block badge-responsive">' . $range_poin . ' (Ringan)</span>';
                                    }
                                ?>
                            </td>
                            
                            <td class="text-center text-nowrap">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="index.php?page=sanksi_edit&id=<?= $data['id']; ?>" class="btn btn-warning text-dark" title="Edit Data">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </a>
                                    <a href="index.php?page=sanksi_hapus&id=<?= $data['id']; ?>" class="btn btn-danger btn-hapus" title="Hapus Data">
                                        <i class="fas fa-trash me-1"></i> Hapus
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            } 
                        } 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tombolHapus = document.querySelectorAll('.btn-hapus');
    tombolHapus.forEach(tombol => {
        tombol.addEventListener('click', function(e) {
            e.preventDefault(); 
            const urlHapus = this.getAttribute('href'); 
            Swal.fire({
                title: 'Yakin hapus data sanksi ini?',
                text: "Tindakan ini tidak dapat dibatalkan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
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