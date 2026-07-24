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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Pelanggaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: url('img/image.png') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.85); /* Efek glass */
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .login-card h3 {
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1.5rem;
            letter-spacing: 0.5px;
        }

        .form-control {
            border-radius: 10px;
            padding: 0.75rem;
            border: 1px solid #ced4da;
        }

        .btn-primary {
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 600;
            background: #288bcd;
            border: none;
            transition: 0.3s;
        }

        .btn-primary:hover {
            background: #1c6a9e;
        }

        .input-group-text {
            border-radius: 0 10px 10px 0;
            background: transparent;
        }
    </style>
</head>
<body>

    <div class="card login-card">
        <h3 class="text-center">Sistem Pelanggaran</h3>
        
        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label text-muted">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted">Password</label>
                <div class="input-group">
                    <input type="password" name="password" class="form-control" id="password" placeholder="Masukkan password" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePass()" style="border-radius: 0 10px 10px 0;">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" name="login" class="btn btn-primary w-100 mt-2">Masuk ke Sistem</button>
        </form>
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