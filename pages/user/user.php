<?php
/** @var mysqli $conn */

// 1. Menangkap nilai dari URL jika form pencarian disubmit
$cari = isset($_GET['cari']) ? $_GET['cari'] : '';

// 2. Merakit query SQL dinamis
$kondisi = "";
if ($cari != '') {
    // Mencari berdasarkan Nama Kelas ATAU Wali Kelas
    $kondisi .= " AND (nama_lengkap LIKE '%$cari%' OR username LIKE '%$cari%' OR role LIKE '%$cari%')";
}

// 3. Eksekusi query dengan kondisi yang sudah dirakit
$sql = "SELECT * FROM users WHERE 1=1 $kondisi ORDER BY nama_lengkap DESC, username ASC";
$query = mysqli_query($conn, $sql);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Data User</h2>
    <a href="index.php?page=user_tambah" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah User</a>
</div>

<!-- FORM SEARCH & FILTER -->
<div class="card shadow-sm mb-4">
    <div class="card-body bg-light">
        <form method="GET" action="index.php" class="row g-3">
            <!-- Hidden input agar URL tetap berada di index.php?page=kelas -->
            <input type="hidden" name="page" value="user">
            
            <div class="col-md-8">
                <input type="text" name="cari" class="form-control" placeholder="Cari Nama Guru / Provoost ..." value="<?= $cari; ?>">
            </div>
            
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Tampilkan</button>
            </div>
            <div class="col-md-2 d-grid">
                <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='index.php?page=user'"><i class="fas fa-sync-alt"></i> Reset</button>
            </div>
        </form>
    </div>
</div>
<!-- END FORM SEARCH & FILTER -->

<div class="table-responsive shadow-sm rounded">
    <table class="table table-bordered table-hover bg-white mb-0">
        <thead class="table-dark">
            <tr>
                <th width="5%">No</th>
                <th>Username</th>
                <th>Nama Pengguna</th>
                <th>Role</th>
                <th width="15%">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            
            if(mysqli_num_rows($query) == 0){
                // Colspan diubah menjadi 5 karena jumlah kolom sekarang ada 5
                echo "<tr><td colspan='5' class='text-center text-danger fw-bold'>Data user tidak ditemukan.</td></tr>";
            } else {
                while($data = mysqli_fetch_array($query)){
            ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= $data['username']; ?></td>
                <td><?= $data['nama_lengkap']; ?></td>
                <td><?= $data['role']; ?></td>
                <td>
                    <a href="index.php?page=user_edit&id=<?= $data['id']; ?>" class="btn btn-sm btn-warning text-dark" title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="index.php?page=user_hapus&id=<?= $data['id']; ?>" class="btn btn-sm btn-danger btn-hapus" title="Hapus">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tombolHapus = document.querySelectorAll('.btn-hapus');
    
    tombolHapus.forEach(tombol => {
        tombol.addEventListener('click', function(e) {
            e.preventDefault(); 
            const urlHapus = this.getAttribute('href'); 

            Swal.fire({
                title: 'Yakin hapus user ini?',
                text: "Data user yang ada di catatan pelanggaran juga akan ikut terhapus secara permanen!",
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