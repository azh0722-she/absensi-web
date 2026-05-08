<?php
session_start();
include 'koneksi.php'; // koneksi ke database

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'admin';
$username = $_SESSION['username'] ?? 'Admin';

// Ambil data admin sesuai user_id
$stmt = mysqli_prepare($conn, "SELECT * FROM admin WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);

// Jika admin tidak ditemukan, gunakan default
if (!$admin) {
    $admin = [
        'nama_lengkap' => $username,
        'foto' => 'https://cdn-icons-png.flaticon.com/512/847/847969.png'
    ];
}

// Fitur pencarian siswa
$keyword = $_GET['keyword'] ?? '';
$query = "SELECT * FROM siswa";
if (!empty($keyword)) {
    $keyword = mysqli_real_escape_string($conn, $keyword);
    $query .= " WHERE (nama_lengkap LIKE '%$keyword%' 
                OR nis LIKE '%$keyword%' 
                OR jenis_kelamin LIKE '%$keyword%' 
                OR tahun_masuk LIKE '%$keyword%' 
                OR alamat LIKE '%$keyword%')";
}
$query .= " ORDER BY nama_lengkap ASC";


// Ambil data siswa (tanpa kolom kelas)
$data_siswa = [];
$result_siswa = mysqli_query($conn, $query);
if ($result_siswa) {
    while ($row = mysqli_fetch_assoc($result_siswa)) {
        $data_siswa[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Siswa - Sistem Absensi Siswa</title>

<!-- Bootstrap & Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<style>
body {
  font-family: "Poppins", sans-serif;
  background: linear-gradient(135deg, #1e293b, #0f172a);
  color: #fff3f3;
  min-height: 100vh;
  overflow-x: hidden;
}
.content {
  padding: 30px;
  animation: fadeIn 0.8s ease;
}
.navbar {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}
.navbar img {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
  margin-right: 8px;
}
.card {
  background: rgba(172, 172, 172, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 15px;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
}
.table {
  --bs-table-bg: transparent !important;
  --bs-table-striped-bg: rgba(255, 255, 255, 0.05);
  --bs-table-hover-bg: rgba(255, 255, 255, 0.1);
  color: #000 !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
  vertical-align: middle;
}
.table thead {
  background-color: rgba(255, 255, 255, 0.2) !important;
  color: #000;
}
.badge {
  font-size: 0.85rem;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
<!-- Sidebar -->
<div class="col-2">
  <?php include 'sidebar.php'; ?>
</div>


    <!-- Konten utama -->
    <div class="content flex-grow-1">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg px-3 mb-4">
            <div class="container-fluid">
                <span class="navbar-brand text-light fw-semibold"><i class="bi bi-people"></i> DATA SISWA </span>
                <div class="d-flex align-items-center">
                    <img src="<?= htmlspecialchars($admin['foto']) ?>" alt="Foto Profil">
                    <span><?= htmlspecialchars($admin['nama_lengkap']) ?> ( <?= htmlspecialchars($role) ?> )</span>
                </div>
            </div>
        </nav>

        <!-- Card daftar siswa -->
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <h5 class="fw-semibold mb-2 mb-md-0"><i class="bi bi-table"></i> Daftar Siswa Terdaftar</h5>

                <!-- Form Pencarian -->
                <form method="GET" class="d-flex" style="max-width: 300px;">
                    <input type="text" name="keyword" class="form-control me-2" placeholder="Cari nama / NIS / jurusan..." value="<?= htmlspecialchars($keyword) ?>">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
                </form>

                <a href="tambah_siswa.php" class="btn btn-success btn-sm mt-2 mt-md-0"><i class="bi bi-file-earmark-plus"></i> Tambah Data</a>
            </div>
                        <div class="table-responsive card p-4">
              <table class="table table-borderless table-striped table-hover align-middle "></table>

            <table class="table table-borderless table-hover align-middle">
                <thead class="text-center">
                    <tr>
                        <th>No</th>
                        <th>Nama Siswa</th>
                        <th>NIS</th>
                        <th>Jenis Kelamin</th>
                        <th>No. Telepon</th>
                        <th>Alamat</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data_siswa)) : ?>
                        <?php $no = 1; foreach ($data_siswa as $s) : ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= htmlspecialchars($s['nama_lengkap'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($s['nis'] ?? '-') ?></td>
                   
                                <td class="text-center"><?= $s['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                                <td><?= htmlspecialchars($s['no_telp'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($s['alamat'] ?? '-') ?></td>
                                <td>
                                    <?php if (($s['status'] ?? '') === 'aktif'): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php elseif (($s['status'] ?? '') === 'lulus'): ?>
                                        <span class="badge bg-primary">Lulus</span>
                                    <?php elseif (($s['status'] ?? '') === 'nonaktif'): ?>
                                        <span class="badge bg-warning text-dark">Nonaktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Keluar</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="edit_siswa.php?id=<?= $s['id'] ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a>
                                    <a href="aksi_siswa.php?hapus=<?= $s['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus data siswa ini, Yang Mulia?');"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-dark">Tidak ada data siswa yang ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
fetch("sidebar.php")
  .then(res => res.text())
  .then(html => document.getElementById("sidebar-container").innerHTML = html)
  .catch(err => console.error("Gagal memuat sidebar:", err));
</script>

</body>
</html>
