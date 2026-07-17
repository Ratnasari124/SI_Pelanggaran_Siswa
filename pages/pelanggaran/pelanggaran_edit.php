<?php
/** @var mysqli $conn */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pesan = "";

// 1. AMBIL ID DATA YANG AKAN DIEDIT
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>window.location.href = 'index.php?page=pelanggaran';</script>";
    exit;
}

$id_pelanggaran = intval($_GET['id']);

// 2. PROSES KETIKA TOMBOL SIMPAN/UPDATE DIKLIK
if (isset($_POST['update'])) {
    $id_siswa   = intval($_POST['id_siswa']);
    $id_jenis   = intval($_POST['id_jenis']);
    $tanggal    = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $id_user_pilihan = isset($_POST['id_user_petugas']) ? intval($_POST['id_user_petugas']) : 0;
    
    // Validasi input wajib
    if (empty($id_siswa) || empty($id_jenis) || empty($tanggal) || empty($id_user_pilihan)) {
        $pesan = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    <strong>Gagal!</strong> Pastikan Anda mengetik lalu memilih Siswa dan Jenis Pelanggaran dari daftar kecil yang muncul agar data tersimpan valid.
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                  </div>";
    } else {
        $sql = "UPDATE pelanggaran SET 
                id_siswa = '$id_siswa', 
                id_jenis = '$id_jenis', 
                tanggal = '$tanggal', 
                keterangan = '$keterangan', 
                id_user = '$id_user_pilihan' 
                WHERE id = '$id_pelanggaran'";
        
        $query = mysqli_query($conn, $sql);

        if ($query) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Catatan pelanggaran berhasil diperbarui.',
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
                        <strong>Gagal Memperbarui:</strong> " . mysqli_error($conn) . "
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                      </div>";
        }
    }
}

// 3. AMBIL DATA LAMA UNTUK DIISI PADA FORM (PRE-FILLED)
$query_lama = mysqli_query($conn, "
    SELECT p.*, s.nama AS nama_siswa, s.nis, j.nama_pelanggaran, j.poin 
    FROM pelanggaran p
    JOIN siswa s ON p.id_siswa = s.id
    JOIN jenis_pelanggaran j ON p.id_jenis = j.id
    WHERE p.id = '$id_pelanggaran'
");
$data_lama = mysqli_fetch_assoc($query_lama);

if (!$data_lama) {
    echo "<script>window.location.href = 'index.php?page=pelanggaran';</script>";
    exit;
}

// 4. DATA MASTER UNTUK AUTOCOMPLETE (SISWA & JENIS)
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
    /* Kotak Saran Kecil dan Ringkas Tepat di Bawah Kolom */
    .suggestion-box {
        position: absolute;
        top: 100%;
        left: 0;
        z-index: 1050;
        width: 100%;
        max-height: 200px;
        overflow-y: auto;
        background-color: #fff;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        display: none;
    }
    .suggestion-item {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f8f9fa;
        font-size: 0.85rem; /* Font diperkecil */
        transition: all 0.2s ease;
    }
    .suggestion-item:last-child {
        border-bottom: none;
    }
    .suggestion-item:hover {
        background-color: #fff5f5;
        color: #dc3545;
        padding-left: 16px;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-0 fw-bold text-dark">Form Ubah Catatan Pelanggaran</h3>
        <p class="text-muted small mb-0">Ubah isi kolom secara manual, lalu pilih kembali dari daftar kecil yang muncul jika ingin mengganti data.</p>
    </div>
    <a href="index.php?page=pelanggaran" class="btn btn-sm btn-secondary shadow-sm"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
</div>

<?= $pesan; ?>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <form method="POST" action="" autocomplete="off">
            
            <div class="row">
                <!-- 1. KOLOM NAMA / NISN SISWA (KECIL & MANUAL) -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold text-secondary small">Nama atau NIS/NISN Siswa</label>
                    <div class="autocomplete-wrapper">
                        <input type="text" id="input_siswa" class="form-control form-control-sm" 
                               value="<?= htmlspecialchars($data_lama['nis'] . ' - ' . $data_lama['nama_siswa']); ?>" 
                               placeholder="Ketik nama / NISN Siswa dan klik" required>
                        <input type="hidden" name="id_siswa" id="hidden_id_siswa" value="<?= $data_lama['id_siswa']; ?>">
                        <div id="box_siswa" class="suggestion-box"></div>
                    </div>
                </div>
                
                <!-- 2. KOLOM JENIS PELANGGARAN (KECIL & MANUAL) -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold text-secondary small">Bentuk / Jenis Pelanggaran</label>
                    <div class="autocomplete-wrapper">
                        <input type="text" id="input_jenis" class="form-control form-control-sm" 
                               value="<?= htmlspecialchars($data_lama['nama_pelanggaran'] . ' (+' . $data_lama['poin'] . ' Poin)'); ?>" 
                               placeholder="Ketik Jenis Pelanggaran dan klik" required>
                        <input type="hidden" name="id_jenis" id="hidden_id_jenis" value="<?= $data_lama['id_jenis']; ?>">
                        <div id="box_jenis" class="suggestion-box"></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- 3. PETUGAS PENCATAT -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold text-secondary small">Petugas Pencatat</label>
                    <select name="id_user_petugas" class="form-select form-select-sm" required>
                        <option value="">-- Pilih Guru / Provoost --</option>
                        <?php 
                        if ($query_petugas && mysqli_num_rows($query_petugas) > 0) {
                            while ($row_petugas = mysqli_fetch_assoc($query_petugas)) {
                                $role_display = ucfirst($row_petugas['role']); 
                                $selected = ($row_petugas['id'] == $data_lama['id_user']) ? "selected" : "";
                                echo "<option value='".$row_petugas['id']."' $selected>".htmlspecialchars($row_petugas['nama_lengkap'])." [".$role_display."]</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                
                <!-- 4. TANGGAL KEJADIAN -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold text-secondary small">Tanggal Kejadian</label>
                    <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= $data_lama['tanggal']; ?>" required>
                </div>
            </div>
            
            <!-- 5. KETERANGAN KRONOLOGI -->
            <div class="mb-4">
                <label class="form-label fw-semibold text-secondary small">Keterangan / Kronologi Singkat</label>
                <textarea name="keterangan" class="form-control form-control-sm" rows="4" placeholder="Tulis catatan tambahan..."><?= htmlspecialchars($data_lama['keterangan']); ?></textarea>
            </div>
            
            <div class="d-flex justify-content-end gap-2 border-top pt-3">
                <button type="button" onclick="window.location.reload();" class="btn btn-sm btn-outline-secondary px-4">Reset Form</button>
                <button type="submit" name="update" class="btn btn-sm btn-danger px-4 shadow-sm"><i class="fas fa-save me-2"></i>Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- JAVASCRIPT LIVE FILTER KETIK MANUAL -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dataSiswa = <?php echo json_encode($array_siswa); ?>;
    const dataJenis = <?php echo json_encode($array_jenis); ?>;

    function setupLiveSearch(inputId, boxId, hiddenId, sourceData) {
        const inputEl = document.getElementById(inputId);
        const boxEl = document.getElementById(boxId);
        const hiddenEl = document.getElementById(hiddenId);

        inputEl.addEventListener('input', function() {
            const keyword = this.value.toLowerCase().trim();
            boxEl.innerHTML = '';
            
            if (keyword === '') {
                boxEl.style.display = 'none';
                hiddenEl.value = '';
                return;
            }

            const filtered = sourceData.filter(item => item.searchable.includes(keyword));

            if (filtered.length > 0) {
                filtered.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'suggestion-item';
                    div.textContent = item.label;
                    
                    div.addEventListener('click', function() {
                        inputEl.value = item.label;
                        hiddenEl.value = item.id;
                        boxEl.style.display = 'none';
                    });
                    
                    boxEl.appendChild(div);
                });
                boxEl.style.display = 'block';
            } else {
                const noResult = document.createElement('div');
                noResult.className = 'suggestion-item text-muted text-center small';
                noResult.textContent = 'Data tidak ditemukan';
                boxEl.appendChild(noResult);
                boxEl.style.display = 'block';
                hiddenEl.value = ''; 
            }
        });

        // Menutup box pencarian jika klik di luar komponen
        document.addEventListener('click', function(e) {
            if (e.target !== inputEl && e.target !== boxEl) {
                boxEl.style.display = 'none';
            }
        });
    }

    // Jalankan sistem untuk halaman edit
    setupLiveSearch('input_siswa', 'box_siswa', 'hidden_id_siswa', dataSiswa);
    setupLiveSearch('input_jenis', 'box_jenis', 'hidden_id_jenis', dataJenis);
});
</script>