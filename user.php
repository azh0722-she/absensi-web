<?php
session_start();
include 'koneksi.php'; // koneksi database

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'];

// Ambil data profil admin dari tabel admin
$stmt = mysqli_prepare($conn, "SELECT * FROM admin WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$resultAdmin = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($resultAdmin);

// Ambil semua user dari database
$query = "SELECT * FROM users ORDER BY id ASC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data User - Sistem Absensi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<style>
body {
  font-family:"Poppins",sans-serif;
  background:linear-gradient(135deg,#1e293b,#0f172a);
  color:#fff3f3;
  min-height:100vh;
  overflow-x:hidden;
}

.content {
  padding:30px;
  animation:fadeIn 0.8s ease;
}

.navbar {
  background: rgba(255,255,255,0.1);
  backdrop-filter: blur(12px);
  border-bottom:1px solid rgba(255,255,255,0.1);
}

.navbar img {
  width:32px;
  height:32px;
  border-radius:50%;
  object-fit:cover;
  margin-right:8px;
}

.card {
  background: rgba(172, 172, 172, 0.85);
  border-radius:15px;
  box-shadow:0 0 20px rgba(0,0,0,0.3);
  border:1px solid rgba(255,255,255,0.05);
}

.table {
  --bs-table-bg: transparent !important;
  --bs-table-striped-bg: rgba(255,255,255,0.05);
  --bs-table-hover-bg: rgba(255,255,255,0.1);
  color:#000 !important; /* teks hitam agar jelas */
  border-color: rgba(255,255,255,0.1) !important;
  vertical-align: middle;
}

.table thead {
  background-color: rgba(255,255,255,0.2) !important;
  color:#000;
}

.btn {
  font-weight:500;
}

@keyframes fadeIn {
  from { opacity:0; transform:translateY(10px); }
  to { opacity:1; transform:translateY(0); }
}
</style>
</head>
<body>
<div class="d-flex">
<!-- Sidebar -->
<div class="col-2">
  <?php include 'sidebar.php'; ?>
</div>


  <!-- Konten utama -->
  <div class="content flex-grow-1">
    <nav class="navbar navbar-expand-lg px-3 mb-4">
      <div class="container-fluid">
        <span class="navbar-brand text-light fw-semibold"><i class="bi bi-people-fill"></i> DATA AKUN USER</span>
        <div class="d-flex align-items-center">
          <img src="<?= !empty($admin['foto']) ? $admin['foto'] : 'https://cdn-icons-png.flaticon.com/512/847/847969.png' ?>" alt="Foto Profil">
          <span><?= htmlspecialchars($admin['nama_lengkap']) ?> (<?= htmlspecialchars($role) ?>)</span>
        </div>
      </div>
    </nav>

    <div class="card p-4">
        <a href="tambah_user.php" class="btn btn-success mb-3"><i class="bi bi-plus-circle"></i> Tambah User</a>
        <div class="table-responsive card p-4">
            <table class="table table-hover table-bordered align-middle">
                <thead class="text-center">
                    <tr>
                        <th>NO</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php $no = 1; // ✅ nomor urut ?>
                        <?php while($user = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="text-center"><?= $no++; ?></td> <!-- ✅ Ganti ID dengan nomor urut -->
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['role']) ?></td>
                                <td class="text-center">
                                    <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil-square"></i> Edit</a>
                                    <a href="hapus_user.php?id=<?= $user['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus user ini?');"><i class="bi bi-trash"></i> Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-dark">Tidak ada data user</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
fetch("sidebar.php")
  .then(res => res.text())
  .then(html => document.getElementById("sidebar-container").innerHTML = html);
</script>
</body>
</html>
