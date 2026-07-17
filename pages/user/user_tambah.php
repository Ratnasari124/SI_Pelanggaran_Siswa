<?php
/** @var mysqli $conn */
// Cek apakah tombol simpan ditekan
if (isset($_POST['simpan'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Mengamankan password
    $nama     = $_POST['nama_lengkap'];
    $role     = $_POST['role'];

    // Query untuk menyimpan data
    $stmt = $conn->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $password, $nama, $role);

    if ($stmt->execute()) {
        echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Sukses!',
                    text: 'User berhasil ditambahkan!',
                    timer: 1500, // Notifikasi akan hilang dalam 1.5 detik
                    showConfirmButton: false // Menyembunyikan tombol OK
                }).then(() => { 
                    window.location.href = 'index.php?page=user'; 
                });
              </script>";
    } else {
        echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Terjadi kesalahan saat menyimpan data.',
                    timer: 2000,
                    showConfirmButton: false
                });
              </script>";
    }
}
?>

<div class="card">
    <div class="card-header">
        <h4>Tambah User Baru</h4>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="mb-3">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" name="password" class="form-control" id="password" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            <div class="mb-3">
                <label>Nama Lengkap</label>
                <input type="text" name="nama_lengkap" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" required>
                    <option value="" disabled selected>-- Pilih Role --</option>
                    <?php
                    $query = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
                    $data_kolom = $query->fetch_assoc();
                    
                    //Mengambil isi ENUM dalam bentuk string, contoh: enum('admin','guru','siswa')
                    $type = $data_kolom['Type'];
                    preg_match("/^enum\(\'(.*)\'\)$/", $type, $matches);
                    $enum_list = explode("','", $matches[1]);
                    foreach ($enum_list as $value) {
                        echo "<option value='$value'>" . ucfirst($value) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <button type="submit" name="simpan" class="btn btn-primary">Simpan User</button>
            <a href="index.php?page=user" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>
<script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');
    const eyeIcon = document.querySelector('#eyeIcon');

    togglePassword.addEventListener('click', function (e) {
        // Toggle tipe input antara 'password' dan 'text'
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        
        // Toggle ikon mata (eye vs eye-slash)
        eyeIcon.classList.toggle('fa-eye');
        eyeIcon.classList.toggle('fa-eye-slash');
    });
</script>