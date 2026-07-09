<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Pelanggaran Siswa</title>
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome untuk Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .sidebar { min-height: 100vh; background-color: #2c3e50; }
        .sidebar a { color: #ecf0f1; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover { background-color: #34495e; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar untuk mode HP akan otomatis menyesuaikan dengan col-md-2 -->
            <div class="col-md-2 col-12 sidebar p-0 d-md-block collapse" id="sidebarMenu">
                <?php include 'sidebar.php'; ?>
            </div>

            <!-- Konten Utama -->
            <div class="col-md-10 col-12 p-4">
                <!-- Tombol Toggle Sidebar untuk HP -->
                <button class="btn btn-dark d-md-none mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                    <i class="fas fa-bars"></i> Menu
                </button>

                <!-- Routing Halaman Dinamis -->
                <?php
                include 'koneksi.php';
                $page = isset($_GET['page']) ? $_GET['page'] : 'beranda';
                
                // LOGIKA BARU: Ambil nama folder dari kata pertama (sebelum tanda underscore)
                // Contoh: 'siswa_tambah' -> foldernya 'siswa'
                $folder = explode('_', $page)[0]; 
                
                // Cek path berdasarkan struktur folder Anda
                $file_baru = "pages/" . $folder . "/" . $page . ".php"; // Misal: pages/siswa/siswa_tambah.php
                $file_lama = "pages/" . $page . ".php";                 // Misal: pages/beranda.php (fallback)

                if (file_exists($file_baru)) {
                    include $file_baru;
                } elseif (file_exists($file_lama)) {
                    include $file_lama;
                } else {
                    echo "<h3>Halaman tidak ditemukan!</h3>";
                    echo "<p class='text-danger'>Sistem tidak dapat menemukan file di: $file_baru</p>";
                }
                ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>