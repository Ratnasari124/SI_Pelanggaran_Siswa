<?php
/** @var mysqli $conn */
$id = $_GET['id'];

// 1. Ambil data user yang akan diedit
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// 2. Logika Update Data
if (isset($_POST['update'])) {
    $nama = $_POST['nama_lengkap'];
    $role = $_POST['role'];

    $update = $conn->prepare("UPDATE users SET nama_lengkap = ?, role = ? WHERE id = ?");
    $update->bind_param("ssi", $nama, $role, $id);
    
    if ($update->execute()) {
        echo "<script>Swal.fire('Berhasil', 'Data berhasil diperbarui!', 'success').then(() => { window.location.href='index.php?page=user'; });</script>";
    }
}

// 3. Logika Reset Password
if (isset($_POST['reset_password'])) {
    $password_default = password_hash('12345678', PASSWORD_DEFAULT);
    $reset = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $reset->bind_param("si", $password_default, $id);
    
    if ($reset->execute()) {
        echo "<script>Swal.fire('Berhasil', 'Password telah direset ke 12345678', 'success');</script>";
    }
}
?>

<div class="card">
    <div class="card-header"><h4>Edit User: <?= $user['username'] ?></h4></div>
    <div class="card-body">
        <form method="POST">
            <div class="mb-3">
                <label>Username</label>
                <input type="text" name="username" class="form-control" value="<?= $user['username'] ?>" required>
            </div>
            <div class="mb-3">
                <label>Nama Lengkap</label>
                <input type="text" name="nama_lengkap" class="form-control" value="<?= $user['nama_lengkap'] ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" required>
                    <?php
                    // 1. Ambil struktur kolom 'role' dari database
                    $query = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
                    $data_kolom = $query->fetch_assoc();
                    
                    // 2. Ambil nilai ENUM (formatnya: enum('admin','guru','siswa'))
                    $type = $data_kolom['Type'];
                    preg_match("/^enum\(\'(.*)\'\)$/", $type, $matches);
                    $enum_list = explode("','", $matches[1]);
                    
                    // 3. Loop untuk menampilkan setiap opsi
                    foreach ($enum_list as $value) {
                        // Cek apakah nilai ini yang sedang terpilih di database
                        $selected = ($user['role'] == $value) ? 'selected' : '';
                        echo "<option value='$value' $selected>" . ucfirst($value) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <button type="submit" name="update" class="btn btn-primary">Simpan Perubahan</button>
            
            <!-- Tombol Reset Password -->
            <button type="submit" name="reset_password" class="btn btn-warning" onclick="return confirm('Yakin ingin mereset password ke 12345678?')">
                Reset Password ke Default
            </button>
            
            <a href="index.php?page=user" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>