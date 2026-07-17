<?php
/** @var mysqli $conn */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pesan = "";

// 1. PROSES KETIKA TOMBOL SIMPAN DIKLIK
if (isset($_POST['simpan'])) {
    $id_siswa   = intval($_POST['id_siswa']);
    $id_jenis   = intval($_POST['id_jenis']);
    $tanggal    = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $id_user_pilihan = isset($_POST['id_user_petugas']) ? intval($_POST['id_user_petugas']) : 0;
    
    // Validasi input wajib
    if (empty($id_siswa) || empty($id_jenis) || empty($tanggal) || empty($id_user_pilihan)) {
        $pesan = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    <strong>Gagal!</strong> Anda harus mengetik manual lalu memilih Siswa dan Pilihan Poin dari daftar di bawah kolom input agar data tersimpan valid.
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                  </div>";
    } else {
        $sql = "INSERT INTO pelanggaran (id_siswa, id_jenis, tanggal, keterangan, id_user) 
                VALUES ('$id_siswa', '$id_jenis', '$tanggal', '$keterangan', '$id_user_pilihan')";
        
        $query = mysqli_query($conn, $sql);

        if ($query) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Pelanggaran siswa berhasil dicatat.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'index.php?page=pelanggaran';
                    });
                });
            </script>";
        } else {
            $pesan = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                        <strong>Gagal Menyimpan:</strong> " . mysqli_error($conn) . "
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                      </div>";
        }
    }
}

// 2. AMBIL DATA DARI DATABASE (Mendukung pencarian manual NISN/NIS & Nama)
$array_siswa = [];
$query_siswa = mysqli_query($conn, "SELECT id, nis, nama FROM siswa ORDER BY nama ASC");
if ($query_siswa) {
    while ($r = mysqli_fetch_assoc($query_siswa)) {
        $array_siswa[] = [
            'id' => $r['id'], 
            'label' => $r['nis'] . " - " . $r['nama'], 
            'searchable' => strtolower($r['nis'] . " " . $r['nama'])
        ];
    }
}

$array_jenis = [];
$query_jenis = mysqli_query($conn, "SELECT id, nama_pelanggaran, poin FROM jenis_pelanggaran ORDER BY nama_pelanggaran ASC");
if ($query_jenis) {
    while ($r = mysqli_fetch_assoc($query_jenis)) {
        $array_jenis[] = [
            'id' => $r['id'], 
            'label' => $r['nama_pelanggaran'] . " (+" . $r['poin'] . " Poin)", 
            'searchable' => strtolower($r['nama_pelanggaran'])
        ];
    }
}

$query_petugas = mysqli_query($conn, "SELECT id, nama_lengkap, role FROM users WHERE role IN ('guru', 'provoost') ORDER BY role ASC, nama_lengkap ASC");
?>

<style>
    .autocomplete-wrapper {
        position: relative;
    }
    /* Kotak Saran Elegan & Rapi Tepat di Bawah Kolom */
    .suggestion-box {
        position: absolute;
        top: 100%;
        left: 0;
        z-index: 1050;
        width: 100%;
        max-height: 220px;
        overflow-y: auto;
        background-color: #fff;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        display: none;
    }
    .suggestion-item {
        padding: 12px 15px;
        cursor: pointer;
        border-bottom: 1px solid #f8f9fa;
        font-size: 0.95rem;
        transition: all 0.2s ease;
    }
    .suggestion-item:last-child {
        border-bottom: none;
    }
    .suggestion-item:hover {
        background-color: #fff5f5;
        color: #dc3545;
        padding-left: 20px; /* Efek transisi geser sedikit saat disorot */
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-0 fw-bold text-dark">Form Catat Pelanggaran Baru</h3>
        <p class="text-muted small mb-0">Isi kolom di bawah secara manual, ketik nama/NISN atau jenis pelanggaran lalu pilih dari daftar yang muncul.</p>
    </div>
    <a href="index.php?page=pelanggaran" class="btn btn-sm btn-secondary shadow-sm"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
</div>

<?= $pesan; ?>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <form method="POST" action="" autocomplete="off">
            
            <div class="row">
                <!-- 1. KOLOM NAMA / NISN SISWA (KETIK MANUAL) -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold text-secondary">Nama atau NIS/NISN Siswa</label>
                    <div class="autocomplete-wrapper">
                        <input type="text" id="input_siswa" class="form-control form-control-sm" placeholder="Ketik Nama / NISN Siswa dan klik" required>
                        <input type="hidden" name="id_siswa" id="hidden_id_siswa">
                        <!-- Box hasil pencarian rapi muncul tepat di bawah input ini -->
                        <div id="box_siswa" class="suggestion-box"></div>
                    </div>
                </div>
                
                <!-- 2. KOLOM JENIS PELANGGARAN (KETIK MANUAL) -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold text-secondary">Bentuk / Jenis Pelanggaran</label>
                    <div class="autocomplete-wrapper">
                        <input type="text" id="input_jenis" class="form-control form-control-sm" placeholder="Ketik Jenis Pelanggaran dan klik" required>
                        <input type="hidden" name="id_jenis" id="hidden_id_jenis">
                        <!-- Box hasil pencarian rapi muncul tepat di bawah input ini -->
                        <div id="box_jenis" class="suggestion-box"></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- 3. PETUGAS PENCATAT -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold text-secondary">Petugas Pencatat</label>
                    <select name="id_user_petugas" class="form-select form-select-sm" required>
                        <option value="">-- Pilih Guru / Provoost --</option>
                        <?php 
                        if ($query_petugas && mysqli_num_rows($query_petugas) > 0) {
                            while ($row_petugas = mysqli_fetch_assoc($query_petugas)) {
                                $role_display = ucfirst($row_petugas['role']); 
                                echo "<option value='".$row_petugas['id']."'>".htmlspecialchars($row_petugas['nama_lengkap'])." [".$role_display."]</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                
                <!-- 4. TANGGAL KEJADIAN -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold text-secondary">Tanggal Kejadian</label>
                    <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d'); ?>" required>
                </div>
            </div>
            
            <!-- 5. KETERANGAN KRONOLOGI -->
            <div class="mb-4">
                <label class="form-label fw-semibold text-secondary">Keterangan / Kronologi Singkat</label>
                <textarea name="keterangan" class="form-control" rows="4" placeholder="Tulis catatan tambahan/kronologi kejadian jika diperlukan..."></textarea>
            </div>
            
            <div class="d-flex justify-content-end gap-2 border-top pt-3">
                <button type="reset" class="btn btn-lg btn-outline-secondary px-4">Reset</button>
                <button type="submit" name="simpan" class="btn btn-lg btn-danger px-4 shadow-sm"><i class="fas fa-save me-2"></i>Simpan Catatan</button>
            </div>
        </form>
    </div>
</div>

<!-- JAVASCRIPT LIVE FILTER KETIK MANUAL -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inject data JSON dari PHP ke JS
    const dataSiswa = <?php echo json_encode($array_siswa); ?>;
    const dataJenis = <?php echo json_encode($array_jenis); ?>;

    // Fungsi Pengendali Live Suggestion Box
    function setupLiveSearch(inputId, boxId, hiddenId, sourceData) {
        const inputEl = document.getElementById(inputId);
        const boxEl = document.getElementById(boxId);
        const hiddenEl = document.getElementById(hiddenId);

        inputEl.addEventListener('input', function() {
            const keyword = this.value.toLowerCase().trim();
            boxEl.innerHTML = ''; // bersihkan data lama
            
            if (keyword === '') {
                boxEl.style.display = 'none';
                hiddenEl.value = '';
                return;
            }

            // Filter data secara cerdas berdasarkan kata kunci yang diketik manual
            const filtered = sourceData.filter(item => item.searchable.includes(keyword));

            if (filtered.length > 0) {
                filtered.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'suggestion-item';
                    div.textContent = item.label;
                    
                    // Aksi saat item saran dipilih/diklik
                    div.addEventListener('click', function() {
                        inputEl.value = item.label;
                        hiddenEl.value = item.id;
                        boxEl.style.display = 'none';
                    });
                    
                    boxEl.appendChild(div);
                });
                boxEl.style.display = 'block';
            } else {
                // Notifikasi rapi jika keyword ketikan manual tidak cocok dengan DB
                const noResult = document.createElement('div');
                noResult.className = 'suggestion-item text-muted text-center small';
                noResult.textContent = 'Data tidak ditemukan';
                boxEl.appendChild(noResult);
                boxEl.style.display = 'block';
                hiddenEl.value = ''; // Reset ID data karena input tidak valid
            }
        });

        // Pengaman: Jika user mengetik lalu menghapus atau mengubah teks tanpa memilih dari list,
        // ID lama akan dikosongkan secara otomatis demi integritas data database.
        inputEl.addEventListener('change', function() {
            setTimeout(() => {
                if(hiddenEl.value === "") {
                    // Opsional: jika ingin mengosongkan teks jika tidak memilih dari daftar
                    // inputEl.value = ""; 
                }
            }, 300);
        });

        // Menutup kotak pop-up saran otomatis jika user mengklik area lain di luar form
        document.addEventListener('click', function(e) {
            if (e.target !== inputEl && e.target !== boxEl) {
                boxEl.style.display = 'none';
            }
        });
    }

    // Jalankan sistem pencarian otomatis pada kedua kolom input manual
    setupLiveSearch('input_siswa', 'box_siswa', 'hidden_id_siswa', dataSiswa);
    setupLiveSearch('input_jenis', 'box_jenis', 'hidden_id_jenis', dataJenis);
});
</script>