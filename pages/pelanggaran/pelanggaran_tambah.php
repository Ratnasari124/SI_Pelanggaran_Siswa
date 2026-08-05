<?php
// 1. PASTIKAN SESSION AKTIF & ERROR REPORTING
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. HUBUNGKAN KE DATABASE
$path_koneksi = dirname(__DIR__, 2) . '/koneksi.php';
if (file_exists($path_koneksi)) {
    include $path_koneksi;
} else {
    include '../../koneksi.php';
}

/** * MEMAKSA INTELLISENSE VS CODE MENGENALI VARIABEL DATABASE
 * @var mysqli $conn 
 * @var mysqli $koneksi
 * @var mysqli $db
 */
$koneksi = isset($conn) ? $conn : (isset($db) ? $db : null);

if (!$koneksi instanceof mysqli) {
    die("Error: Objek koneksi database tidak valid. Periksa file koneksi.php Anda.");
}

// ========================================================
// TANGKAP MENU ASAL (Pengelompokan / Semua)
// ========================================================
if (isset($_GET['from']) && !empty($_GET['from'])) {
    $from_view = trim($_GET['from']);
} elseif (isset($_GET['view']) && !empty($_GET['view'])) {
    $from_view = trim($_GET['view']);
} elseif (isset($_SESSION['last_view_pelanggaran'])) {
    $from_view = $_SESSION['last_view_pelanggaran'];
} else {
    $from_view = 'pengelompokan';
}
$_SESSION['last_view_pelanggaran'] = $from_view;

// ========================================================
// 3. PROSES SIMPAN DATA (POST FORM)
// ========================================================
$alert = '';
$swal_script = '';

if (isset($_POST['simpan_pelanggaran'])) {
    $id_siswa     = (int)$_POST['id_siswa'];
    $id_jenis     = (int)$_POST['id_jenis'];
    $id_user      = isset($_POST['id_user']) ? (int)$_POST['id_user'] : 1;
    $tanggal      = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    $keterangan   = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
    $redirect_view = mysqli_real_escape_string($koneksi, $_POST['redirect_view']);

    if ($id_siswa > 0 && $id_jenis > 0 && !empty($tanggal)) {
        $stmt_ins = $koneksi->prepare("INSERT INTO pelanggaran (id_siswa, id_jenis, id_user, tanggal, keterangan) VALUES (?, ?, ?, ?, ?)");
        $stmt_ins->bind_param("iiiss", $id_siswa, $id_jenis, $id_user, $tanggal, $keterangan);
        
        if ($stmt_ins->execute()) {
            $target_url = "index.php?page=pelanggaran&view=" . urlencode($redirect_view);
            // SweetAlert2 bergaya sama seperti Modal Sukses pada Manajemen User
            $swal_script = "
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sukses!',
                        text: 'Catatan pelanggaran berhasil ditambahkan!',
                        showConfirmButton: false,
                        timer: 1500,
                        timerProgressBar: true
                    }).then(function() {
                        window.location.href = '$target_url';
                    });
                } else {
                    window.location.href = '$target_url';
                }
            });
            </script>";
        } else {
            $swal_script = "
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Terjadi kesalahan saat menyimpan data ke database.'
                    });
                }
            });
            </script>";
        }
        $stmt_ins->close();
    } else {
        $swal_script = "
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Peringatan!',
                    text: 'Silakan pilih nama siswa dan jenis pelanggaran dari daftar yang muncul.'
                });
            }
        });
        </script>";
    }
}

// ========================================================
// 4. AMBIL DATA DARI DATABASE (PRE-LOAD DATA SISWA & JENIS)
// ========================================================

// A. Data Siswa
$raw_siswa = [];
$q_siswa = mysqli_query($koneksi, "SELECT s.id, s.nis, s.nama, k.nama_kelas 
                                   FROM siswa s 
                                   LEFT JOIN kelas k ON s.id_kelas = k.id 
                                   ORDER BY s.nama ASC");
if ($q_siswa) {
    while ($row = mysqli_fetch_assoc($q_siswa)) {
        $raw_siswa[] = [
            'id' => $row['id'],
            'nama' => $row['nama'],
            'nis' => $row['nis'] ?? '-',
            'kelas' => $row['nama_kelas'] ?? '-'
        ];
    }
}

// B. Data Jenis Pelanggaran
$raw_jenis = [];
$q_jenis = mysqli_query($koneksi, "SELECT id, nama_pelanggaran, poin FROM jenis_pelanggaran ORDER BY nama_pelanggaran ASC");
if ($q_jenis) {
    while ($row = mysqli_fetch_assoc($q_jenis)) {
        $raw_jenis[] = [
            'id' => $row['id'],
            'nama' => $row['nama_pelanggaran'],
            'poin' => $row['poin']
        ];
    }
}

// C. Data User / Petugas
$opt_petugas = mysqli_query($koneksi, "SELECT id, nama_lengkap FROM users ORDER BY nama_lengkap ASC");
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?= $swal_script ?>

<div class="container-fluid py-3 py-md-4">

    <div class="card shadow-sm border-0 rounded-3 bg-white">
        <div class="card-header bg-dark text-white p-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
            <h5 class="mb-0 fw-bold fs-6 fs-md-5"><i class="fas fa-plus-circle text-warning me-2"></i>Tambah Catatan Pelanggaran Siswa</h5>
            
            <a href="index.php?page=pelanggaran&view=<?= urlencode($from_view) ?>" class="btn btn-secondary btn-sm align-self-start align-self-sm-auto">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Menu <?= $from_view == 'pengelompokan' ? 'Pengelompokan' : 'Semua Pelanggaran' ?>
            </a>
        </div>
        
        <div class="card-body p-3 p-md-4">
            <form method="POST" action="" autocomplete="off" id="form_pelanggaran">
                <input type="hidden" name="redirect_view" value="<?= htmlspecialchars($from_view) ?>">

                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <div class="position-relative mb-3">
                            <label class="form-label fw-bold small text-secondary">Cari Nama / NIS Siswa <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-user"></i></span>
                                <input type="text" id="input_siswa_search" class="form-control" placeholder="Ketik 1 huruf / kata / NIS siswa..." required>
                            </div>
                            <input type="hidden" name="id_siswa" id="hidden_id_siswa" required>
                            
                            <div id="box_suggest_siswa" class="autocomplete-suggestions d-none card shadow position-absolute w-100 bg-white border"></div>
                            <small class="text-muted d-block mt-1" style="font-size: 0.78rem;">* Langsung munculkan daftar siswa begitu mengetik huruf pertama.</small>
                        </div>

                        <div class="position-relative mb-3">
                            <label class="form-label fw-bold small text-secondary">Cari Jenis Pelanggaran <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-gavel"></i></span>
                                <input type="text" id="input_jenis_search" class="form-control" placeholder="Ketik 1 huruf / kata pelanggaran..." required>
                            </div>
                            <input type="hidden" name="id_jenis" id="hidden_id_jenis" required>
                            
                            <div id="box_suggest_jenis" class="autocomplete-suggestions d-none card shadow position-absolute w-100 bg-white border"></div>
                            <small class="text-muted d-block mt-1" style="font-size: 0.78rem;">* Contoh: Ketik "t", "terlambat", "sepatu", dll.</small>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Tanggal Kejadian <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Petugas / Pencatat</label>
                            <select name="id_user" class="form-select">
                                <?php if($opt_petugas && mysqli_num_rows($opt_petugas) > 0): ?>
                                    <?php while($u = mysqli_fetch_assoc($opt_petugas)): ?>
                                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nama_lengkap']) ?></option>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <option value="1">Guru Piket / Staff</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Keterangan Tambahan (Opsional)</label>
                            <textarea name="keterangan" class="form-control" rows="3" placeholder="Contoh: Terjadi pada jam pelajaran ke-3..."></textarea>
                        </div>
                    </div>
                </div>

                <hr class="text-muted">
                
                <div class="d-flex flex-column-reverse flex-sm-row justify-content-end align-items-stretch align-items-sm-center gap-2">
                    <a href="index.php?page=pelanggaran&view=<?= urlencode($from_view) ?>" class="btn btn-secondary px-4 text-center">
                        <i class="fas fa-times me-1"></i> Batal
                    </a>
                    
                    <button type="reset" class="btn btn-light px-3 border" id="btn_reset_form">Reset Form</button>
                    
                    <button type="submit" name="simpan_pelanggaran" class="btn btn-primary px-4 shadow-sm">
                        <i class="fas fa-save me-1"></i> Simpan Catatan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Transfer data PHP ke variabel JavaScript
const LIST_SISWA = <?= json_encode($raw_siswa) ?>;
const LIST_JENIS = <?= json_encode($raw_jenis) ?>;

document.addEventListener("DOMContentLoaded", function () {

    // Helper Highlight kata yang cocok
    function highlightText(text, keyword) {
        if (!text) return '';
        if (!keyword) return text;
        const words = keyword.trim().split(/\s+/).filter(w => w.length > 0);
        if (words.length === 0) return text;
        
        const pattern = words.map(w => w.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&')).join('|');
        const reg = new RegExp(`(${pattern})`, 'gi');
        return text.replace(reg, '<mark class="p-0 bg-warning text-dark">$1</mark>');
    }

    // ==========================================
    // 1. OTOMATISASI PENCARIAN SISWA
    // ==========================================
    const inputSiswa = document.getElementById('input_siswa_search');
    const boxSiswa = document.getElementById('box_suggest_siswa');
    const hiddenSiswa = document.getElementById('hidden_id_siswa');

    function renderSiswa(query) {
        const val = query.trim().toLowerCase();
        boxSiswa.innerHTML = '';

        if (val.length < 1) {
            boxSiswa.classList.add('d-none');
            hiddenSiswa.value = '';
            return;
        }

        const filtered = LIST_SISWA.filter(item => {
            return item.nama.toLowerCase().includes(val) || 
                   item.nis.toLowerCase().includes(val) || 
                   item.kelas.toLowerCase().includes(val);
        }).slice(0, 15);

        if (filtered.length > 0) {
            boxSiswa.classList.remove('d-none');
            filtered.forEach(item => {
                let div = document.createElement('div');
                div.className = 'p-2 border-bottom suggest-item d-flex justify-content-between align-items-center';
                
                let namaFormatted = highlightText(item.nama, query);
                let nisFormatted  = highlightText(item.nis, query);

                div.innerHTML = `<div><strong>${namaFormatted}</strong> <br><small class="text-muted">NIS: ${nisFormatted}</small></div> 
                                 <span class="badge bg-secondary small">${item.kelas}</span>`;
                
                div.onclick = function() {
                    inputSiswa.value = item.nama;
                    hiddenSiswa.value = item.id;
                    boxSiswa.classList.add('d-none');
                };
                boxSiswa.appendChild(div);
            });
        } else {
            boxSiswa.classList.remove('d-none');
            boxSiswa.innerHTML = '<div class="p-2 text-muted small text-center">Siswa tidak ditemukan</div>';
        }
    }

    if (inputSiswa) {
        inputSiswa.addEventListener('input', function() {
            hiddenSiswa.value = '';
            renderSiswa(this.value);
        });
        inputSiswa.addEventListener('focus', function() {
            if (this.value.trim().length > 0 && !hiddenSiswa.value) {
                renderSiswa(this.value);
            }
        });
    }

    // ==========================================
    // 2. OTOMATISASI PENCARIAN JENIS PELANGGARAN
    // ==========================================
    const inputJenis = document.getElementById('input_jenis_search');
    const boxJenis = document.getElementById('box_suggest_jenis');
    const hiddenJenis = document.getElementById('hidden_id_jenis');

    function renderJenis(query) {
        const val = query.trim().toLowerCase();
        boxJenis.innerHTML = '';

        if (val.length < 1) {
            boxJenis.classList.add('d-none');
            hiddenJenis.value = '';
            return;
        }

        const filtered = LIST_JENIS.filter(item => {
            return item.nama.toLowerCase().includes(val);
        }).slice(0, 15);

        if (filtered.length > 0) {
            boxJenis.classList.remove('d-none');
            filtered.forEach(item => {
                let div = document.createElement('div');
                div.className = 'p-2 border-bottom suggest-item d-flex justify-content-between align-items-center';
                
                let jenisFormatted = highlightText(item.nama, query);

                div.innerHTML = `<span style="max-width:75%; word-break:break-word;">${jenisFormatted}</span> 
                                 <span class="badge bg-danger">+${item.poin} Poin</span>`;
                
                div.onclick = function() {
                    inputJenis.value = item.nama + " (+" + item.poin + " Poin)";
                    hiddenJenis.value = item.id;
                    boxJenis.classList.add('d-none');
                };
                boxJenis.appendChild(div);
            });
        } else {
            boxJenis.classList.remove('d-none');
            boxJenis.innerHTML = '<div class="p-2 text-muted small text-center">Jenis pelanggaran tidak ditemukan</div>';
        }
    }

    if (inputJenis) {
        inputJenis.addEventListener('input', function() {
            hiddenJenis.value = '';
            renderJenis(this.value);
        });
        inputJenis.addEventListener('focus', function() {
            if (this.value.trim().length > 0 && !hiddenJenis.value) {
                renderJenis(this.value);
            }
        });
    }

    // Sembunyikan rekomendasi jika mengklik area luar
    document.addEventListener('click', function(e) {
        if(e.target !== inputSiswa && boxSiswa) boxSiswa.classList.add('d-none');
        if(e.target !== inputJenis && boxJenis) boxJenis.classList.add('d-none');
    });

    // Reset Form Listener
    const btnReset = document.getElementById('btn_reset_form');
    if(btnReset){
        btnReset.addEventListener('click', function(){
            hiddenSiswa.value = '';
            hiddenJenis.value = '';
            if(boxSiswa) boxSiswa.classList.add('d-none');
            if(boxJenis) boxJenis.classList.add('d-none');
        });
    }
});
</script>

<style>
    .autocomplete-suggestions { 
        max-height: 250px; 
        overflow-y: auto; 
        z-index: 9999; 
        border-radius: 6px; 
        margin-top: 2px; 
    }
    .suggest-item { 
        cursor: pointer; 
        transition: background 0.2s, color 0.2s; 
        font-size: 0.88rem; 
        color: #333333; 
        padding: 10px 15px !important;
    }
    .suggest-item:hover { 
        background-color: #0d6efd; 
        color: #ffffff; 
    }
    .suggest-item:hover small,
    .suggest-item:hover .text-muted {
        color: #e0e0e0 !important;
    }
</style>