<?php
// 1. PASTIKAN SESSION AKTIF & ERROR REPORTING
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. HUBUNGKAN KE DATABASE (Menggunakan Jalur Absolut Aman)
$path_koneksi = dirname(__DIR__, 2) . '/koneksi.php';
if (file_exists($path_koneksi)) {
    include $path_koneksi;
} else {
    include '../../koneksi.php';
}

/** 
 * MEMAKSA INTELLISENSE VS CODE MENGENALI VARIABEL DATABASE
 * @var mysqli $conn 
 * @var mysqli $koneksi
 * @var mysqli $db
 */

// Menghubungkan ke variabel koneksi yang aktif di koneksi.php Anda
$koneksi = isset($conn) ? $conn : (isset($db) ? $db : null);

// Validasi mutlak untuk memastikan objek koneksi siap pakai dan dikenali VS Code
if (!$koneksi instanceof mysqli) {
    die("Error: Objek koneksi database tidak valid atau tidak ditemukan. Periksa file koneksi.php Anda.");
}

// ========================================================
// 3. PROSES AJAX LIVE SEARCH AUTOCOMPLETE (DITEMPATKAN DI PALING ATAS)
// ========================================================
if (isset($_GET['action']) && $_GET['action'] == 'search_siswa') {
    // Bersihkan buffer output untuk memastikan tidak ada HTML liar yang bocor ke JSON
    if (ob_get_length()) ob_clean(); 
    
    $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
    $result = [];

    if ($keyword !== '') {
        $query = "SELECT s.id, s.nis, s.nama, k.nama_kelas 
                  FROM siswa s 
                  LEFT JOIN kelas k ON s.id_kelas = k.id 
                  WHERE s.nama LIKE ? OR s.nis LIKE ? 
                  LIMIT 15";
                  
        $stmt = $koneksi->prepare($query);
        $search_param = "%" . $keyword . "%"; // Mencari kecocokan huruf di depan, tengah, atau belakang nama
        $stmt->bind_param("ss", $search_param, $search_param);
        $stmt->execute();
        $q_result = $stmt->get_result();
        
        while($r = $q_result->fetch_assoc()) {
            $result[] = $r;
        }
        $stmt->close();
    }
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result);
    exit; // Menghentikan rendering HTML agar response murni JSON
}

if (isset($_GET['action']) && $_GET['action'] == 'search_jenis') {
    // Bersihkan buffer output
    if (ob_get_length()) ob_clean();

    $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
    $result = [];

    if ($keyword !== '') {
        // Menggunakan pencarian kata yang fleksibel pada nama pelanggaran
        $query = "SELECT id, nama_pelanggaran, poin FROM jenis_pelanggaran WHERE nama_pelanggaran LIKE ? LIMIT 15";
        
        $stmt = $koneksi->prepare($query);
        $search_param = "%" . $keyword . "%"; // Mencari kata di posisi manapun yang berkaitan
        $stmt->bind_param("s", $search_param);
        $stmt->execute();
        $q_result = $stmt->get_result();
        
        while($r = $q_result->fetch_assoc()) {
            $result[] = $r;
        }
        $stmt->close();
    }
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result);
    exit; // Menghentikan rendering HTML agar response murni JSON
}

// Tangkap mode asal halaman untuk redirect nantinya
$from_view = isset($_GET['from']) ? htmlspecialchars($_GET['from']) : 'semua';

// ========================================================
// 4. PROSES SIMPAN DATA (POST FORM)
// ========================================================
$alert = '';
if (isset($_POST['simpan_pelanggaran'])) {
    $id_siswa      = (int)$_POST['id_siswa'];
    $id_jenis      = (int)$_POST['id_jenis'];
    $id_user       = isset($_POST['id_user']) ? (int)$_POST['id_user'] : 1;
    $tanggal       = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    $keterangan    = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
    $redirect_view = mysqli_real_escape_string($koneksi, $_POST['redirect_view']);

    if($id_siswa > 0 && $id_jenis > 0 && !empty($tanggal)) {
        $stmt_ins = $koneksi->prepare("INSERT INTO pelanggaran (id_siswa, id_jenis, id_user, tanggal, keterangan) VALUES (?, ?, ?, ?, ?)");
        $stmt_ins->bind_param("iiiss", $id_siswa, $id_jenis, $id_user, $tanggal, $keterangan);
        
        if ($stmt_ins->execute()) {
            echo "<script>
                    alert('Berhasil! Catatan pelanggaran baru berhasil disimpan.');
                    window.location.href = 'index.php?page=pelanggaran&view=" . $redirect_view . "';
                  </script>";
            exit;
        } else {
            $alert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Gagal!</strong> Terjadi kesalahan sistem saat menyimpan data ke database.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                      </div>';
        }
        $stmt_ins->close();
    } else {
        $alert = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <strong>Peringatan!</strong> Mohon pilih Siswa dan Jenis Pelanggaran terlebih dahulu.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
    }
}

// Ambil data petugas pencatat
$opt_petugas = mysqli_query($koneksi, "SELECT id, nama_lengkap FROM users ORDER BY nama_lengkap ASC");
?>

<!-- ========================================================
// 5. TAMPILAN ELEMEN FORM INPUT HTML
// ======================================================== -->
<div class="container-fluid py-4">
    <?= $alert ?>

    <div class="card shadow-sm border-0 rounded-3 bg-white">
        <div class="card-header bg-dark text-white p-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="fas fa-plus-circle text-warning me-2"></i>Tambah Catatan Pelanggaran Siswa</h5>
            <a href="index.php?page=pelanggaran&view=<?= $from_view ?>" class="btn btn-secondary btn-sm shadow-2xs">
                <i class="fas fa-arrow-left me-1"></i> Batal
            </a>
        </div>
        
        <div class="card-body p-4">
            <form method="POST" action="" autocomplete="off">
                <input type="hidden" name="redirect_view" value="<?= $from_view ?>">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="position-relative mb-3">
                            <label class="form-label fw-bold small text-secondary">Cari Nama / NIS Siswa</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-user"></i></span>
                                <input type="text" id="input_siswa_search" class="form-control" placeholder="Ketik nama depan/kata kunci siswa..." required>
                            </div>
                            <input type="hidden" name="id_siswa" id="hidden_id_siswa" required>
                            <div id="box_suggest_siswa" class="autocomplete-suggestions d-none card shadow-sm position-absolute w-100 bg-white border z-index-3"></div>
                        </div>

                        <div class="position-relative mb-3">
                            <label class="form-label fw-bold small text-secondary">Cari Jenis Pelanggaran</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-gavel"></i></span>
                                <input type="text" id="input_jenis_search" class="form-control" placeholder="Ketik nama pelanggaran yang bersangkutan..." required>
                            </div>
                            <input type="hidden" name="id_jenis" id="hidden_id_jenis" required>
                            <div id="box_suggest_jenis" class="autocomplete-suggestions d-none card shadow-sm position-absolute w-100 bg-white border z-index-3"></div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Tanggal Kejadian</label>
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
                
                <div class="text-end">
                    <button type="reset" class="btn btn-light px-4 me-2 border" id="btn_reset_form">Reset Form</button>
                    <button type="submit" name="simpan_pelanggaran" class="btn btn-primary px-4 shadow-sm">
                        <i class="fas fa-save me-1"></i> Simpan Catatan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================
// 6. SCRIPT JAVASCRIPT AJAX LIVE SEARCH (PERBAIKAN TINGKAT STABILITAS)
// ======================================================== -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    // Ambil parameter halaman aktif saat ini secara dinamis dari URL browser
    const currentUrlParams = new URLSearchParams(window.location.search);
    const currentPage = currentUrlParams.get('page') || 'pelanggaran_tambah';

    // --- FITUR PENCARIAN SISWA ---
    const inputSiswa = document.getElementById('input_siswa_search');
    const boxSiswa = document.getElementById('box_suggest_siswa');
    const hiddenSiswa = document.getElementById('hidden_id_siswa');
    
    if (inputSiswa) {
        inputSiswa.addEventListener('input', function() {
            let val = this.value.trim();
            if(val.length < 1) { 
                boxSiswa.classList.add('d-none'); 
                hiddenSiswa.value = ''; 
                return; 
            }

            // Mengirim parameter URL action yang akan dipotong oleh perintah exit PHP di bagian atas
            fetch(`index.php?page=${currentPage}&action=search_siswa&keyword=${encodeURIComponent(val)}`)
                .then(res => {
                    if (!res.ok) throw new Error('Response jaringan bermasalah');
                    return res.json();
                })
                .then(data => {
                    boxSiswa.innerHTML = '';
                    if(data && data.length > 0) {
                        boxSiswa.classList.remove('d-none');
                        data.forEach(item => {
                            let div = document.createElement('div');
                            div.className = 'p-2 border-bottom suggest-item style-suggest d-flex justify-content-between align-items-center';
                            div.innerHTML = `<div><strong>${item.nama}</strong> <br><small class="text-muted">NIS: ${item.nis}</small></div> 
                                             <span class="badge bg-secondary small">${item.nama_kelas ?? '-'}</span>`;
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
                })
                .catch(err => console.error("Error fetch siswa:", err));
        });
    }

    // --- FITUR PENCARIAN JENIS PELANGGARAN ---
    const inputJenis = document.getElementById('input_jenis_search');
    const boxJenis = document.getElementById('box_suggest_jenis');
    const hiddenJenis = document.getElementById('hidden_id_jenis');

    if (inputJenis) {
        inputJenis.addEventListener('input', function() {
            let val = this.value.trim();
            if(val.length < 1) { 
                boxJenis.classList.add('d-none'); 
                hiddenJenis.value = ''; 
                return; 
            }

            fetch(`index.php?page=${currentPage}&action=search_jenis&keyword=${encodeURIComponent(val)}`)
                .then(res => {
                    if (!res.ok) throw new Error('Response jaringan bermasalah');
                    return res.json();
                })
                .then(data => {
                    boxJenis.innerHTML = '';
                    if(data && data.length > 0) {
                        boxJenis.classList.remove('d-none');
                        data.forEach(item => {
                            let div = document.createElement('div');
                            div.className = 'p-2 border-bottom suggest-item style-suggest d-flex justify-content-between align-items-center';
                            div.innerHTML = `<span style="max-width:75%; display:inline-block; word-break:break-word;">${item.nama_pelanggaran}</span> 
                                             <span class="badge bg-danger">+${item.poin} Poin</span>`;
                            div.onclick = function() {
                                inputJenis.value = item.nama_pelanggaran;
                                hiddenJenis.value = item.id;
                                boxJenis.classList.add('d-none');
                            };
                            boxJenis.appendChild(div);
                        });
                    } else {
                        boxJenis.classList.remove('d-none');
                        boxJenis.innerHTML = '<div class="p-2 text-muted small text-center">Jenis pelanggaran tidak ditemukan</div>';
                    }
                })
                .catch(err => console.error("Error fetch jenis:", err));
        });
    }

    // Menutup kotak saran otomatis jika pengguna mengklik di luar area input pencarian
    document.addEventListener('click', function(e) {
        if(e.target !== inputSiswa && boxSiswa) boxSiswa.classList.add('d-none');
        if(e.target !== inputJenis && boxJenis) boxJenis.classList.add('d-none');
    });

    // Reset hidden input jika tombol reset form ditekan
    const btnReset = document.getElementById('btn_reset_form');
    if(btnReset){
        btnReset.addEventListener('click', function(){
            hiddenSiswa.value = '';
            hiddenJenis.value = '';
            boxSiswa.classList.add('d-none');
            boxJenis.classList.add('d-none');
        });
    }
});
</script>

<style>
    .autocomplete-suggestions { max-height: 230px; overflow-y: auto; z-index: 9999; border-radius: 6px; margin-top: 5px; }
    .suggest-item { cursor: pointer; transition: background 0.2s; font-size: 0.88rem; color: #333; }
    .suggest-item:hover { background-color: #f8f9fa; color: #000; }
    .style-suggest { padding: 10px 15px !important; }
    .shadow-2xs { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
</style>