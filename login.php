<?php
session_start();
include 'koneksi.php'; // Pastikan file ini berisi koneksi ke database

$error = "";

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // Ambil data user berdasarkan username
    $query = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $query->bind_param("s", $username);
    $query->execute();
    $result = $query->get_result();
    $data = $result->fetch_assoc();

    if ($data) {
        // Cek apakah password cocok dengan hash di database
        if (password_verify($password, $data['password'])) {
            $_SESSION['login'] = true;
            $_SESSION['id'] = $data['id'];
            $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
            $_SESSION['role'] = $data['role'];

            header("Location: index.php");
            exit;
        } else {
            $error = "Username atau Password salah!";
        }
    } else {
        $error = "Username atau Password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Sistem Pelanggaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .login-card { width: 100%; max-width: 400px; margin-top: 100px; }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center">
        <div class="card login-card shadow p-4">
            <h3 class="text-center mb-4">Login Sistem</h3>
            <?php if($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Password</label>
                    <div class="input-group">
                        <input type="password" name="password" class="form-control" id="password" required>
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePass()">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
            </form>
        </div>
    </div>

    <script>
        function togglePass() {
            const p = document.getElementById("password");
            const icon = document.getElementById("eyeIcon");
            if (p.type === "password") {
                p.type = "text";
                icon.classList.replace("fa-eye", "fa-eye-slash");
            } else {
                p.type = "password";
                icon.classList.replace("fa-eye-slash", "fa-eye");
            }
        }
    </script>
</body>
</html>